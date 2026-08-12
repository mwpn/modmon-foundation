<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contracts;

use App\Foundation\SDK\DTOs\PermissionDefinition;

/**
 * Contract for the permission registry.
 *
 * Modules declare their permissions through this registry.
 * An RBAC platform module (when installed) consumes these declarations.
 */
interface PermissionRegistryContract
{
    /**
     * Register permissions declared by a module.
     *
     * @param PermissionDefinition[] $permissions
     */
    public function register(string $moduleCode, array $permissions): void;

    /**
     * Remove all permissions declared by a module.
     */
    public function removeByModule(string $moduleCode): void;

    /**
     * Get all registered permissions.
     *
     * @return PermissionDefinition[]
     */
    public function all(): array;

    /**
     * Get permissions for a specific module.
     *
     * @return PermissionDefinition[]
     */
    public function forModule(string $moduleCode): array;

    /**
     * Get all permission identifiers grouped by module.
     *
     * @return array<string, string[]>
     */
    public function groupedByModule(): array;
}
