<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModuleLifecycleTest extends TestCase
{
    private string $modulesJsonPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulesJsonPath = storage_path('app/modules.json');

        // Clean state for each test
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        // Re-bind a fresh registrar
        $this->app->forgetInstance(ModuleRegistrarContract::class);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(CapabilityRegistryContract::class);
        $this->app->forgetInstance(NavigationRegistryContract::class);
        $this->app->forgetInstance(WorkspaceRegistryContract::class);
        $this->app->forgetInstance(PermissionRegistryContract::class);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }
        parent::tearDown();
    }

    public function test_module_discovered_before_install(): void
    {
        $manager = app(ModuleManager::class);
        $manifests = $manager->discover();

        $this->assertArrayHasKey('example', $manifests);

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertNull($registrar->getState('example'));
    }

    public function test_module_install_succeeds(): void
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('example');

        $this->assertTrue($result['success'], implode(', ', $result['messages']));

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertEquals(ModuleState::Enabled, $registrar->getState('example'));
    }

    public function test_install_registers_capabilities(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertTrue($capabilities->has('example.demo'));
    }

    public function test_install_registers_navigation(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');

        $nav = app(NavigationRegistryContract::class);
        $items = $nav->items();
        $this->assertNotEmpty($items);
        $this->assertEquals('example.dashboard', $items[0]->id);
    }

    public function test_install_registers_dashboard_widgets(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');

        $workspace = app(WorkspaceRegistryContract::class);
        $widgets = $workspace->widgetsForSlot('workspace.default.dashboard.main');
        $this->assertNotEmpty($widgets);
    }

    public function test_install_registers_permissions(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');

        $permissions = app(PermissionRegistryContract::class);
        $all = $permissions->all();
        $this->assertCount(2, $all);
    }

    public function test_install_creates_routes(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');

        // Routes should be registered
        $this->assertTrue(
            collect(app('router')->getRoutes())->contains(fn ($route) => $route->uri() === 'example'),
            'Route /example should be registered after install',
        );
    }

    public function test_disable_removes_capabilities(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertFalse($capabilities->has('example.demo'));
    }

    public function test_disable_removes_navigation(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');

        $nav = app(NavigationRegistryContract::class);
        $items = $nav->items();
        $this->assertEmpty($items);
    }

    public function test_disable_removes_dashboard_widgets(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');

        $workspace = app(WorkspaceRegistryContract::class);
        $widgets = $workspace->widgetsForSlot('workspace.default.dashboard.main');
        $this->assertEmpty($widgets);
    }

    public function test_disable_removes_permissions(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');

        $permissions = app(PermissionRegistryContract::class);
        $this->assertEmpty($permissions->all());
    }

    public function test_disable_preserves_state_as_disabled(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertEquals(ModuleState::Disabled, $registrar->getState('example'));
    }

    public function test_reenable_restores_capabilities(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');
        $manager->enable('example');

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertTrue($capabilities->has('example.demo'));
    }

    public function test_reenable_restores_navigation(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');
        $manager->enable('example');

        $nav = app(NavigationRegistryContract::class);
        $items = $nav->items();
        $this->assertNotEmpty($items);
    }

    public function test_reenable_restores_permissions(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');
        $manager->enable('example');

        $permissions = app(PermissionRegistryContract::class);
        $this->assertCount(2, $permissions->all());
    }

    public function test_reenable_state_becomes_enabled(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');
        $manager->enable('example');

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertEquals(ModuleState::Enabled, $registrar->getState('example'));
    }

    public function test_double_install_fails(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $result = $manager->install('example');

        $this->assertFalse($result['success']);
    }

    public function test_install_nonexistent_module_fails(): void
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('nonexistent');

        $this->assertFalse($result['success']);
    }

    public function test_enable_non_installed_module_fails(): void
    {
        $manager = app(ModuleManager::class);
        $result = $manager->enable('example');

        $this->assertFalse($result['success']);
    }

    public function test_diagnose_returns_diagnostics(): void
    {
        $manager = app(ModuleManager::class);
        $diagnostics = $manager->diagnose('example');

        $this->assertNotEmpty($diagnostics);

        // Should have manifest, provider, state checks at minimum
        $checks = array_map(fn ($d) => $d->check, $diagnostics);
        $this->assertContains('manifest', $checks);
        $this->assertContains('provider', $checks);
        $this->assertContains('state', $checks);
    }

    public function test_diagnose_nonexistent_module(): void
    {
        $manager = app(ModuleManager::class);
        $diagnostics = $manager->diagnose('nonexistent');

        $this->assertCount(1, $diagnostics);
        $this->assertFalse($diagnostics[0]->passed);
    }
}
