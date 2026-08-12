<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contributions;

use App\Foundation\SDK\DTOs\PermissionDefinition;

/**
 * Interface for modules that declare permissions.
 */
interface ContributesPermissions
{
    /**
     * Return the permissions this module declares.
     *
     * @return PermissionDefinition[]
     */
    public function permissionDefinitions(): array;
}
