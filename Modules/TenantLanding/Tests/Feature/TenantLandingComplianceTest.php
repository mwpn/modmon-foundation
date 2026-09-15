<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\TenantLanding\Application\Services\TenantLandingService;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Modules\TenantLanding\Domain\DTOs\TenantLandingData;

/**
 * Phase 1 — portability / compliance proof.
 */
class TenantLandingComplianceTest extends TenantLandingTestCase
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

    public function test_portability_lifecycle_and_host_sources_unchanged(): void
    {
        $hashesBefore = $this->hashWatchedHostFiles();

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenant-landing'));
        $this->assertFalse(Schema::hasTable('tenant_landing'));

        $this->artisan('module:doctor', ['code' => 'tenant-landing'])->assertFailed();
        $this->assertFalse(Schema::hasTable('tenant_landing'));

        $this->installDomainStack();
        $this->artisan('module:doctor', ['code' => 'tenant-landing'])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('tenant_landing'));

        $manager = app(ModuleManager::class);
        $install = $manager->install('tenant-landing');
        $this->assertTrue($install['success'], implode(' | ', $install['messages']));
        $this->assertTrue(
            collect($install['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migrations applied')),
        );
        $this->assertTrue(Schema::hasTable('tenant_landing'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $this->assertInstanceOf(TenantLandingService::class, app(TenantLandingContract::class));

        $tenant = $this->createActiveTenant('comp', 'Compliance Co');
        $this->attachDomain($tenant->id, 'comp.example.com');
        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            headline: 'Compliance Landing',
        ));

        $this->get('http://comp.example.com/')
            ->assertOk()
            ->assertSee('Compliance Landing', false);

        $this->get('/tenant-landing')->assertOk();

        $disable = $manager->disable('tenant-landing');
        $this->assertTrue($disable['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenant-landing'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $this->assertDatabaseHas('tenant_landing', ['headline' => 'Compliance Landing']);

        $enable = $manager->enable('tenant-landing');
        $this->assertTrue($enable['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $this->assertSame(
            'Compliance Landing',
            app(TenantLandingContract::class)->forTenant($tenant->id)->headline,
        );

        $this->assertSame($hashesBefore, $this->hashWatchedHostFiles());
    }

    public function test_migration_failure_is_fail_closed(): void
    {
        $this->installDomainStack();

        Schema::create('tenant_landing', function ($table) {
            $table->id();
            $table->string('headline')->nullable();
        });

        $hashesBefore = $this->hashWatchedHostFiles();
        $result = app(ModuleManager::class)->install('tenant-landing');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migration failed')),
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'NOT installed')),
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenant-landing'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $this->assertFalse(app()->bound(TenantLandingContract::class));
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
