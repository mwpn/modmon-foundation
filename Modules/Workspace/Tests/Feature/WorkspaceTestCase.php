<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\DTOs\TenantRead;
use Modules\Workspace\Application\Services\ContextActiveWorkspace;
use Tests\TestCase;

/**
 * Shared Workspace feature setup with host modules.json isolation.
 */
abstract class WorkspaceTestCase extends TestCase
{
    use RefreshDatabase;

    private string $modulesJsonPath;

    private static bool $hostModulesJsonCaptured = false;

    private static bool $hostModulesJsonExisted = false;

    private static ?string $hostModulesJsonBackup = null;

    protected static ?string $bootModulesJson = null;

    protected function setUp(): void
    {
        $bootingFromSnapshot = static::$bootModulesJson !== null;

        $this->modulesJsonPath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR
            .'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'modules.json';

        $this->captureHostModulesJson($this->modulesJsonPath);

        if ($bootingFromSnapshot) {
            $dir = dirname($this->modulesJsonPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($this->modulesJsonPath, static::$bootModulesJson);
        } elseif (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::setUp();

        $this->modulesJsonPath = storage_path('app/modules.json');
        $this->captureHostModulesJson($this->modulesJsonPath);

        if ($bootingFromSnapshot) {
            file_put_contents($this->modulesJsonPath, (string) static::$bootModulesJson);
            static::$bootModulesJson = null;

            return;
        }

        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->forgetModuleSingletons();
    }

    protected function tearDown(): void
    {
        static::$bootModulesJson = null;
        $this->restoreHostModulesJson(storage_path('app/modules.json'));

        $fallback = dirname(__DIR__, 4).DIRECTORY_SEPARATOR
            .'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'modules.json';
        if ($fallback !== storage_path('app/modules.json')) {
            $this->restoreHostModulesJson($fallback);
        }

        parent::tearDown();
    }

    protected function forgetModuleSingletons(): void
    {
        $this->app->forgetInstance(ModuleRegistrarContract::class);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(CapabilityRegistryContract::class);
        $this->app->forgetInstance(NavigationRegistryContract::class);
        $this->app->forgetInstance(PermissionRegistryContract::class);
        $this->app->forgetInstance(WorkspaceRegistryContract::class);
        $this->app->forgetInstance(ActiveWorkspaceContract::class);
        $this->app->forgetInstance(ContextActiveWorkspace::class);
    }

    protected function installStack(): ModuleManager
    {
        $manager = app(ModuleManager::class);

        foreach (['identity', 'tenancy', 'workspace'] as $code) {
            $result = $manager->install($code);
            if (! $result['success']) {
                $this->fail("Module '{$code}' could not be installed: ".implode(' | ', $result['messages']));
            }
        }

        $this->app->forgetInstance(ActiveWorkspaceContract::class);
        $this->app->forgetInstance(ContextActiveWorkspace::class);

        return $manager;
    }

    protected function createUser(string $email = 'user@example.com'): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }

    protected function createActiveTenant(string $code = 'acme', string $name = 'Acme'): TenantRead
    {
        return app(TenantContract::class)->create($code, $name);
    }

    protected function assignTenantContext(User $user, TenantRead $tenant): void
    {
        app(MembershipContract::class)->add($user->id, $tenant->id);
        app(TenantContextContract::class)->setCurrent($user->id, $tenant->id);
    }

    private function captureHostModulesJson(string $path): void
    {
        if (self::$hostModulesJsonCaptured) {
            return;
        }

        self::$hostModulesJsonCaptured = true;
        self::$hostModulesJsonExisted = file_exists($path);
        self::$hostModulesJsonBackup = self::$hostModulesJsonExisted
            ? (string) file_get_contents($path)
            : null;
    }

    private function restoreHostModulesJson(string $path): void
    {
        if (! self::$hostModulesJsonCaptured) {
            return;
        }

        if (self::$hostModulesJsonExisted) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($path, (string) self::$hostModulesJsonBackup);

            return;
        }

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
