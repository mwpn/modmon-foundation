<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;

/**
 * Phase 3 — portability / compliance proof (no new features).
 *
 * Models a clean-host sequence on this Foundation authoring host:
 * Tenancy discovered → doctor fails without Identity → Identity install →
 * doctor passes → install owns migrations → contracts + HTTP +
 * contributions → disable/enable preserves data → fail-closed migration →
 * host sources untouched. Requires identity.user only (no RBAC/Settings/
 * Subscription).
 */
final class TenancyComplianceTest extends TenancyTestCase
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

    public function test_portability_lifecycle_contributions_and_invariants(): void
    {
        $hashesBefore = $this->hashWatchedHostFiles();

        // Copy/discovery only — Tenancy not installed; no schema; no caps.
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenancy'));
        $this->assertFalse(Schema::hasTable('tenancy_tenants'));
        $this->assertFalse(Schema::hasTable('tenancy_memberships'));
        $this->assertFalse(Schema::hasTable('tenancy_contexts'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.tenant'));
        $this->assertFalse(app()->bound(TenantContract::class));

        // Doctor fails without Identity (identity.user missing).
        $diagnostics = app(ModuleManager::class)->diagnose('tenancy');
        $capabilities = collect($diagnostics)->first(fn ($d) => $d->check === 'capabilities');
        $this->assertNotNull($capabilities);
        $this->assertFalse($capabilities->passed);
        $this->assertStringContainsString('identity.user', $capabilities->message);

        // Discovery / failed doctor must not mutate schema.
        $this->assertFalse(Schema::hasTable('tenancy_tenants'));

        // Identity first — then doctor passes.
        $this->installIdentity();
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('identity.user'));

        foreach (app(ModuleManager::class)->diagnose('tenancy') as $diagnostic) {
            $this->assertTrue(
                $diagnostic->passed,
                "{$diagnostic->check}: {$diagnostic->message}",
            );
        }
        $this->assertFalse(Schema::hasTable('tenancy_tenants'));

        $manager = app(ModuleManager::class);
        $install = $manager->install('tenancy');
        $this->assertTrue($install['success'], implode(' | ', $install['messages']));
        $this->assertTrue(
            collect($install['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migrations applied')),
            'Explicit install must own and report Tenancy migrations',
        );

        $this->assertTrue(Schema::hasTable('tenancy_tenants'));
        $this->assertTrue(Schema::hasTable('tenancy_memberships'));
        $this->assertTrue(Schema::hasTable('tenancy_contexts'));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('tenancy'));

        $caps = app(CapabilityRegistryContract::class);
        $this->assertTrue($caps->has('tenancy.tenant'));
        $this->assertTrue($caps->has('tenancy.membership'));
        $this->assertTrue($caps->has('tenancy.context'));
        $this->assertFalse($caps->has('authorization.permission'));
        $this->assertFalse($caps->has('settings.runtime'));

        $this->assertTrue(app()->bound(TenantContract::class));
        $this->assertTrue(app()->bound(MembershipContract::class));
        $this->assertTrue(app()->bound(TenantContextContract::class));

        // Core contracts + HTTP.
        $user = $this->createUser('compliance@example.com');
        $tenant = app(TenantContract::class)->create('acme', 'Acme Compliance');
        app(MembershipContract::class)->add($user->id, $tenant->id);
        app(TenantContextContract::class)->setCurrent($user->id, $tenant->id);
        $this->assertSame($tenant->id, app(TenantContextContract::class)->currentTenantId($user->id));

        $this->get('/tenancy/tenants')->assertOk();
        $this->get('/tenancy/tenants/'.$tenant->id.'/edit')->assertOk();
        $this->get('/tenancy/context?user_id='.$user->id)->assertOk();

        // Experience contributions while enabled.
        $permIds = array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('tenancy'),
        );
        $this->assertEqualsCanonicalizing([
            'tenancy.tenants.view',
            'tenancy.tenants.manage',
            'tenancy.members.manage',
            'tenancy.context.switch',
        ], $permIds);

        $this->assertTrue(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->id === 'tenancy.tenants'),
        );
        $this->assertTrue(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->id === 'tenancy.active-tenants'),
        );

        // Disable clears runtime contributions; data survives.
        $disable = $manager->disable('tenancy');
        $this->assertTrue($disable['success'], implode(' | ', $disable['messages']));
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenancy'));
        $this->assertFalse($caps->has('tenancy.tenant'));
        $this->assertFalse($caps->has('tenancy.membership'));
        $this->assertFalse($caps->has('tenancy.context'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenancy'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenancy'),
        );
        $this->assertFalse(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->moduleCode === 'tenancy'),
        );

        $this->assertDatabaseHas('tenancy_tenants', ['code' => 'acme', 'name' => 'Acme Compliance']);
        $this->assertDatabaseHas('tenancy_memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('tenancy_contexts', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        // Re-enable restores capabilities, contributions, contracts, HTTP.
        $enable = $manager->enable('tenancy');
        $this->assertTrue($enable['success'], implode(' | ', $enable['messages']));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.tenant'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.membership'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.context'));
        $this->assertSame('acme', app(TenantContract::class)->findByCode('acme')?->code);
        $this->assertTrue(app(MembershipContract::class)->isMember($user->id, $tenant->id));
        $this->assertSame($tenant->id, app(TenantContextContract::class)->currentTenantId($user->id));
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('tenancy'));
        $this->assertTrue(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->id === 'tenancy.tenants'),
        );
        $this->assertTrue(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->id === 'tenancy.active-tenants'),
        );
        $this->get('/tenancy/tenants')->assertOk();

        $this->assertSame(
            $hashesBefore,
            $this->hashWatchedHostFiles(),
            'Install/disable/enable must not modify unrelated host source files',
        );
    }

    public function test_migration_failure_is_fail_closed(): void
    {
        $this->installIdentity();

        Schema::create('tenancy_tenants', function ($table) {
            $table->id();
            $table->string('code')->unique();
        });

        $hashesBefore = $this->hashWatchedHostFiles();

        $result = app(ModuleManager::class)->install('tenancy');

        $this->assertFalse($result['success']);
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'Migration failed')),
        );
        $this->assertTrue(
            collect($result['messages'])->contains(fn ($m) => str_contains((string) $m, 'NOT installed')),
        );
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenancy'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.tenant'));
        $this->assertFalse(app()->bound(TenantContract::class));
        $this->assertSame($hashesBefore, $this->hashWatchedHostFiles());
    }

    public function test_tenancy_requires_identity_user_only(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(base_path('Modules/Tenancy/module.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(['identity.user'], $manifest['requires']['capabilities'] ?? []);
        $this->assertSame(
            ['tenancy.tenant', 'tenancy.membership', 'tenancy.context'],
            $manifest['provides'],
        );
        $this->assertSame('platform', $manifest['type']);
        $this->assertNotContains('authorization.permission', $manifest['requires']['capabilities']);
        $this->assertNotContains('settings.runtime', $manifest['requires']['capabilities']);
        $this->assertNotContains('identity.authentication', $manifest['requires']['capabilities']);

        $this->installIdentityAndTenancy();
        $caps = app(CapabilityRegistryContract::class);
        $this->assertTrue($caps->has('identity.user'));
        $this->assertTrue($caps->has('tenancy.tenant'));
        $this->assertFalse($caps->has('authorization.permission'));
        $this->assertFalse($caps->has('settings.runtime'));
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
