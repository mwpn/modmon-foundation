<?php

declare(strict_types=1);

namespace Modules\Branding\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Branding\Application\Services\ApplicationBranding;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingData;

/**
 * Phase 1 — portability / compliance proof.
 *
 * discover → doctor → install → contract + HTTP + contributions →
 * disable/enable data preserve → host sources untouched.
 * No Identity/RBAC/Settings/Tenancy required.
 */
class BrandingComplianceTest extends BrandingTestCase
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
        'app/Foundation/Experience/views/layouts/app.blade.php',
    ];

    public function test_portability_lifecycle_and_invariants(): void
    {
        $hashesBefore = $this->hashWatchedHostFiles();

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('branding'));
        $this->assertFalse(Schema::hasTable('branding_application'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertFalse(
            app()->bound(BrandingContract::class),
            'BrandingContract must not be bound before Branding is installed/enabled',
        );

        $this->artisan('module:doctor', ['code' => 'branding'])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('branding_application'));

        $manager = app(ModuleManager::class);
        $install = $manager->install('branding');
        $this->assertTrue($install['success'], implode(' | ', $install['messages']));
        $this->assertTrue(
            collect($install['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migrations applied')),
            'Explicit install must own and report Branding migrations',
        );

        $this->assertTrue(Schema::hasTable('branding_application'));
        $this->assertDatabaseCount('branding_application', 0);
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('branding'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertInstanceOf(ApplicationBranding::class, app(BrandingContract::class));

        $defaults = app(BrandingContract::class)->current();
        $this->assertFalse($defaults->configured);
        $this->assertSame((string) config('app.name', 'ModMon'), $defaults->name);
        $this->assertDatabaseCount('branding_application', 0);

        $this->get('/branding')->assertOk();

        $this->put('/branding', [
            'name' => 'Compliance Brand',
            'primary_color' => '#123456',
            'login_title' => 'Hello',
            'logo' => UploadedFile::fake()->image('logo.png', 32, 32),
        ])->assertRedirect(route('branding.edit'));

        $read = app(BrandingContract::class)->current();
        $this->assertTrue($read->configured);
        $this->assertSame('Compliance Brand', $read->name);
        $this->assertSame('#123456', $read->primaryColor);
        $this->assertNotNull($read->logoUrl);
        $this->assertTrue(Storage::disk('public')->exists('branding/logo.png'));

        $permIds = array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('branding'),
        );
        $this->assertSame(['branding.manage'], $permIds);

        $nav = collect(app(NavigationRegistryContract::class)->items())
            ->first(fn ($i) => $i->id === 'branding.edit');
        $this->assertNotNull($nav);

        $this->put('/branding', [
            'name' => 'X',
            'primary_color' => 'bad',
        ])->assertSessionHasErrors('primary_color');
        $this->assertSame('Compliance Brand', app(BrandingContract::class)->current()->name);

        $disable = $manager->disable('branding');
        $this->assertTrue($disable['success'], implode(' | ', $disable['messages']));
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('branding'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('branding'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'branding'),
        );
        $this->assertDatabaseHas('branding_application', [
            'name' => 'Compliance Brand',
            'primary_color' => '#123456',
            'login_title' => 'Hello',
        ]);

        $enable = $manager->enable('branding');
        $this->assertTrue($enable['success'], implode(' | ', $enable['messages']));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.application'));
        $restored = app(BrandingContract::class)->current();
        $this->assertSame('Compliance Brand', $restored->name);
        $this->assertSame('#123456', $restored->primaryColor);
        $this->assertNotNull($restored->logoUrl);
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('branding'));

        // Contract still rejects empty name / foreign paths after re-enable.
        try {
            app(BrandingContract::class)->update(new BrandingData(name: '   '));
            $this->fail('Expected empty name rejection');
        } catch (\InvalidArgumentException) {
            //
        }

        $this->assertSame(
            $hashesBefore,
            $this->hashWatchedHostFiles(),
            'Branding lifecycle must not mutate watched host Foundation/source files',
        );
    }

    /**
     * @return array<string, string>
     */
    private function hashWatchedHostFiles(): array
    {
        $hashes = [];
        foreach ($this->watchedHostFiles as $relative) {
            $absolute = base_path($relative);
            $this->assertFileExists($absolute);
            $hashes[$relative] = hash_file('sha256', $absolute);
        }

        return $hashes;
    }
}
