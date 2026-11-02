<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenantDomains\Application\Services\TenantDomainService;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Http\Middleware\ResolveTenantDomain;

/**
 * Phase 1 — portability / compliance proof.
 */
class TenantDomainsComplianceTest extends TenantDomainsTestCase
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

    public function test_portability_lifecycle_resolve_and_context_isolation(): void
    {
        $hashesBefore = $this->hashWatchedHostFiles();

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenant-domains'));
        $this->assertFalse(Schema::hasTable('tenancy_domains'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.domain'));

        $this->artisan('module:doctor', ['code' => 'tenant-domains'])->assertFailed();
        $this->assertFalse(Schema::hasTable('tenancy_domains'));

        $this->installDependencies();
        $this->artisan('module:doctor', ['code' => 'tenant-domains'])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('tenancy_domains'));

        $manager = app(ModuleManager::class);
        $install = $manager->install('tenant-domains');
        $this->assertTrue($install['success'], implode(' | ', $install['messages']));
        $this->assertTrue(
            collect($install['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migrations applied')),
        );
        $this->assertTrue(Schema::hasTable('tenancy_domains'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertInstanceOf(TenantDomainService::class, app(TenantDomainContract::class));

        $domains = app(TenantDomainContract::class);
        $tenantA = $this->createActiveTenant('alpha', 'Alpha');
        $tenantB = $this->createActiveTenant('beta', 'Beta');
        $user = $this->createUser('comp@example.com');

        $domains->attach($tenantA->id, 'WWW.Alpha.Example.com:443', primary: true);
        $domains->attach($tenantA->id, 'alpha.example.com', primary: false);
        $domains->attach($tenantB->id, 'beta.example.com', primary: true);

        $this->assertSame('www.alpha.example.com', $domains->findByHostname('www.alpha.example.com')?->hostname);
        $this->assertCount(1, array_filter(
            $domains->listForTenant($tenantA->id),
            fn ($d) => $d->isPrimary,
        ));

        $domains->setPrimary($tenantA->id, $domains->findByHostname('alpha.example.com')->id);
        $primaries = array_values(array_filter(
            $domains->listForTenant($tenantA->id),
            fn ($d) => $d->isPrimary,
        ));
        $this->assertCount(1, $primaries);
        $this->assertSame('alpha.example.com', $primaries[0]->hostname);

        $resolved = $domains->resolve('ALPHA.Example.com');
        $this->assertNotNull($resolved);
        $this->assertSame($tenantA->id, $resolved->tenantId);

        app(MembershipContract::class)->add($user->id, $tenantA->id);
        app(MembershipContract::class)->add($user->id, $tenantB->id);
        app(TenantContextContract::class)->setCurrent($user->id, $tenantA->id);
        $this->assertSame($tenantA->id, app(TenantContextContract::class)->currentTenantId($user->id));

        $this->assertSame($tenantB->id, $domains->resolve('beta.example.com')?->tenantId);
        $this->assertSame($tenantA->id, app(TenantContextContract::class)->currentTenantId($user->id));

        $domains->deactivate($domains->findByHostname('beta.example.com')->id);
        $this->assertNull($domains->resolve('beta.example.com'));
        $domains->activate($domains->findByHostname('beta.example.com')->id);
        app(TenantContract::class)->deactivate($tenantB->id);
        $this->assertNull($domains->resolve('beta.example.com'));

        $this->get('/tenant-domains')->assertOk();
        $this->assertSame(['tenant-domains.manage'], array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('tenant-domains'),
        ));

        $capturedTenantId = null;
        app(ResolveTenantDomain::class)->handle(
            \Illuminate\Http\Request::create('http://alpha.example.com/', 'GET'),
            function ($req) use (&$capturedTenantId) {
                $capturedTenantId = $req->attributes->get(ResolveTenantDomain::ATTR_TENANT_ID);

                return response('ok');
            },
        );
        $this->assertSame($tenantA->id, $capturedTenantId);
        $this->assertSame($tenantA->id, app(TenantContextContract::class)->currentTenantId($user->id));

        $disable = $manager->disable('tenant-domains');
        $this->assertTrue($disable['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenant-domains'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenant-domains'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenant-domains'),
        );
        $this->assertDatabaseHas('tenancy_domains', ['hostname' => 'alpha.example.com']);

        $enable = $manager->enable('tenant-domains');
        $this->assertTrue($enable['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertNotNull(app(TenantDomainContract::class)->resolve('alpha.example.com'));

        $this->assertSame($hashesBefore, $this->hashWatchedHostFiles());
    }

    public function test_migration_failure_is_fail_closed(): void
    {
        $this->installDependencies();

        Schema::create('tenancy_domains', function ($table) {
            $table->id();
            $table->string('hostname')->unique();
        });

        $hashesBefore = $this->hashWatchedHostFiles();
        $result = app(ModuleManager::class)->install('tenant-domains');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migration failed')),
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'NOT installed')),
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenant-domains'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertFalse(app()->bound(TenantDomainContract::class));
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
