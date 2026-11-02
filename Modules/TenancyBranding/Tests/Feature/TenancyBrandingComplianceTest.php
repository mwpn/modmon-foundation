<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingData;
use Modules\TenancyBranding\Application\Services\TenantBrandingService;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideData;

/**
 * Phase 1 — portability / compliance proof on the authoring host stack.
 */
class TenancyBrandingComplianceTest extends TenancyBrandingTestCase
{
    /** @var list<string> */
    private array $watchedHostFiles = [
        'bootstrap/app.php',
        'routes/web.php',
        'config/app.php',
        'config/auth.php',
        'composer.json',
        'composer.lock',
        'app/Foundation/FoundationServiceProvider.php',
        'app/Foundation/Runtime/ModuleManager.php',
    ];

    public function test_portability_lifecycle_merge_and_invariants(): void
    {
        $hashesBefore = $this->hashWatchedHostFiles();

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenancy-branding'));
        $this->assertFalse(Schema::hasTable('tenancy_branding_overrides'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.tenant'));

        $this->artisan('module:doctor', ['code' => 'tenancy-branding'])->assertFailed();
        $this->assertFalse(Schema::hasTable('tenancy_branding_overrides'));

        $this->installDependencies();
        $this->artisan('module:doctor', ['code' => 'tenancy-branding'])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('tenancy_branding_overrides'));

        $manager = app(ModuleManager::class);
        $install = $manager->install('tenancy-branding');
        $this->assertTrue($install['success'], implode(' | ', $install['messages']));
        $this->assertTrue(
            collect($install['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migrations applied')),
        );

        $this->assertTrue(Schema::hasTable('tenancy_branding_overrides'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.tenant'));
        $this->assertInstanceOf(TenantBrandingService::class, app(TenantBrandingContract::class));

        app(BrandingContract::class)->update(new BrandingData(
            name: 'App Brand',
            primaryColor: '#111111',
        ));

        $tenant = $this->createActiveTenant('comp', 'Compliance Co');
        $user = $this->createUser('comp@example.com');

        $inherited = app(TenantBrandingContract::class)->forTenant($tenant->id);
        $this->assertSame('App Brand', $inherited->name);
        $this->assertSame('#111111', $inherited->primaryColor);

        $withoutContext = app(TenantBrandingContract::class)->forCurrentUser($user->id);
        $this->assertSame('App Brand', $withoutContext->name);

        $this->put('/tenancy-branding/tenants/'.$tenant->id, [
            'name' => 'Tenant Brand',
            'primary_color' => '#ABCDEF',
            'logo' => UploadedFile::fake()->image('logo.png', 24, 24),
        ])->assertRedirect();

        $effective = app(TenantBrandingContract::class)->forTenant($tenant->id);
        $this->assertSame('Tenant Brand', $effective->name);
        $this->assertSame('#ABCDEF', $effective->primaryColor);
        $this->assertNotNull($effective->logoUrl);
        $this->assertSame('App Brand', app(BrandingContract::class)->current()->name);
        $this->assertTrue(Storage::disk('public')->exists('tenancy-branding/'.$tenant->id.'/logo.png'));

        $this->bindUserToTenant($user->id, $tenant->id);
        $this->assertSame('Tenant Brand', app(TenantBrandingContract::class)->forCurrentUser($user->id)->name);

        $this->get('/tenancy-branding')->assertOk();
        $this->assertSame(['tenancy-branding.manage'], array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('tenancy-branding'),
        ));

        $disable = $manager->disable('tenancy-branding');
        $this->assertTrue($disable['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenancy-branding'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.tenant'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenancy-branding'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenancy-branding'),
        );
        $this->assertDatabaseHas('tenancy_branding_overrides', [
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Brand',
        ]);
        $this->assertTrue(Storage::disk('public')->exists('tenancy-branding/'.$tenant->id.'/logo.png'));

        $enable = $manager->enable('tenancy-branding');
        $this->assertTrue($enable['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.tenant'));
        $this->assertSame('Tenant Brand', app(TenantBrandingContract::class)->forTenant($tenant->id)->name);
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('tenancy-branding'));

        $this->assertSame($hashesBefore, $this->hashWatchedHostFiles());
    }

    public function test_migration_failure_is_fail_closed(): void
    {
        $this->installDependencies();

        Schema::create('tenancy_branding_overrides', function ($table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
        });

        $hashesBefore = $this->hashWatchedHostFiles();
        $result = app(ModuleManager::class)->install('tenancy-branding');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migration failed')),
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'NOT installed')),
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenancy-branding'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.tenant'));
        $this->assertFalse(app()->bound(TenantBrandingContract::class));
        $this->assertSame($hashesBefore, $this->hashWatchedHostFiles());
    }

    /**
     * @return array<string, string>
     */
    private function hashWatchedHostFiles(): array
    {
        $hashes = [];
        foreach ($this->watchedHostFiles as $relative) {
            $path = base_path($relative);
            $this->assertFileExists($path);
            $hashes[$relative] = hash_file('sha256', $path);
        }

        return $hashes;
    }
}
