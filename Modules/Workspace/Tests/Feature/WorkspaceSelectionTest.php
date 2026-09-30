<?php

declare(strict_types=1);

namespace Modules\Workspace\Tests\Feature;

use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;
use Modules\Workspace\Application\Services\ContextActiveWorkspace;

class WorkspaceSelectionTest extends WorkspaceTestCase
{
    public function test_owner_fallback_without_domain_or_context(): void
    {
        $this->installStack();

        $this->assertSame(
            ContextActiveWorkspace::WORKSPACE_OWNER,
            app(ActiveWorkspaceContract::class)->current(),
        );
    }

    public function test_tenant_selected_from_request_domain_attribute(): void
    {
        $this->installStack();

        request()->attributes->set(ContextActiveWorkspace::ATTR_TENANT_DOMAIN_TENANT_ID, 7);

        $this->assertSame(
            ContextActiveWorkspace::WORKSPACE_TENANT,
            app(ActiveWorkspaceContract::class)->current(),
        );
    }

    public function test_tenant_selected_from_tenancy_context(): void
    {
        $this->installStack();
        $user = $this->createUser('ctx@example.com');
        $tenant = $this->createActiveTenant('beta', 'Beta');
        $this->assignTenantContext($user, $tenant);
        $this->actingAs($user);

        $this->assertSame(
            ContextActiveWorkspace::WORKSPACE_TENANT,
            app(ActiveWorkspaceContract::class)->current(),
        );
    }

    public function test_domain_attribute_wins_over_owner_fallback(): void
    {
        $this->installStack();
        $this->actingAs($this->createUser('plain@example.com'));
        request()->attributes->set(ContextActiveWorkspace::ATTR_TENANT_DOMAIN_TENANT_ID, 3);

        $this->assertSame(
            ContextActiveWorkspace::WORKSPACE_TENANT,
            app(ActiveWorkspaceContract::class)->current(),
        );
    }

    public function test_dashboard_and_nav_follow_owner_workspace(): void
    {
        $this->installStack();
        $this->registerGlobalNav();
        $this->actingAs($this->createUser('owner-dash@example.com'));

        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-workspace-owner', $html);
        $this->assertStringNotContainsString('data-workspace-tenant', $html);
        $this->assertStringContainsString('Owner workspace', $html);
        $this->assertStringNotContainsString('>Tenant workspace<', $html);
        $this->assertStringContainsString('Global Nav', $html);
    }

    public function test_dashboard_and_nav_follow_tenant_workspace_from_context(): void
    {
        $this->installStack();
        $this->registerGlobalNav();

        $user = $this->createUser('tenant-dash@example.com');
        $tenant = $this->createActiveTenant('gamma', 'Gamma');
        $this->assignTenantContext($user, $tenant);
        $this->actingAs($user);

        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-workspace-tenant', $html);
        $this->assertStringNotContainsString('data-workspace-owner', $html);
        $this->assertStringContainsString('Tenant workspace', $html);
        $this->assertStringNotContainsString('>Owner workspace<', $html);
        $this->assertStringContainsString('Global Nav', $html);
    }

    private function registerGlobalNav(): void
    {
        app(NavigationRegistryContract::class)->register(new NavigationItem(
            id: 'test.global',
            moduleCode: 'example',
            label: 'Global Nav',
            route: '/global',
            group: 'Modules',
            order: 5,
        ));
    }
}
