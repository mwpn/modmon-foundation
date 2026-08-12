<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Identity\Infrastructure\Queries\EloquentUserQuery;
use Modules\Identity\Models\User;
use Tests\TestCase;

/**
 * Install identity on a clean host: fresh tables created, capabilities
 * registered, module enabled, auth provider wired at runtime, .env
 * untouched.
 */
class IdentityInstallTest extends TestCase
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

        // Fresh-host scenario: simulate a Foundation 2.x host with no
        // user scaffolding — drop the host-created auth tables so the
        // Identity migration has to create them.
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');

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

    public function test_install_creates_both_tables(): void
    {
        $manager = app(ModuleManager::class);
        $result = $manager->install('identity');

        $this->assertTrue($result['success'], implode(', ', $result['messages']));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
    }

    public function test_install_marks_enabled_and_registers_capabilities(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('identity');

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertEquals(ModuleState::Enabled, $registrar->getState('identity'));

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertTrue($capabilities->has('identity.user'));
        $this->assertTrue($capabilities->has('identity.authentication'));
    }

    public function test_auth_provider_resolves_to_identity_user(): void
    {
        app(ModuleManager::class)->install('identity');

        $this->assertSame(
            User::class,
            config('auth.providers.users.model'),
            'Auth provider must resolve to Identity User while enabled',
        );
    }

    public function test_install_leaves_env_byte_identical(): void
    {
        if ($this->envOriginal === '') {
            $this->markTestSkipped('.env not present in this environment.');
        }

        app(ModuleManager::class)->install('identity');

        $this->assertSame($this->envOriginal, file_get_contents($this->envPath));
    }

    public function test_migrations_are_recorded_in_migrations_table(): void
    {
        app(ModuleManager::class)->install('identity');

        $this->assertTrue(
            Schema::hasTable('migrations'),
            'Laravel migration tracking table must exist',
        );

        $migrations = DB::table('migrations')->pluck('migration');
        $this->assertTrue(
            $migrations->contains(fn ($m) => str_contains($m, 'identity_users_tables')),
            'Identity migration must be tracked by Laravel',
        );
    }

    public function test_install_does_not_touch_sessions_table(): void
    {
        app(ModuleManager::class)->install('identity');

        $this->assertTrue(Schema::hasTable('sessions'));
        $columns = Schema::getColumnListing('sessions');
        $this->assertContains('id', $columns);
        $this->assertContains('user_id', $columns);
        $this->assertContains('payload', $columns);
        $this->assertContains('last_activity', $columns);
    }

    public function test_user_query_contract_is_bound_after_install(): void
    {
        app(ModuleManager::class)->install('identity');

        $query = app(UserQueryContract::class);

        $this->assertInstanceOf(EloquentUserQuery::class, $query);
    }

    public function test_install_is_idempotent_guard_against_second_install(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('identity');

        $result = $manager->install('identity');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'already installed')),
        );
    }

    public function test_fresh_tables_preserved_across_disable(): void
    {
        $manager = app(ModuleManager::class);
        $manager->install('identity');
        $manager->disable('identity');

        $this->assertTrue(Schema::hasTable('users'), 'users must survive disable');
        $this->assertTrue(Schema::hasTable('password_reset_tokens'), 'password_reset_tokens must survive disable');

        $registrar = app(ModuleRegistrarContract::class);
        $this->assertEquals(ModuleState::Disabled, $registrar->getState('identity'));
    }

    public function test_fresh_creation_preserves_schema(): void
    {
        app(ModuleManager::class)->install('identity');

        $this->assertSame(
            ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'],
            Schema::getColumnListing('users'),
        );
        $this->assertSame(
            ['email', 'token', 'created_at'],
            Schema::getColumnListing('password_reset_tokens'),
        );
    }
}
