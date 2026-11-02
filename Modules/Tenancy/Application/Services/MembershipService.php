<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Services;

use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\DTOs\MembershipRead;
use Modules\Tenancy\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenancy\Domain\Exceptions\UnknownUserException;
use Modules\Tenancy\Domain\Models\Membership;
use Modules\Tenancy\Domain\Models\Tenant;

/**
 * Database-backed MembershipContract. Belonging only — no roles.
 * Subjects validated via Identity UserQueryContract.
 */
final class MembershipService implements MembershipContract
{
    public function __construct(
        private readonly UserQueryContract $users,
    ) {}

    public function isMember(int $userId, int $tenantId): bool
    {
        return Membership::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    public function membershipsForUser(int $userId): array
    {
        return Membership::query()
            ->where('user_id', $userId)
            ->orderBy('tenant_id')
            ->get()
            ->map(fn (Membership $m) => $this->toRead($m))
            ->all();
    }

    public function membersOfTenant(int $tenantId): array
    {
        return Membership::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('user_id')
            ->get()
            ->map(fn (Membership $m) => $this->toRead($m))
            ->all();
    }

    public function add(int $userId, int $tenantId): MembershipRead
    {
        $this->assertKnownUser($userId);

        if (! Tenant::query()->whereKey($tenantId)->exists()) {
            throw new TenantNotFoundException("Tenant {$tenantId} not found.");
        }

        $existing = Membership::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existing !== null) {
            return $this->toRead($existing);
        }

        $membership = Membership::query()->create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);

        return $this->toRead($membership);
    }

    public function remove(int $userId, int $tenantId): void
    {
        Membership::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    private function assertKnownUser(int $userId): void
    {
        if ($this->users->findById($userId) === null) {
            throw new UnknownUserException("User {$userId} is unknown to Identity.");
        }
    }

    private function toRead(Membership $membership): MembershipRead
    {
        return new MembershipRead(
            id: (int) $membership->id,
            tenantId: (int) $membership->tenant_id,
            userId: (int) $membership->user_id,
            joinedAt: $membership->created_at?->toDateTimeImmutable(),
        );
    }
}
