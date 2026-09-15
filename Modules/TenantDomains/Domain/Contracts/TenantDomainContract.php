<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Domain\Contracts;

use Modules\TenantDomains\Domain\DTOs\TenantDomainRead;
use Modules\TenantDomains\Domain\DTOs\TenantDomainResolution;

/**
 * Public tenancy.domain capability contract.
 *
 * Maps hostnames to tenants. Never mutates Tenancy TenantContext.
 * Domain resolution is not authorization.
 */
interface TenantDomainContract
{
    /**
     * Resolve hostname to an active domain of an active tenant.
     * Miss → null. Never mutates TenantContext.
     */
    public function resolve(string $hostname): ?TenantDomainResolution;

    /**
     * @return list<TenantDomainRead>
     */
    public function listForTenant(int $tenantId): array;

    public function findByHostname(string $hostname): ?TenantDomainRead;

    public function attach(int $tenantId, string $hostname, bool $primary = false): TenantDomainRead;

    public function setPrimary(int $tenantId, int $domainId): TenantDomainRead;

    public function activate(int $domainId): void;

    public function deactivate(int $domainId): void;

    public function detach(int $domainId): void;
}
