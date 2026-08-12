<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Foundation\Experience\Workspace\WorkspaceRegistry;
use App\Foundation\SDK\DTOs\DashboardWidget;
use PHPUnit\Framework\TestCase;

class WorkspaceRegistryTest extends TestCase
{
    private WorkspaceRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new WorkspaceRegistry();
    }

    public function test_register_and_retrieve_widgets(): void
    {
        $widget = new DashboardWidget(
            id: 'test.widget',
            moduleCode: 'test',
            slot: 'workspace.owner.dashboard.main',
            view: 'test::widget',
        );

        $this->registry->register($widget);
        $widgets = $this->registry->widgetsForSlot('workspace.owner.dashboard.main');
        $this->assertCount(1, $widgets);
    }

    public function test_remove_by_module(): void
    {
        $this->registry->register(new DashboardWidget('a.w', 'mod-a', 'workspace.owner.dashboard.main', 'a::w'));
        $this->registry->register(new DashboardWidget('b.w', 'mod-b', 'workspace.owner.dashboard.main', 'b::w'));

        $this->registry->removeByModule('mod-a');

        $widgets = $this->registry->widgetsForSlot('workspace.owner.dashboard.main');
        $this->assertCount(1, $widgets);
        $this->assertEquals('b.w', $widgets[0]->id);
    }

    public function test_widgets_sorted_by_order(): void
    {
        $this->registry->register(new DashboardWidget('b', 'mod', 'slot', 'v', order: 200));
        $this->registry->register(new DashboardWidget('a', 'mod', 'slot', 'v', order: 10));

        $widgets = $this->registry->widgetsForSlot('slot');
        $this->assertEquals('a', $widgets[0]->id);
    }

    public function test_workspaces_extracts_unique_workspace_ids(): void
    {
        $this->registry->register(new DashboardWidget('a', 'mod', 'workspace.owner.dashboard.main', 'v'));
        $this->registry->register(new DashboardWidget('b', 'mod', 'workspace.owner.dashboard.stats', 'v'));
        $this->registry->register(new DashboardWidget('c', 'mod', 'workspace.tenant.dashboard.main', 'v'));

        $workspaces = $this->registry->workspaces();
        $this->assertCount(2, $workspaces);
        $this->assertContains('workspace.owner', $workspaces);
        $this->assertContains('workspace.tenant', $workspaces);
    }

    public function test_slots_for_workspace(): void
    {
        $this->registry->register(new DashboardWidget('a', 'mod', 'workspace.owner.dashboard.main', 'v'));
        $this->registry->register(new DashboardWidget('b', 'mod', 'workspace.owner.dashboard.stats', 'v'));
        $this->registry->register(new DashboardWidget('c', 'mod', 'workspace.tenant.dashboard.main', 'v'));

        $slots = $this->registry->slotsFor('workspace.owner');
        $this->assertCount(2, $slots);
        $this->assertContains('workspace.owner.dashboard.main', $slots);
    }
}
