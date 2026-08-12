<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use Tests\TestCase;

class ArtisanCommandsTest extends TestCase
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

    public function test_module_list_command(): void
    {
        $this->artisan('module:list')
            ->assertSuccessful()
            ->expectsTable(
                ['Name', 'Code', 'Version', 'Type', 'State', 'Provides', 'Requires'],
                [
                    ['Example', 'example', '1.0.0', 'business', 'discovered', 'example.demo', '-'],
                    ['Identity', 'identity', '1.0.0', 'platform', 'discovered', 'identity.user, identity.authentication', '-'],
                ],
            );
    }

    public function test_module_doctor_command(): void
    {
        $this->artisan('module:doctor', ['code' => 'example'])
            ->assertSuccessful();
    }

    public function test_module_doctor_nonexistent(): void
    {
        $this->artisan('module:doctor', ['code' => 'nonexistent'])
            ->assertFailed();
    }

    public function test_module_install_command(): void
    {
        $this->artisan('module:install', ['code' => 'example'])
            ->assertSuccessful();

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertTrue($registrar->isEnabled('example'));
    }

    public function test_module_disable_command(): void
    {
        $this->artisan('module:install', ['code' => 'example']);
        $this->artisan('module:disable', ['code' => 'example'])
            ->assertSuccessful();

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertFalse($registrar->isEnabled('example'));
    }

    public function test_module_enable_command(): void
    {
        $this->artisan('module:install', ['code' => 'example']);
        $this->artisan('module:disable', ['code' => 'example']);
        $this->artisan('module:enable', ['code' => 'example'])
            ->assertSuccessful();

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertTrue($registrar->isEnabled('example'));
    }

    public function test_foundation_doctor_command(): void
    {
        $this->artisan('foundation:doctor')
            ->assertSuccessful();
    }
}
