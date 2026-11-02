<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Application\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenantDomains\Application\HostnameNormalizer;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Domain\DTOs\TenantDomainRead;
use Modules\TenantDomains\Domain\DTOs\TenantDomainResolution;
use Modules\TenantDomains\Domain\Models\TenantDomain;

/**
 * Database-backed TenantDomainContract.
 * Uses TenantContract only — never Tenancy tables or TenantContext.
 */
final class TenantDomainService implements TenantDomainContract
{
    public function __construct(
        private readonly TenantContract $tenants,
        private readonly HostnameNormalizer $normalizer,
    ) {}

    public function resolve(string $hostname): ?TenantDomainResolution
    {
        $normalized = $this->normalizer->tryNormalize($hostname);
        if ($normalized === null) {
            return null;
        }

        $row = TenantDomain::query()
            ->where('hostname', $normalized)
            ->where('active', true)
            ->first();

        if ($row === null) {
            return null;
        }

        $tenant = $this->tenants->findById((int) $row->tenant_id);
        if ($tenant === null || ! $tenant->active) {
            return null;
        }

        return new TenantDomainResolution(
            tenantId: $tenant->id,
            hostname: $normalized,
            isPrimary: (bool) $row->is_primary,
            tenant: $tenant,
        );
    }

    public function listForTenant(int $tenantId): array
    {
        $this->assertTenantExists($tenantId);

        return TenantDomain::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_primary')
            ->orderBy('hostname')
            ->get()
            ->map(fn (TenantDomain $row) => $this->toRead($row))
            ->all();
    }

    public function findByHostname(string $hostname): ?TenantDomainRead
    {
        $normalized = $this->normalizer->tryNormalize($hostname);
        if ($normalized === null) {
            return null;
        }

        $row = TenantDomain::query()->where('hostname', $normalized)->first();

        return $row === null ? null : $this->toRead($row);
    }

    public function attach(int $tenantId, string $hostname, bool $primary = false): TenantDomainRead
    {
        $this->assertActiveTenant($tenantId);
        $normalized = $this->normalizer->normalize($hostname);

        if (TenantDomain::query()->where('hostname', $normalized)->exists()) {
            throw new InvalidArgumentException("Hostname '{$normalized}' is already attached.");
        }

        return DB::transaction(function () use ($tenantId, $normalized, $primary) {
            if ($primary) {
                $this->clearPrimaryForTenant($tenantId);
            }

            $row = TenantDomain::query()->create([
                'tenant_id' => $tenantId,
                'hostname' => $normalized,
                'is_primary' => $primary,
                'active' => true,
            ]);

            return $this->toRead($row);
        });
    }

    public function setPrimary(int $tenantId, int $domainId): TenantDomainRead
    {
        $this->assertTenantExists($tenantId);

        return DB::transaction(function () use ($tenantId, $domainId) {
            $row = TenantDomain::query()->lockForUpdate()->find($domainId);

            if ($row === null || (int) $row->tenant_id !== $tenantId) {
                throw new InvalidArgumentException(
                    "Domain '{$domainId}' was not found for tenant '{$tenantId}'.",
                );
            }

            $this->clearPrimaryForTenant($tenantId);
            $row->is_primary = true;
            $row->save();

            return $this->toRead($row->fresh());
        });
    }

    public function activate(int $domainId): void
    {
        $row = $this->findOwned($domainId);
        $row->active = true;
        $row->save();
    }

    public function deactivate(int $domainId): void
    {
        $row = $this->findOwned($domainId);
        $row->active = false;
        $row->save();
    }

    public function detach(int $domainId): void
    {
        $row = $this->findOwned($domainId);
        $row->delete();
    }

    private function clearPrimaryForTenant(int $tenantId): void
    {
        TenantDomain::query()
            ->where('tenant_id', $tenantId)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    private function assertActiveTenant(int $tenantId): void
    {
        $tenant = $this->tenants->findById($tenantId);

        if ($tenant === null || ! $tenant->active) {
            throw new InvalidArgumentException(
                "Tenant '{$tenantId}' was not found or is inactive.",
            );
        }
    }

    private function assertTenantExists(int $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw new InvalidArgumentException("Tenant '{$tenantId}' was not found.");
        }
    }

    private function findOwned(int $domainId): TenantDomain
    {
        $row = TenantDomain::query()->find($domainId);

        if ($row === null) {
            throw new InvalidArgumentException("Domain '{$domainId}' was not found.");
        }

        return $row;
    }

    private function toRead(TenantDomain $row): TenantDomainRead
    {
        return new TenantDomainRead(
            id: (int) $row->id,
            tenantId: (int) $row->tenant_id,
            hostname: (string) $row->hostname,
            isPrimary: (bool) $row->is_primary,
            active: (bool) $row->active,
        );
    }
}
