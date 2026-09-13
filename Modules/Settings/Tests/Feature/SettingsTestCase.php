<?php

declare(strict_types=1);

namespace Modules\Settings\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared setup for Settings feature tests: clean modules.json and
 * fresh Foundation singletons so each test starts discovered-only.
 */
abstract class SettingsTestCase extends TestCase
{
    use RefreshDatabase;

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
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::tearDown();
    }

    protected function installSettings(): ModuleManager
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('settings');

        if (! $result['success']) {
            $this->fail('Settings module could not be installed: '.implode(' | ', $result['messages']));
        }

        return $manager;
    }
}
