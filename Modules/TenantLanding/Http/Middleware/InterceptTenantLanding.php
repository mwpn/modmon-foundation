<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Domain\DTOs\TenantDomainResolution;
use Modules\TenantLanding\Application\Services\LandingComposer;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve guest landing at `/` only when hostname resolves to an active tenant.
 * Unknown/inactive hosts pass through. Never mutates TenantContext.
 */
final class InterceptTenantLanding
{
    public const ATTR_RESOLUTION = 'tenant_domain.resolution';

    public function __construct(
        private readonly TenantDomainContract $domains,
        private readonly TenantLandingContract $landings,
        private readonly LandingComposer $composer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() !== '/') {
            return $next($request);
        }

        $resolution = $this->resolveTenant($request);
        if (! $resolution instanceof TenantDomainResolution) {
            return $next($request);
        }

        $config = $this->landings->forTenant($resolution->tenantId);
        $landing = $this->composer->compose($resolution, $config);

        return response()->view('tenant-landing::show', [
            'landing' => $landing,
        ]);
    }

    private function resolveTenant(Request $request): ?TenantDomainResolution
    {
        if ($request->attributes->has(self::ATTR_RESOLUTION)) {
            $attr = $request->attributes->get(self::ATTR_RESOLUTION);

            return $attr instanceof TenantDomainResolution ? $attr : null;
        }

        return $this->domains->resolve($request->getHost());
    }
}
