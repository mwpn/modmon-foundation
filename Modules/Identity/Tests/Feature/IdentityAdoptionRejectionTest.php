<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Adoption must be strict: incompatible schema or partial table state
 * aborts install with a clear diagnostic and leaves data untouched.
 */
class IdentityAdoptionRejectionTest extends TestCase
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
     * Rebuild users with an incompatible schema (missing password,
     * remember_token, timestamps).
     */
    private function rebuildUsersWithIncompatibleSchema(): void
    {
        Schema::dropIfExists('users');
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
        });
    }

    public function test_install_aborts_on_incompatible_schema(): void
    {
        $this->rebuildUsersWithIncompatibleSchema();

        $manager = app(ModuleManager::class);
        $result = $manager->install('identity');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'Migration failed')),
            'Install must report migration failure',
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'Schema incompatibilities')),
            'Diagnostic must name the schema incompatibilities',
        );

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('identity'));
    }

    public function test_incompatible_schema_does_not_record_migration(): void
    {
        $this->rebuildUsersWithIncompatibleSchema();

        app(ModuleManager::class)->install('identity');

        $this->assertFalse(
            DB::table('migrations')->where('migration', 'like', '%identity_users_tables%')->exists(),
            'Laravel must not record the Identity migration when adoption fails',
        );
    }

    public function test_incompatible_schema_does_not_register_capabilities(): void
    {
        $this->rebuildUsersWithIncompatibleSchema();

        app(ModuleManager::class)->install('identity');

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertFalse($capabilities->has('identity.user'));
        $this->assertFalse($capabilities->has('identity.authentication'));
    }

    public function test_incompatible_schema_leaves_existing_schema_unchanged(): void
    {
        $this->rebuildUsersWithIncompatibleSchema();

        app(ModuleManager::class)->install('identity');

        // Schema must remain exactly as the host had it: users still has
        // only the original columns and password_reset_tokens untouched.
        $columns = Schema::getColumnListing('users');
        $this->assertSame(['id', 'name', 'email'], $columns);
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
    }

    public function test_install_aborts_when_only_users_exists(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        $manager = app(ModuleManager::class);
        $result = $manager->install('identity');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'partial table state')),
            'Diagnostic must describe the partial table state',
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('identity'));
    }

    public function test_partial_state_does_not_record_migration_or_capabilities(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        app(ModuleManager::class)->install('identity');

        $this->assertFalse(
            DB::table('migrations')->where('migration', 'like', '%identity_users_tables%')->exists(),
            'Laravel must not record the Identity migration when adoption is blocked',
        );

        $capabilities = app(CapabilityRegistryContract::class);
        $this->assertFalse($capabilities->has('identity.user'));
        $this->assertFalse($capabilities->has('identity.authentication'));

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('identity'));
    }

    public function test_install_aborts_when_only_password_reset_tokens_exists(): void
    {
        Schema::dropIfExists('users');

        $manager = app(ModuleManager::class);
        $result = $manager->install('identity');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains($m, 'partial table state')),
            'Diagnostic must describe the partial table state',
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('identity'));
    }

    public function test_partial_state_leaves_existing_data_untouched(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        DB::table('users')->insert([
            'name' => 'Partial Host',
            'email' => 'partial@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ModuleManager::class)->install('identity');

        $this->assertSame(1, DB::table('users')->count(), 'Data must remain untouched after failed adoption');
    }

    public function test_incompatible_schema_leaves_existing_data_untouched(): void
    {
        // Incompatible users table but with a data row
        Schema::dropIfExists('users');
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
        });
        DB::table('users')->insert([
            'name' => 'Mismatch Host',
            'email' => 'mismatch@example.com',
        ]);

        app(ModuleManager::class)->install('identity');

        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame('Mismatch Host', DB::table('users')->value('name'));
    }
}
