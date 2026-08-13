<?php

declare(strict_types=1);

namespace Modules\Rbac\Domain\Contracts;

/**
 * Public RBAC boundary for other modules.
 *
 * Resolves to a module implementation; never import `Modules\Rbac`
 * internals from outside the module. All user ids are validated
 * against the Identity `UserQueryContract`.
 */
interface AuthorizationContract
{
    /**
     * Whether the identity has the permission through any of its roles.
     */
    public function identityHasPermission(string $userId, string $permissionId): bool;
}
