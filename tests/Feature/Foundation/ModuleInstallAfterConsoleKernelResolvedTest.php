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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression: module:install must run owned migrations even after an
 * already-enabled module has resolved the console kernel during boot.
 *
 * Fresh-host sequence this models:
 *   migrate (baseline) → install Identity (registers a console command
 *   via Kernel::registerCommand) → doctor/install a second module with
 *   migrations (RBAC). Nested Artisan::call('migrate') fails with
 *   "The command migrate does not exist" because MigrationServiceProvider
 *   is deferred. Install must use the Migrator API instead.
 */
class ModuleInstallAfterConsoleKernelResolvedTest extends TestCase
{
    private string $modulesJsonPath;

    /** @var string[] */
    private array $fixtureDirs = [];

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

        $this->writeEarlyConsoleModule();
        $this->writeFollowOnModule();
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtureDirs as $dir) {
            if (is_dir($dir)) {
                File::deleteDirectory($dir);
            }
        }

        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::tearDown();
    }

    public function test_second_module_migrations_run_after_enabled_module_resolved_console_kernel(): void
    {
        Schema::dropIfExists('follow_on_entries');

        $this->artisan('module:install', ['code' => 'early-console'])->assertSuccessful();
        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('early-console'),
        );

        $this->artisan('module:doctor', ['code' => 'follow-on'])->assertSuccessful();

        $result = app(ModuleManager::class)->install('follow-on');

        $this->assertTrue($result['success'], implode("\n", $result['messages']));
        $this->assertFalse(
            collect($result['messages'])->contains(
                fn ($message) => str_contains($message, 'The command "migrate" does not exist'),
            ),
        );
        $this->assertTrue(
            Schema::hasTable('follow_on_entries'),
            'follow-on owned migration must run during module:install',
        );
        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('follow-on'),
        );
    }

    public function test_failed_follow_on_migration_is_fail_closed(): void
    {
        Schema::create('follow_on_entries', function ($table) {
            $table->id();
        });

        $this->artisan('module:install', ['code' => 'early-console'])->assertSuccessful();

        $result = app(ModuleManager::class)->install('follow-on');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'Migration failed')),
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'Module was NOT installed')),
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('follow-on'));
    }

    private function writeEarlyConsoleModule(): void
    {
        $dir = base_path('Modules/EarlyConsole');
        $this->fixtureDirs[] = $dir;
        File::ensureDirectoryExists($dir);

        File::put($dir.'/module.json', json_encode([
            'schema' => 1,
            'name' => 'EarlyConsole',
            'code' => 'early-console',
            'version' => '1.0.0',
            'type' => 'platform',
            'provider' => 'Tests\\Fixtures\\ModuleLifecycle\\EarlyConsoleKernelServiceProvider',
            'compatibility' => [
                'php' => '^8.3',
                'laravel' => '^13.0',
                'foundation' => '^1.0',
            ],
            'requires' => ['capabilities' => []],
            'provides' => ['fixture.early-console'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function writeFollowOnModule(): void
    {
        $dir = base_path('Modules/FollowOn');
        $this->fixtureDirs[] = $dir;
        File::ensureDirectoryExists($dir.'/Database/Migrations');

        File::put($dir.'/module.json', json_encode([
            'schema' => 1,
            'name' => 'FollowOn',
            'code' => 'follow-on',
            'version' => '1.0.0',
            'type' => 'platform',
            'provider' => 'Tests\\Fixtures\\ModuleLifecycle\\FollowOnMigratingServiceProvider',
            'compatibility' => [
                'php' => '^8.3',
                'laravel' => '^13.0',
                'foundation' => '^1.0',
            ],
            'requires' => ['capabilities' => []],
            'provides' => ['fixture.follow-on'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put(
            $dir.'/Database/Migrations/2026_08_14_000001_create_follow_on_entries.php',
            <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_on_entries', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_on_entries');
    }
};
PHP
        );
    }
}
