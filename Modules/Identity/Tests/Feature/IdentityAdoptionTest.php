<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Identity\Models\User;
use Tests\TestCase;

/**
 * Foundation 1.x legacy adoption: users/password_reset_tokens already
 * exist with host-created schema and data. Identity must adopt them
 * without schema or data changes.
 */
class IdentityAdoptionTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }
        parent::tearDown();
    }

    /**
     * Seed legacy user data into the host-created tables (which exist
     * after RefreshDatabase ran the host migrations).
     */
    private function seedLegacyData(): void
    {
        DB::table('users')->insert([
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'legacy@example.com',
            'token' => 'legacy-token',
            'created_at' => now(),
        ]);
    }

    public function test_install_adopts_compatible_legacy_tables(): void
    {
        $this->seedLegacyData();

        $manager = app(ModuleManager::class);
        $result = $manager->install('identity');

        $this->assertTrue($result['success'], implode(', ', $result['messages']));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('identity'));
    }

    public function test_adoption_preserves_existing_user_data(): void
    {
        $this->seedLegacyData();

        app(ModuleManager::class)->install('identity');

        $user = DB::table('users')->where('email', 'legacy@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Legacy User', $user->name);
        $this->assertTrue(password_verify('secret', $user->password), 'Password hash must be preserved');
        $this->assertTrue(
            DB::table('password_reset_tokens')->where('email', 'legacy@example.com')->exists(),
        );
    }

    public function test_adoption_does_not_recreate_tables(): void
    {
        $this->seedLegacyData();

        app(ModuleManager::class)->install('identity');

        // Original row survives — a recreated table would be empty
        $this->assertSame(1, DB::table('users')->count());
    }

    public function test_legacy_user_authenticates_against_identity_model(): void
    {
        $this->seedLegacyData();

        app(ModuleManager::class)->install('identity');

        $this->assertSame(User::class, config('auth.providers.users.model'));

        $this->assertTrue(
            Auth::validate(['email' => 'legacy@example.com', 'password' => 'secret']),
        );
    }

    public function test_adoption_does_not_touch_sessions(): void
    {
        $this->seedLegacyData();

        app(ModuleManager::class)->install('identity');

        $this->assertTrue(Schema::hasTable('sessions'));
    }

    public function test_adoption_records_migration_exactly_once(): void
    {
        $this->seedLegacyData();

        app(ModuleManager::class)->install('identity');

        $count = DB::table('migrations')
            ->where('migration', 'like', '%identity_users_tables%')
            ->count();

        $this->assertSame(1, $count, 'Identity migration must be recorded exactly once');
    }

    public function test_adoption_subsequent_migration_runs_are_noops(): void
    {
        $this->seedLegacyData();

        app(ModuleManager::class)->install('identity');

        // Re-run migrate on the module path: Laravel tracking makes it a no-op
        $exitCode = Artisan::call('migrate', [
            '--path' => realpath(base_path('Modules/Identity/Database/Migrations')),
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringNotContainsString('identity_users_tables', Artisan::output());
        $this->assertSame(1, DB::table('users')->count(), 'Data must survive a re-run');
        $this->assertSame(
            ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'],
            Schema::getColumnListing('users'),
            'Schema must be unchanged by a re-run',
        );
    }

    public function test_adoption_preserves_table_schema(): void
    {
        $this->seedLegacyData();

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
