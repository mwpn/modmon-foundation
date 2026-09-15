<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\DTOs\TenantRead;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Tests\TestCase;

abstract class TenantLandingTestCase extends TestCase
{
    use RefreshDatabase;

    private string $modulesJsonPath;

    /**
     * When set, the next application boot loads this modules.json snapshot
     * (used to prove fresh boot while Landing is disabled).
     */
    protected static ?string $bootModulesJson = null;

    protected function setUp(): void
    {
        $bootingFromSnapshot = static::$bootModulesJson !== null;

        $this->modulesJsonPath = dirname(__DIR__, 4).DIRECTORY_SEPARATOR
            .'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'modules.json';

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

        if ($bootingFromSnapshot) {
            file_put_contents($this->modulesJsonPath, (string) static::$bootModulesJson);
            static::$bootModulesJson = null;

            return;
        }

        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->app->forgetInstance(ModuleRegistrarContract::class);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(CapabilityRegistryContract::class);
        $this->app->forgetInstance(NavigationRegistryContract::class);
        $this->app->forgetInstance(PermissionRegistryContract::class);
    }

    protected function tearDown(): void
    {
        static::$bootModulesJson = null;

        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $fallback = dirname(__DIR__, 4).DIRECTORY_SEPARATOR
            .'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'modules.json';
        if (file_exists($fallback)) {
            unlink($fallback);
        }

        parent::tearDown();
    }

    protected function installModule(string $code): ModuleManager
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install($code);
        if (! $result['success']) {
            $this->fail("Module '{$code}' could not be installed: ".implode(' | ', $result['messages']));
        }

        return $manager;
    }

    protected function installDomainStack(): ModuleManager
    {
        $this->installModule('identity');
        $this->installModule('tenancy');
        $this->installModule('tenant-domains');

        return app(ModuleManager::class);
    }

    protected function installLandingStack(): ModuleManager
    {
        $manager = $this->installDomainStack();
        $result = $manager->install('tenant-landing');
        if (! $result['success']) {
            $this->fail('TenantLanding could not be installed: '.implode(' | ', $result['messages']));
        }

        return $manager;
    }

    protected function createActiveTenant(string $code = 'acme', string $name = 'Acme'): TenantRead
    {
        return app(TenantContract::class)->create($code, $name);
    }

    protected function attachDomain(int $tenantId, string $hostname, bool $primary = true): void
    {
        app(TenantDomainContract::class)->attach($tenantId, $hostname, $primary);
    }

    protected function createUser(string $email = 'user@example.com'): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }
}
