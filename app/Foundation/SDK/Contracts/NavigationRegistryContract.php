<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contracts;

use App\Foundation\SDK\DTOs\NavigationItem;

/**
 * Contract for the navigation registry.
 *
 * Modules contribute navigation items through this registry.
 * The Experience shell renders items from enabled modules only.
 */
interface NavigationRegistryContract
{
    /**
     * Register a navigation item contributed by a module.
     */
    public function register(NavigationItem $item): void;

    /**
     * Remove all navigation items contributed by a module.
     */
    public function removeByModule(string $moduleCode): void;

    /**
     * Get all registered navigation items, optionally filtered by workspace.
     *
     * @return NavigationItem[]
     */
    public function items(?string $workspace = null): array;

    /**
     * Get navigation items grouped by their group identifier.
     *
     * @return array<string, NavigationItem[]>
     */
    public function grouped(?string $workspace = null): array;
}
