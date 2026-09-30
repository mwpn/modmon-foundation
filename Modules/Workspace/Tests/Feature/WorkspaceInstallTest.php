<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;

class WorkspaceInstallTest extends WorkspaceTestCase
{
    public function test_discovered_before_install(): void
    {
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('workspace'));
        $this->artisan('module:doctor', ['code' => 'workspace'])->assertFailed();
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('workspace.owner'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('workspace.tenant'));
    }

    public function test_doctor_passes_when_dependencies_installed(): void
    {
        $manager = app(\App\Foundation\Runtime\ModuleManager::class);
        $this->assertTrue($manager->install('identity')['success']);
        $this->assertTrue($manager->install('tenancy')['success']);

        $this->artisan('module:doctor', ['code' => 'workspace'])->assertSuccessful();
    }

    public function test_install_enables_capabilities_without_migrations_table(): void
    {
        $this->installStack();

        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('workspace'),
        );
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('workspace.owner'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('workspace.tenant'));
        $this->assertFalse(Schema::hasTable('workspace_settings'));
    }
}
