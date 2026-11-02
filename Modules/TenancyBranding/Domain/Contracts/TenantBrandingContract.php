<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Domain\Contracts;

use Modules\Branding\Domain\DTOs\BrandingRead;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideData;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideRead;

/**
 * Public branding.tenant capability contract.
 *
 * Composes tenant overrides over BrandingContract — does not replace
 * branding.application.
 */
interface TenantBrandingContract
{
    /**
     * Effective branding for an explicit tenant (merge over application).
     * Unknown or inactive tenant → fail closed.
     */
    public function forTenant(int $tenantId): BrandingRead;

    /**
     * Effective branding for the subject's current tenant context.
     * No current tenant → identical to BrandingContract::current().
     */
    public function forCurrentUser(int $userId): BrandingRead;

    /**
     * Raw override row (null fields = inherit). Unknown/inactive tenant → fail closed.
     */
    public function overrideFor(int $tenantId): TenantBrandingOverrideRead;

    public function updateOverride(int $tenantId, TenantBrandingOverrideData $data): BrandingRead;

    /**
     * Delete override row and owned assets; returns application branding.
     */
    public function clearOverride(int $tenantId): BrandingRead;
}
