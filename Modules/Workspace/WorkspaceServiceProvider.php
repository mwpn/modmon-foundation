<?php

declare(strict_types=1);

namespace Modules\Workspace;

use App\Foundation\SDK\Contributions\ContributesDashboard;
use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\DTOs\DashboardWidget;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Support\ServiceProvider;
use Modules\Workspace\Application\Services\ContextActiveWorkspace;

/**
 * Portable workspace composition: binds ActiveWorkspaceContract and
 * contributes minimal owner/tenant Experience surfaces.
 *
 * Does not mutate Tenancy TenantContext. Does not depend on TenantDomains.
 *
 * Binding is skipped when Foundation does not define
 * ActiveWorkspaceContract so pre-1.1 hosts without the interface do
 * not fatal during provider boot. Manifest requires Foundation ^1.1
 * so doctor/install reject Contract 1.0.0 before install.
 */
class WorkspaceServiceProvider extends ServiceProvider implements
    ContributesNavigation,
    ContributesDashboard
{
    public function register(): void
    {
        if (! $this->foundationProvidesActiveWorkspace()) {
            return;
        }

        $this->app->singleton(ContextActiveWorkspace::class);
        $this->app->singleton(
            \App\Foundation\SDK\Contracts\ActiveWorkspaceContract::class,
            ContextActiveWorkspace::class,
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'workspace');
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'workspace.owner.home',
                moduleCode: 'workspace',
                label: 'Owner workspace',
                route: '/dashboard',
                workspace: 'workspace.owner',
                group: 'Workspace',
                order: 20,
                activePattern: 'dashboard',
            ),
            new NavigationItem(
                id: 'workspace.tenant.home',
                moduleCode: 'workspace',
                label: 'Tenant workspace',
                route: '/dashboard',
                workspace: 'workspace.tenant',
                group: 'Workspace',
                order: 20,
                activePattern: 'dashboard',
            ),
        ];
    }

    public function dashboardWidgets(): array
    {
        return [
            new DashboardWidget(
                id: 'workspace.owner.marker',
                moduleCode: 'workspace',
                slot: 'workspace.owner.dashboard.main',
                view: 'workspace::widgets.owner-marker',
                order: 10,
            ),
            new DashboardWidget(
                id: 'workspace.tenant.marker',
                moduleCode: 'workspace',
                slot: 'workspace.tenant.dashboard.main',
                view: 'workspace::widgets.tenant-marker',
                order: 10,
            ),
        ];
    }

    private function foundationProvidesActiveWorkspace(): bool
    {
        return interface_exists(\App\Foundation\SDK\Contracts\ActiveWorkspaceContract::class);
    }
}
