<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Application\Services;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use Illuminate\Support\Facades\Route;
use Modules\TenantDomains\Domain\DTOs\TenantDomainResolution;
use Modules\TenantLanding\Domain\DTOs\EffectiveLanding;
use Modules\TenantLanding\Domain\DTOs\TenantLandingRead;
use Throwable;

/**
 * Composes guest landing from domain resolution + optional branding/auth.
 *
 * Branding cascade (optional): branding.tenant → branding.application → TenantRead.name
 * Login CTA only when identity.authentication + identity.login route exist.
 */
final class LandingComposer
{
    private const BRANDING_TENANT = 'Modules\\TenancyBranding\\Domain\\Contracts\\TenantBrandingContract';

    private const BRANDING_APPLICATION = 'Modules\\Branding\\Domain\\Contracts\\BrandingContract';

    public function __construct(
        private readonly CapabilityRegistryContract $capabilities,
    ) {}

    public function compose(
        TenantDomainResolution $resolution,
        TenantLandingRead $config,
    ): EffectiveLanding {
        $tenant = $resolution->tenant;
        $brand = $this->resolveBrand($tenant->id, $tenant->name);

        $headline = $config->headline ?? $brand['name'];
        $loginUrl = null;
        if ($config->showLoginCta) {
            $loginUrl = $this->resolveLoginUrl();
        }

        return new EffectiveLanding(
            tenantId: $tenant->id,
            tenantCode: $tenant->code,
            brandName: $brand['name'],
            logoUrl: $brand['logoUrl'],
            logoDarkUrl: $brand['logoDarkUrl'],
            faviconUrl: $brand['faviconUrl'],
            primaryColor: $brand['primaryColor'],
            accentColor: $brand['accentColor'],
            headline: $headline,
            summary: $config->summary,
            loginUrl: $loginUrl,
        );
    }

    /**
     * @return array{
     *     name: string,
     *     logoUrl: ?string,
     *     logoDarkUrl: ?string,
     *     faviconUrl: ?string,
     *     primaryColor: ?string,
     *     accentColor: ?string
     * }
     */
    private function resolveBrand(int $tenantId, string $tenantName): array
    {
        if ($this->capabilities->has('branding.tenant')) {
            $fromTenant = $this->tryBranding(self::BRANDING_TENANT, static function (object $contract) use ($tenantId) {
                return $contract->forTenant($tenantId);
            });
            if ($fromTenant !== null) {
                return $fromTenant;
            }
        }

        if ($this->capabilities->has('branding.application')) {
            $fromApp = $this->tryBranding(self::BRANDING_APPLICATION, static function (object $contract) {
                return $contract->current();
            });
            if ($fromApp !== null) {
                return $fromApp;
            }
        }

        return [
            'name' => $tenantName,
            'logoUrl' => null,
            'logoDarkUrl' => null,
            'faviconUrl' => null,
            'primaryColor' => null,
            'accentColor' => null,
        ];
    }

    /**
     * @param  callable(object): object  $call
     * @return array{
     *     name: string,
     *     logoUrl: ?string,
     *     logoDarkUrl: ?string,
     *     faviconUrl: ?string,
     *     primaryColor: ?string,
     *     accentColor: ?string
     * }|null
     */
    private function tryBranding(string $contractFqn, callable $call): ?array
    {
        if (! interface_exists($contractFqn) || ! app()->bound($contractFqn)) {
            return null;
        }

        try {
            $read = $call(app($contractFqn));
        } catch (Throwable) {
            return null;
        }

        return [
            'name' => (string) $read->name,
            'logoUrl' => $read->logoUrl,
            'logoDarkUrl' => $read->logoDarkUrl,
            'faviconUrl' => $read->faviconUrl,
            'primaryColor' => $read->primaryColor,
            'accentColor' => $read->accentColor,
        ];
    }

    private function resolveLoginUrl(): ?string
    {
        if (! $this->capabilities->has('identity.authentication')) {
            return null;
        }

        if (! Route::has('identity.login')) {
            return null;
        }

        try {
            return route('identity.login');
        } catch (Throwable) {
            return null;
        }
    }
}
