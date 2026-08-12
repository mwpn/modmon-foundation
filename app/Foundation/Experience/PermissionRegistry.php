<?php

declare(strict_types=1);

namespace App\Foundation\Experience;

use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\DTOs\PermissionDefinition;

/**
 * In-memory permission registry.
 *
 * Modules declare their permissions here. An RBAC platform module
 * (when installed) consumes these declarations.
 */
class PermissionRegistry implements PermissionRegistryContract
{
    /** @var array<string, PermissionDefinition[]> moduleCode => permissions */
    private array $permissions = [];

    public function register(string $moduleCode, array $permissions): void
    {
        $this->permissions[$moduleCode] = $permissions;
    }

    public function removeByModule(string $moduleCode): void
    {
        unset($this->permissions[$moduleCode]);
    }

    public function all(): array
    {
        return array_merge(...array_values($this->permissions ?: [[]]));
    }

    public function forModule(string $moduleCode): array
    {
        return $this->permissions[$moduleCode] ?? [];
    }

    public function groupedByModule(): array
    {
        $grouped = [];
        foreach ($this->permissions as $moduleCode => $perms) {
            $grouped[$moduleCode] = array_map(fn (PermissionDefinition $p) => $p->id, $perms);
        }

        return $grouped;
    }
}
