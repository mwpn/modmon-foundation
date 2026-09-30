<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Feature;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;

class WorkspaceContributionTest extends WorkspaceTestCase
{
    public function test_navigation_and_widgets_are_workspace_scoped(): void
    {
        $this->installStack();

        $nav = collect(app(NavigationRegistryContract::class)->items());
        $owner = $nav->first(fn ($i) => $i->id === 'workspace.owner.home');
        $tenant = $nav->first(fn ($i) => $i->id === 'workspace.tenant.home');

        $this->assertNotNull($owner);
        $this->assertSame('workspace.owner', $owner->workspace);
        $this->assertNotNull($tenant);
        $this->assertSame('workspace.tenant', $tenant->workspace);

        $ownerWidgets = app(WorkspaceRegistryContract::class)
            ->widgetsForSlot('workspace.owner.dashboard.main');
        $tenantWidgets = app(WorkspaceRegistryContract::class)
            ->widgetsForSlot('workspace.tenant.dashboard.main');
        $defaultWidgets = app(WorkspaceRegistryContract::class)
            ->widgetsForSlot('workspace.default.dashboard.main');

        $this->assertTrue(collect($ownerWidgets)->contains(fn ($w) => $w->id === 'workspace.owner.marker'));
        $this->assertTrue(collect($tenantWidgets)->contains(fn ($w) => $w->id === 'workspace.tenant.marker'));
        $this->assertFalse(collect($defaultWidgets)->contains(fn ($w) => $w->moduleCode === 'workspace'));
    }
}
