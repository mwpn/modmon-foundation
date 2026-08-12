<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Workspace;

use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\DTOs\DashboardWidget;

/**
 * In-memory workspace/dashboard registry.
 *
 * Modules contribute widgets to named workspace slots.
 */
class WorkspaceRegistry implements WorkspaceRegistryContract
{
    /** @var DashboardWidget[] */
    private array $widgets = [];

    public function register(DashboardWidget $widget): void
    {
        $this->widgets[$widget->id] = $widget;
    }

    public function removeByModule(string $moduleCode): void
    {
        $this->widgets = array_filter(
            $this->widgets,
            fn (DashboardWidget $w) => $w->moduleCode !== $moduleCode,
        );
    }

    public function widgetsForSlot(string $slot): array
    {
        $widgets = array_filter(
            $this->widgets,
            fn (DashboardWidget $w) => $w->slot === $slot,
        );

        $sorted = array_values($widgets);
        usort($sorted, fn (DashboardWidget $a, DashboardWidget $b) => $a->order <=> $b->order);

        return $sorted;
    }

    public function workspaces(): array
    {
        $workspaces = [];
        foreach ($this->widgets as $widget) {
            // Extract workspace from slot: "workspace.owner.dashboard.stats" → "workspace.owner"
            $parts = explode('.', $widget->slot);
            if (count($parts) >= 2) {
                $ws = $parts[0] . '.' . $parts[1];
                $workspaces[$ws] = true;
            }
        }

        return array_keys($workspaces);
    }

    public function slotsFor(string $workspace): array
    {
        $slots = [];
        foreach ($this->widgets as $widget) {
            if (str_starts_with($widget->slot, $workspace . '.')) {
                $slots[$widget->slot] = true;
            }
        }

        return array_keys($slots);
    }
}
