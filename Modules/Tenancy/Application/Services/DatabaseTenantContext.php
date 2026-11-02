<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Services;

use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\DTOs\TenantContextRead;
use Modules\Tenancy\Domain\DTOs\TenantRead;
use Modules\Tenancy\Domain\Exceptions\InvalidTenantContextException;
use Modules\Tenancy\Domain\Exceptions\UnknownUserException;
use Modules\Tenancy\Domain\Models\TenantContext;

/**
 * Database-backed TenantContextContract.
 *
 * Persists per-user current tenant in tenancy_contexts. Selection is
 * membership-gated and fail-closed for inactive tenants / missing membership.
 * clear() removes the selection row only — not membership or tenant rows.
 */
final class DatabaseTenantContext implements TenantContextContract
{
    public function __construct(
        private readonly UserQueryContract $users,
        private readonly MembershipContract $memberships,
        private readonly TenantContract $tenants,
    ) {}

    public function current(int $userId): TenantContextRead
    {
        $row = TenantContext::query()->where('user_id', $userId)->first();

        if ($row === null) {
            return new TenantContextRead(userId: $userId, tenantId: null);
        }

        $tenant = $this->resolveUsableTenant($userId, (int) $row->tenant_id);

        if ($tenant === null) {
            return new TenantContextRead(userId: $userId, tenantId: null);
        }

        return new TenantContextRead(
            userId: $userId,
            tenantId: $tenant->id,
            tenantCode: $tenant->code,
            tenantName: $tenant->name,
        );
    }

    public function currentTenant(int $userId): ?TenantRead
    {
        $tenantId = $this->currentTenantId($userId);

        return $tenantId === null ? null : $this->tenants->findById($tenantId);
    }

    public function currentTenantId(int $userId): ?int
    {
        return $this->current($userId)->tenantId;
    }

    public function setCurrent(int $userId, int $tenantId): void
    {
        if ($this->users->findById($userId) === null) {
            throw new UnknownUserException("User {$userId} is unknown to Identity.");
        }

        $tenant = $this->tenants->findById($tenantId);

        if ($tenant === null || ! $tenant->active) {
            throw new InvalidTenantContextException(
                "Cannot select tenant {$tenantId}: tenant missing or inactive.",
            );
        }

        if (! $this->memberships->isMember($userId, $tenantId)) {
            throw new InvalidTenantContextException(
                "Cannot select tenant {$tenantId}: user {$userId} is not a member.",
            );
        }

        TenantContext::query()->updateOrCreate(
            ['user_id' => $userId],
            ['tenant_id' => $tenantId],
        );
    }

    public function clear(int $userId): void
    {
        TenantContext::query()->where('user_id', $userId)->delete();
    }

    private function resolveUsableTenant(int $userId, int $tenantId): ?TenantRead
    {
        $tenant = $this->tenants->findById($tenantId);

        if ($tenant === null || ! $tenant->active) {
            return null;
        }

        if (! $this->memberships->isMember($userId, $tenantId)) {
            return null;
        }

        return $tenant;
    }
}
