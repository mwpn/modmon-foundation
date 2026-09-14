<?php

declare(strict_types=1);

namespace Modules\Branding\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class BrandingTestCase extends TestCase
{
    use RefreshDatabase;

    private string $modulesJsonPath;

    protected function setUp(): void
    {
        $this->modulesJsonPath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR
            .'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'modules.json';
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::setUp();

        $this->modulesJsonPath = storage_path('app/modules.json');
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->app->forgetInstance(ModuleRegistrarContract::class);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(CapabilityRegistryContract::class);
        $this->app->forgetInstance(NavigationRegistryContract::class);
        $this->app->forgetInstance(PermissionRegistryContract::class);
        $this->app->forgetInstance(WorkspaceRegistryContract::class);

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::tearDown();
    }

    protected function installBranding(): ModuleManager
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('branding');

        if (! $result['success']) {
            $this->fail('Branding could not be installed: '.implode(' | ', $result['messages']));
        }

        return $manager;
    }
}
