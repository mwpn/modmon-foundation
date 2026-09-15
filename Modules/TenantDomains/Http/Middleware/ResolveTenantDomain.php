<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Domain\DTOs\TenantDomainResolution;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve Host → tenant onto request attributes only.
 * Never mutates Tenancy TenantContext. Not authorization.
 */
final class ResolveTenantDomain
{
    public const ATTR_RESOLUTION = 'tenant_domain.resolution';

    public const ATTR_TENANT_ID = 'tenant_domain.tenant_id';

    public function __construct(
        private readonly TenantDomainContract $domains,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $resolution = $this->domains->resolve($request->getHost());

        $request->attributes->set(self::ATTR_RESOLUTION, $resolution);
        $request->attributes->set(
            self::ATTR_TENANT_ID,
            $resolution instanceof TenantDomainResolution ? $resolution->tenantId : null,
        );

        return $next($request);
    }
}
