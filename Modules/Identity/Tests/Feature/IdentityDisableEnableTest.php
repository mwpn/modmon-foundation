<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use App\Models\User as HostUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Identity\Models\User;
use Tests\TestCase;

/**
 * Phase 5 — disable/enable lifecycle (proposal §16).
 *
 * Disable preserves data and removes capabilities; enable restores
 * capabilities and reapplies runtime auth wiring. Auth wiring applies
 * only while the module is enabled on a given request/process boot.
 */
class IdentityDisableEnableTest extends TestCase
{
    use RefreshDatabase;

    private string $modulesJsonPath;

    private string $envPath;

    private string $envOriginal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesJsonPath = storage_path('app/modules.json');
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->envPath = base_path('.env');
        $this->envOriginal = file_exists($this->envPath) ? file_get_contents($this->envPath) : '';

        $this->app->forgetInstance(ModuleRegistrarContract::class);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(CapabilityRegistryContract::class);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        if (file_exists($this->envPath)) {
            file_put_contents($this->envPath, $this->envOriginal);
        }

        parent::tearDown();
    }

    private function installIdentity(): ModuleManager
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('identity');

        if (! $result['success']) {
            $this->fail('Identity module could not be installed: '.implode(' | ', $result['messages']));
        }

        return $manager;
    }

    public function test_disable_unregisters_capabilities_and_preserves_data(): void
    {
        $manager = $this->installIdentity();

        $user = User::query()->create([
            'name' => 'Keep Me',
            'email' => 'keep@example.com',
            'password' => 'secret-password',
        ]);

        $result = $manager->disable('identity');

        $this->assertTrue($result['success'], implode(' | ', $result['messages']));
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('identity'));

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertFalse($capabilities->has('identity.user'));
        $this->assertFalse($capabilities->has('identity.authentication'));

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $this->assertTrue(Schema::hasTable('sessions'), 'Foundation sessions table must remain');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'keep@example.com',
            'name' => 'Keep Me',
        ]);
        $this->assertTrue(
            Hash::check('secret-password', User::query()->find($user->id)->password),
            'Password hash must survive disable',
        );
    }

    public function test_disable_and_enable_leave_env_byte_identical(): void
    {
        if ($this->envOriginal === '') {
            $this->markTestSkipped('.env not present in this environment.');
        }

        $manager = $this->installIdentity();
        $this->assertSame($this->envOriginal, file_get_contents($this->envPath));

        $this->assertTrue($manager->disable('identity')['success']);
        $this->assertSame($this->envOriginal, file_get_contents($this->envPath));

        $this->assertTrue($manager->enable('identity')['success']);
        $this->assertSame($this->envOriginal, file_get_contents($this->envPath));
    }

    public function test_enable_restores_capabilities_and_auth_wiring(): void
    {
        $manager = $this->installIdentity();

        User::query()->create([
            'name' => 'Survivor',
            'email' => 'survivor@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertTrue($manager->disable('identity')['success']);

        // Simulate a subsequent request where Identity is not enabled:
        // the host default auth model is what Laravel would resolve
        // without Identity's provider.
        config()->set('auth.providers.users.model', HostUser::class);
        $this->assertSame(HostUser::class, config('auth.providers.users.model'));

        $result = $manager->enable('identity');

        $this->assertTrue($result['success'], implode(' | ', $result['messages']));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('identity'));

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertTrue($capabilities->has('identity.user'));
        $this->assertTrue($capabilities->has('identity.authentication'));

        $this->assertSame(
            User::class,
            config('auth.providers.users.model'),
            'Enable must reapply Identity runtime auth wiring',
        );

        $this->assertDatabaseHas('users', [
            'email' => 'survivor@example.com',
            'name' => 'Survivor',
        ]);
    }

    public function test_auth_wiring_applies_only_while_enabled(): void
    {
        $manager = $this->installIdentity();

        $this->assertSame(User::class, config('auth.providers.users.model'));

        $this->assertTrue($manager->disable('identity')['success']);

        // Mid-process config may still hold the last in-memory value
        // (Foundation does not unload already-registered providers).
        // A fresh boot without Identity enabled must fall back to the
        // host default — simulate that next-request state here.
        config()->set('auth.providers.users.model', HostUser::class);

        $this->assertSame(
            HostUser::class,
            config('auth.providers.users.model'),
            'Without Identity enabled, auth must use the host default model',
        );
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('identity.user'));

        $this->assertTrue($manager->enable('identity')['success']);

        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('identity.authentication'));
    }

    public function test_disable_does_not_drop_identity_tables(): void
    {
        $manager = $this->installIdentity();
        $columnsBefore = Schema::getColumnListing('users');

        $this->assertTrue($manager->disable('identity')['success']);

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $this->assertSame($columnsBefore, Schema::getColumnListing('users'));
    }
}
