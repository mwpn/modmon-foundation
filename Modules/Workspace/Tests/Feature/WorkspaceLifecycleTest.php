<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Feature;

use App\Foundation\Experience\Workspace\DefaultActiveWorkspace;
use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\ModuleState;
use Modules\Workspace\Application\Services\ContextActiveWorkspace;

class WorkspaceLifecycleTest extends WorkspaceTestCase
{
    public function test_disable_removes_contributions_and_fresh_boot_restores_default_workspace(): void
    {
        $manager = $this->installStack();
        $this->assertInstanceOf(ContextActiveWorkspace::class, app(ActiveWorkspaceContract::class));

        $this->assertTrue($manager->disable('workspace')['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('workspace'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('workspace.owner'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'workspace'),
        );
        $this->assertFalse(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.owner.dashboard.main'))
                ->contains(fn ($w) => $w->moduleCode === 'workspace'),
        );

        static::$bootModulesJson = (string) file_get_contents(storage_path('app/modules.json'));
        $this->refreshApplication();

        $this->assertEquals(
            ModuleState::Disabled,
            app(ModuleRegistrarContract::class)->getState('workspace'),
        );
        $this->assertInstanceOf(DefaultActiveWorkspace::class, app(ActiveWorkspaceContract::class));
        $this->assertSame(ActiveWorkspaceContract::DEFAULT, app(ActiveWorkspaceContract::class)->current());
    }

    public function test_enable_restores_binding_and_contributions(): void
    {
        $manager = $this->installStack();
        $this->assertTrue($manager->disable('workspace')['success']);
        $this->assertTrue($manager->enable('workspace')['success']);

        $this->app->forgetInstance(ActiveWorkspaceContract::class);
        $this->app->forgetInstance(ContextActiveWorkspace::class);

        $this->assertTrue(app(CapabilityRegistryContract::class)->has('workspace.tenant'));
        $this->assertInstanceOf(ContextActiveWorkspace::class, app(ActiveWorkspaceContract::class));
        $this->assertTrue(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->id === 'workspace.owner.home'),
        );
    }
}
