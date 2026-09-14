<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Identity\Models\User;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\Tenancy\Domain\DTOs\TenantRead;
use Tests\TestCase;

abstract class TenancyBrandingTestCase extends TestCase
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

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
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

    protected function installDependencies(): ModuleManager
    {
        $this->installModule('identity');
        $this->installModule('branding');
        $this->installModule('tenancy');

        return app(ModuleManager::class);
    }

    protected function installStack(): ModuleManager
    {
        $manager = $this->installDependencies();
        $result = $manager->install('tenancy-branding');

        if (! $result['success']) {
            $this->fail('TenancyBranding could not be installed: '.implode(' | ', $result['messages']));
        }

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

    protected function bindUserToTenant(int $userId, int $tenantId): void
    {
        app(MembershipContract::class)->add($userId, $tenantId);
        app(TenantContextContract::class)->setCurrent($userId, $tenantId);
    }
}
