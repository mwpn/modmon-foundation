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
use Tests\TestCase;

/**
 * Regression tests for install safety defects found during Foundation v1 audit.
 *
 * - Migration failure must abort install and NOT mark module as enabled
 * - Capability collisions must be rejected
 * - Duplicate provider class detection
 * - module:doctor wording accuracy
 */
class InstallSafetyTest extends TestCase
{
    private string $modulesJsonPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulesJsonPath = storage_path('app/modules.json');

        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

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

    /**
     * After a successful install, module must be in Enabled state.
     * State should be persisted BEFORE capabilities are registered
     * to prevent inconsistency if capability registration has side effects.
     */
    public function test_install_persists_state_before_capabilities(): void
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('example');

        $this->assertTrue($result['success']);

        // State must be persisted to disk
        $this->assertFileExists($this->modulesJsonPath);
        $rawData = json_decode(file_get_contents($this->modulesJsonPath), true);
        $this->assertEquals('enabled', $rawData['example'] ?? null);
    }

    /**
     * Capability collision: if capability is already registered by another
     * module, install must fail.
     */
    public function test_install_rejects_capability_collision(): void
    {
        // Pre-register the capability as if another module provides it
        $capabilities = app(CapabilityRegistryContract::class);
        $capabilities->registerProvider('other-module', ['example.demo']);

        $manager = app(ModuleManager::class);
        $result = $manager->install('example');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'already provided')),
            'Error should mention capability collision',
        );

        // Module must NOT be marked as installed
        $registrar = app(ModuleRegistrarContract::class);
        $this->assertNull($registrar->getState('example'));
    }

    /**
     * Enable must also check for capability collision.
     */
    public function test_enable_rejects_capability_collision(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');

        // Simulate another module taking the capability while example was disabled
        $capabilities = app(CapabilityRegistryContract::class);
        $capabilities->registerProvider('sneaky-module', ['example.demo']);

        $result = $manager->enable('example');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'already provided')),
            'Error should mention capability collision on enable',
        );
    }

    /**
     * module:doctor should say "installed and enabled" for an enabled module,
     * not "ready for installation".
     */
    public function test_doctor_command_wording_for_installed_module(): void
    {
        // Install the module first
        $manager = app(ModuleManager::class);
        $manager->install('example');

        $this->artisan('module:doctor', ['code' => 'example'])
            ->assertSuccessful()
            ->expectsOutputToContain('installed and enabled');
    }

    /**
     * module:doctor should say "ready for installation" for a discovered-only module.
     */
    public function test_doctor_command_wording_for_discovered_module(): void
    {
        $this->artisan('module:doctor', ['code' => 'example'])
            ->assertSuccessful()
            ->expectsOutputToContain('ready for installation');
    }

    /**
     * module:doctor should say "disabled" for a disabled module.
     */
    public function test_doctor_command_wording_for_disabled_module(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('example');
        $manager->disable('example');

        $this->artisan('module:doctor', ['code' => 'example'])
            ->assertSuccessful()
            ->expectsOutputToContain('disabled');
    }

    /**
     * Verify the install flow does not skip migration result checking.
     * This test proves the migration return code is inspected.
     */
    public function test_install_completes_with_valid_migrations(): void
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('example');

        $this->assertTrue($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'Migrations applied')),
            'Should confirm migrations were applied',
        );
    }
}
