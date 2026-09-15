<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Domain\Contracts;

use Modules\TenantLanding\Domain\DTOs\TenantLandingData;
use Modules\TenantLanding\Domain\DTOs\TenantLandingRead;

/**
 * Public tenancy.landing capability contract.
 *
 * Owns minimal per-tenant landing copy. Does not resolve hostnames,
 * mutate TenantContext, or own branding/login.
 */
interface TenantLandingContract
{
    /**
     * Config for a tenant. Missing row → defaults (null copy, showLoginCta true).
     */
    public function forTenant(int $tenantId): TenantLandingRead;

    public function update(int $tenantId, TenantLandingData $data): TenantLandingRead;

    /**
     * @return list<TenantLandingRead>
     */
    public function allConfigured(): array;
}
