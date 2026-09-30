<?php

declare(strict_types=1);

namespace Modules\Workspace\Application\Services;

use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;

/**
 * Resolves active Experience workspace from request/domain attributes
 * then tenancy context, otherwise owner.
 *
 * Soft-reads {@see self::ATTR_TENANT_DOMAIN_TENANT_ID} when present
 * (published by TenantDomains middleware when that module is enabled).
 * Does not import TenantDomains and does not mutate TenantContext.
 */
final class ContextActiveWorkspace implements ActiveWorkspaceContract
{
    /**
     * Request attribute key aligned with TenantDomains ResolveTenantDomain
     * without a hard module dependency.
     */
    public const ATTR_TENANT_DOMAIN_TENANT_ID = 'tenant_domain.tenant_id';

    public const WORKSPACE_OWNER = 'workspace.owner';

    public const WORKSPACE_TENANT = 'workspace.tenant';

    public function __construct(
        private readonly TenantContextContract $context,
    ) {}

    public function current(): string
    {
        if ($this->resolvedDomainTenantId() !== null) {
            return self::WORKSPACE_TENANT;
        }

        $user = Auth::user();
        if ($user instanceof Authenticatable) {
            $tenantId = $this->context->currentTenantId((int) $user->getAuthIdentifier());
            if ($tenantId !== null) {
                return self::WORKSPACE_TENANT;
            }
        }

        return self::WORKSPACE_OWNER;
    }

    private function resolvedDomainTenantId(): ?int
    {
        $request = request();
        if (! $request) {
            return null;
        }

        $raw = $request->attributes->get(self::ATTR_TENANT_DOMAIN_TENANT_ID);
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}
