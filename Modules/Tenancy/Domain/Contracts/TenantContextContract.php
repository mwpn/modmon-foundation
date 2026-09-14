<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Contracts;

use Modules\Tenancy\Domain\DTOs\TenantContextRead;
use Modules\Tenancy\Domain\DTOs\TenantRead;

/**
 * Public tenancy.context capability contract.
 *
 * Resolves and switches the current tenant for a subject. Switching is
 * gated by membership only — not by RBAC permissions.
 *
 * Persistence is module-owned (`tenancy_contexts`). Not a Foundation API.
 */
interface TenantContextContract
{
    public function current(int $userId): TenantContextRead;

    public function currentTenant(int $userId): ?TenantRead;

    public function currentTenantId(int $userId): ?int;

    /**
     * Select current tenant. Must reject when the user is not an active
     * member of an active tenant (Phase 1 invariant).
     */
    public function setCurrent(int $userId, int $tenantId): void;

    public function clear(int $userId): void;
}
