<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Experience\Workspace\DefaultActiveWorkspace;
use App\Foundation\SDK\Contracts\ActiveWorkspaceContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\DTOs\DashboardWidget;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\View;
use Tests\Fixtures\ActiveWorkspace\OwnerActiveWorkspace;
use Tests\Fixtures\ActiveWorkspace\OwnerWorkspaceServiceProvider;
use Tests\TestCase;

/**
 * Active workspace resolution for Experience dashboard + shell nav.
 *
 * Consumer boundary proof: workspace modules rebind
 * {@see ActiveWorkspaceContract} through a normal ServiceProvider.
 */
class ActiveWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance(NavigationRegistryContract::class);
        $this->app->forgetInstance(WorkspaceRegistryContract::class);
        $this->app->forgetInstance(ActiveWorkspaceContract::class);

        View::addNamespace('experience-test', base_path('tests/fixtures/views'));
    }

    public function test_default_active_workspace_is_workspace_default(): void
    {
        $resolver = app(ActiveWorkspaceContract::class);

        $this->assertInstanceOf(DefaultActiveWorkspace::class, $resolver);
        $this->assertSame(ActiveWorkspaceContract::DEFAULT, $resolver->current());
    }

    public function test_default_dashboard_renders_only_default_workspace_slots(): void
    {
        $this->registerDefaultAndOwnerContributions();

        $this->actingAs($this->user());

        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Default Widget', $html);
        $this->assertStringNotContainsString('Owner Widget', $html);
        $this->assertStringContainsString('Shared Nav', $html);
        $this->assertStringContainsString('Default Nav', $html);
        $this->assertStringNotContainsString('Owner Nav', $html);
    }

    public function test_module_provider_rebind_selects_owner_dashboard_slots_and_nav(): void
    {
        $this->registerDefaultAndOwnerContributions();
        $this->registerOwnerWorkspaceProvider();

        $this->assertInstanceOf(OwnerActiveWorkspace::class, app(ActiveWorkspaceContract::class));
        $this->assertSame('workspace.owner', app(ActiveWorkspaceContract::class)->current());

        $this->actingAs($this->user());

        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Owner Widget', $html);
        $this->assertStringNotContainsString('Default Widget', $html);
        $this->assertStringContainsString('Shared Nav', $html);
        $this->assertStringContainsString('Owner Nav', $html);
        $this->assertStringNotContainsString('Default Nav', $html);
    }

    public function test_explicit_app_shell_workspace_overrides_provider_resolver(): void
    {
        $this->registerDefaultAndOwnerContributions();
        $this->registerOwnerWorkspaceProvider();

        $this->assertSame('workspace.owner', app(ActiveWorkspaceContract::class)->current());

        $this->actingAs($this->user());

        $html = view('experience-test::shell-workspace-override', [
            'workspace' => ActiveWorkspaceContract::DEFAULT,
        ])->render();

        $this->assertStringContainsString('Shared Nav', $html);
        $this->assertStringContainsString('Default Nav', $html);
        $this->assertStringNotContainsString('Owner Nav', $html);
    }

    private function registerOwnerWorkspaceProvider(): void
    {
        $this->app->register(OwnerWorkspaceServiceProvider::class);
        $this->app->forgetInstance(ActiveWorkspaceContract::class);
    }

    private function registerDefaultAndOwnerContributions(): void
    {
        $nav = app(NavigationRegistryContract::class);
        $nav->register(new NavigationItem(
            id: 'test.shared',
            moduleCode: 'example',
            label: 'Shared Nav',
            route: '/shared',
            group: 'Modules',
            order: 10,
        ));
        $nav->register(new NavigationItem(
            id: 'test.default',
            moduleCode: 'example',
            label: 'Default Nav',
            route: '/default-nav',
            workspace: 'workspace.default',
            group: 'Modules',
            order: 20,
        ));
        $nav->register(new NavigationItem(
            id: 'test.owner',
            moduleCode: 'example',
            label: 'Owner Nav',
            route: '/owner-nav',
            workspace: 'workspace.owner',
            group: 'Modules',
            order: 30,
        ));

        $workspace = app(WorkspaceRegistryContract::class);
        $workspace->register(new DashboardWidget(
            id: 'test.default.widget',
            moduleCode: 'example',
            slot: 'workspace.default.dashboard.main',
            view: 'experience-test::default-widget',
            order: 10,
        ));
        $workspace->register(new DashboardWidget(
            id: 'test.owner.widget',
            moduleCode: 'example',
            slot: 'workspace.owner.dashboard.main',
            view: 'experience-test::owner-widget',
            order: 10,
        ));
    }

    private function user(): Authenticatable
    {
        return new class implements Authenticatable {
            use Authorizable;

            public string $name = 'Shell User';

            public string $email = 'shell@example.test';

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int|string
            {
                return 1;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): ?string
            {
                return null;
            }

            public function getRememberToken(): ?string
            {
                return null;
            }

            public function setRememberToken(#[\SensitiveParameter] $value): void
            {
                //
            }

            public function getRememberTokenName(): string
            {
                return 'remember_token';
            }
        };
    }
}
