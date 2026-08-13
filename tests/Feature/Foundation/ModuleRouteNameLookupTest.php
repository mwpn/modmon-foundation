<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression: module routes loaded post-boot must refresh Laravel's
 * route name look-up table so route() / Route::has() resolve them.
 *
 * The fix in ModuleManager is generic — no module-specific knowledge.
 */
class ModuleRouteNameLookupTest extends TestCase
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

    public function test_post_boot_module_named_routes_resolve_immediately(): void
    {
        $this->assertFalse(
            Route::has('example.index'),
            'example.index must not exist before the module is installed',
        );

        $result = app(ModuleManager::class)->install('example');
        $this->assertTrue($result['success'], implode(' | ', $result['messages']));

        $this->assertTrue(Route::has('example.index'));
        $this->assertTrue(Route::has('example.about'));
        $this->assertSame(url('/example'), route('example.index'));
        $this->assertSame(url('/example/about'), route('example.about'));
    }

    public function test_host_route_names_remain_intact_after_module_route_load(): void
    {
        // Host registers an arbitrary named route before module install.
        Route::get('/host-probe', fn () => 'ok')->name('host.probe');
        Route::getRoutes()->refreshNameLookups();

        $this->assertTrue(Route::has('host.probe'));
        $hostUrl = route('host.probe');

        $result = app(ModuleManager::class)->install('example');
        $this->assertTrue($result['success'], implode(' | ', $result['messages']));

        $this->assertTrue(Route::has('host.probe'), 'Host route name must survive module route load');
        $this->assertSame($hostUrl, route('host.probe'));
        $this->assertTrue(Route::has('example.index'));
    }

    public function test_multiple_modules_loaded_post_boot_remain_resolvable(): void
    {
        $manager = app(ModuleManager::class);

        $example = $manager->install('example');
        $this->assertTrue($example['success'], implode(' | ', $example['messages']));

        $identity = $manager->install('identity');
        $this->assertTrue($identity['success'], implode(' | ', $identity['messages']));

        $this->assertTrue(Route::has('example.index'));
        $this->assertTrue(Route::has('example.about'));
        $this->assertTrue(Route::has('identity.login'));
        $this->assertTrue(Route::has('identity.logout'));
        $this->assertTrue(Route::has('identity.password.reset'));

        $this->assertSame(url('/login'), route('identity.login'));
        $this->assertSame(url('/example'), route('example.index'));
    }

    public function test_re_enable_keeps_named_routes_resolvable(): void
    {
        $manager = app(ModuleManager::class);

        $this->assertTrue($manager->install('example')['success']);
        $this->assertTrue(Route::has('example.index'));

        $this->assertTrue($manager->disable('example')['success']);
        // Mid-process disable does not unload already-registered routes;
        // name look-ups must still remain coherent (not corrupted).
        $this->assertTrue(Route::has('example.index'));
        $this->assertSame(url('/example'), route('example.index'));

        $this->assertTrue($manager->enable('example')['success']);
        $this->assertTrue(Route::has('example.index'));
        $this->assertTrue(Route::has('example.about'));
        $this->assertSame(url('/example'), route('example.index'));
    }

    public function test_module_manager_has_no_identity_specific_route_knowledge(): void
    {
        $source = file_get_contents(base_path('app/Foundation/Runtime/ModuleManager.php'));

        $this->assertStringNotContainsString('identity.login', $source);
        $this->assertStringNotContainsString('identity.logout', $source);
        $this->assertStringNotContainsString('Modules\\Identity', $source);
        $this->assertStringContainsString('refreshNameLookups', $source);
    }
}
