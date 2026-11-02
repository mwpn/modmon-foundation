<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;

/**
 * Phase 2 Experience contributions and HTTP admin surface.
 */
final class TenancyContributionTest extends TenancyTestCase
{
    public function test_permissions_navigation_and_dashboard_are_contributed_when_enabled(): void
    {
        $this->installIdentityAndTenancy();

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

        $nav = collect(app(NavigationRegistryContract::class)->items());
        $tenantsNav = $nav->first(fn ($i) => $i->id === 'tenancy.tenants');
        $contextNav = $nav->first(fn ($i) => $i->id === 'tenancy.context');

        $this->assertNotNull($tenantsNav);
        $this->assertSame('/tenancy/tenants', $tenantsNav->route);
        $this->assertSame('tenancy.tenants.view', $tenantsNav->permission);
        $this->assertNotNull($contextNav);
        $this->assertSame('/tenancy/context', $contextNav->route);
        $this->assertSame('tenancy.context.switch', $contextNav->permission);

        $widgets = app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats');
        $this->assertTrue(collect($widgets)->contains(fn ($w) => $w->id === 'tenancy.active-tenants'));
        $this->assertTrue(collect($widgets)->contains(
            fn ($w) => $w->id === 'tenancy.active-tenants' && $w->slot === 'workspace.default.dashboard.stats',
        ));
    }

    public function test_http_tenant_crud_membership_and_context(): void
    {
        $this->installIdentityAndTenancy();
        $user = $this->createUser();

        $this->get('/tenancy/tenants')->assertOk();
        $this->get('/tenancy/tenants/create')->assertOk();

        $this->post('/tenancy/tenants', [
            'code' => 'acme',
            'name' => 'Acme Corp',
        ])->assertRedirect();

        $tenant = app(TenantContract::class)->findByCode('acme');
        $this->assertNotNull($tenant);
        $this->assertTrue($tenant->active);

        $this->get('/tenancy/tenants/'.$tenant->id.'/edit')->assertOk();

        $this->put('/tenancy/tenants/'.$tenant->id, [
            'name' => 'Acme Renamed',
        ])->assertRedirect(route('tenancy.tenants.edit', ['tenant' => $tenant->id]));

        $this->assertSame('Acme Renamed', app(TenantContract::class)->findById($tenant->id)?->name);

        $this->post('/tenancy/tenants/'.$tenant->id.'/members', [
            'user_id' => $user->id,
        ])->assertRedirect(route('tenancy.tenants.edit', ['tenant' => $tenant->id]));

        $this->assertTrue(app(MembershipContract::class)->isMember($user->id, $tenant->id));

        $this->get('/tenancy/context?user_id='.$user->id)->assertOk();

        $this->post('/tenancy/context', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ])->assertRedirect(route('tenancy.context.show', ['user_id' => $user->id]));

        $this->assertSame($tenant->id, app(TenantContextContract::class)->currentTenantId($user->id));

        $this->post('/tenancy/context/clear', [
            'user_id' => $user->id,
        ])->assertRedirect(route('tenancy.context.show', ['user_id' => $user->id]));

        $this->assertNull(app(TenantContextContract::class)->currentTenantId($user->id));
        $this->assertTrue(app(MembershipContract::class)->isMember($user->id, $tenant->id));

        $this->post('/tenancy/tenants/'.$tenant->id.'/deactivate')
            ->assertRedirect(route('tenancy.tenants.edit', ['tenant' => $tenant->id]));
        $this->assertFalse(app(TenantContract::class)->findById($tenant->id)?->active);

        $this->post('/tenancy/tenants/'.$tenant->id.'/activate')
            ->assertRedirect(route('tenancy.tenants.edit', ['tenant' => $tenant->id]));
        $this->assertTrue(app(TenantContract::class)->findById($tenant->id)?->active);

        $this->delete('/tenancy/tenants/'.$tenant->id.'/members/'.$user->id)
            ->assertRedirect(route('tenancy.tenants.edit', ['tenant' => $tenant->id]));
        $this->assertFalse(app(MembershipContract::class)->isMember($user->id, $tenant->id));
    }

    public function test_http_context_set_rejects_non_member(): void
    {
        $this->installIdentityAndTenancy();
        $user = $this->createUser();
        $tenant = app(TenantContract::class)->create('acme', 'Acme');

        $this->post('/tenancy/context', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ])->assertRedirect()->assertSessionHasErrors('tenant_id');
    }

    public function test_routes_contributions_cleared_after_disable(): void
    {
        $manager = $this->installIdentityAndTenancy();
        $this->get('/tenancy/tenants')->assertOk();

        $this->assertTrue($manager->disable('tenancy')['success']);

        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenancy'),
        );
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenancy'));
        $this->assertFalse(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->moduleCode === 'tenancy'),
        );
    }
}
