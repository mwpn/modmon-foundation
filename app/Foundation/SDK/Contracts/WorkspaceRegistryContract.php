<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contracts;

use App\Foundation\SDK\DTOs\DashboardWidget;

/**
 * Contract for the workspace/dashboard registry.
 *
 * Modules contribute dashboard widgets to named workspace slots.
 * The Experience shell renders widgets from enabled modules only.
 */
interface WorkspaceRegistryContract
{
    /**
     * Register a dashboard widget contributed by a module.
     */
    public function register(DashboardWidget $widget): void;

    /**
     * Remove all widgets contributed by a module.
     */
    public function removeByModule(string $moduleCode): void;

    /**
     * Get widgets for a specific slot.
     *
     * @return DashboardWidget[]
     */
    public function widgetsForSlot(string $slot): array;

    /**
     * Get all available workspace identifiers.
     *
     * @return string[]
     */
    public function workspaces(): array;

    /**
     * Get all slots for a workspace.
     *
     * @return string[]
     */
    public function slotsFor(string $workspace): array;
}
