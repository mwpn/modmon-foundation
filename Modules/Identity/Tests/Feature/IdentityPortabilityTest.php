<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Identity\Models\User;
use Tests\TestCase;

/**
 * Phase 6 — in-process portability invariants that mirror the clean-host
 * proof (proposal §20): discover → doctor → install (legacy adopt) →
 * configure/use → disable/enable, without unrelated host source edits.
 *
 * The strongest verification is the sibling clean-host run recorded in
 * docs/reports/identity-compliance-v1.md; these tests lock the same
 * behavioural contracts into the suite.
 */
class IdentityPortabilityTest extends TestCase
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

    public function test_module_is_discovered_and_doctor_passes_without_host_edits(): void
    {
        $this->assertTrue(
            File::isDirectory(base_path('Modules/Identity')),
            'Identity must be present as a copied module directory',
        );

        foreach (['bootstrap/app.php', 'routes/web.php', 'config/auth.php'] as $hostFile) {
            $contents = File::get(base_path($hostFile));
            $this->assertStringNotContainsString(
                'Modules\\Identity',
                $contents,
                "{$hostFile} must not hardcode Identity (no unrelated host source edits)",
            );
        }

        $exit = Artisan::call('module:doctor', ['code' => 'identity']);
        $output = Artisan::output();
        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('All checks passed', $output);
    }

    public function test_install_adopts_legacy_tables_and_enables_auth_without_env_mutation(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));

        $legacyId = User::query()->create([
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => 'legacy-secret-password',
        ])->id;

        $envBefore = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;

        $result = app(ModuleManager::class)->install('identity');

        $this->assertTrue($result['success'], implode(' | ', $result['messages']));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('identity'));

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertTrue($capabilities->has('identity.user'));
        $this->assertTrue($capabilities->has('identity.authentication'));

        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertTrue(Route::has('identity.login'));
        $this->assertTrue(Route::has('identity.password.reset'));

        $this->assertDatabaseHas('users', [
            'id' => $legacyId,
            'email' => 'legacy@example.com',
            'name' => 'Legacy User',
        ]);

        if ($envBefore !== null) {
            $this->assertSame($envBefore, file_get_contents($this->envPath));
        }
    }

    public function test_user_create_login_and_lifecycle_work_after_portable_install(): void
    {
        $manager = app(ModuleManager::class);
        $this->assertTrue($manager->install('identity')['success']);

        $this->artisan('identity:user:create', [
            '--name' => 'Portable Owner',
            '--email' => 'portable@example.com',
            '--password' => 'secret-password',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'portable@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret-password', $user->password));

        $this->post(route('identity.login.submit'), [
            'email' => 'portable@example.com',
            'password' => 'secret-password',
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->assertTrue($manager->disable('identity')['success']);
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('identity.user'));
        $this->assertDatabaseHas('users', ['email' => 'portable@example.com']);

        $this->assertTrue($manager->enable('identity')['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('identity.authentication'));
        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertTrue(Route::has('identity.login'));
    }
}
