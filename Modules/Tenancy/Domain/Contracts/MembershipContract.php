<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Contracts;

use Modules\Tenancy\Domain\DTOs\MembershipRead;

/**
 * Public tenancy.membership capability contract.
 *
 * Membership is belonging of an Identity user to a tenant.
 * It is not login (Identity) and not permission/role assignment (RBAC).
 *
 * Phase 0: interface locked. Phase 1: persistence + binding.
 */
interface MembershipContract
{
    public function isMember(int $userId, int $tenantId): bool;

    /**
     * @return list<MembershipRead>
     */
    public function membershipsForUser(int $userId): array;

    /**
     * @return list<MembershipRead>
     */
    public function membersOfTenant(int $tenantId): array;

    public function add(int $userId, int $tenantId): MembershipRead;

    public function remove(int $userId, int $tenantId): void;
}
