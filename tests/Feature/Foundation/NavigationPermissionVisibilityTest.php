<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Experience\Components\AppShell;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Experience shell honors NavigationItem::permission via Laravel Gate.
 *
 * Generic: no RBAC, Identity, or module-code knowledge. Host-defined
 * abilities work the same as any other Gate ability.
 */
class NavigationPermissionVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance(NavigationRegistryContract::class);

        $nav = app(NavigationRegistryContract::class);
        $nav->register(new NavigationItem(
            id: 'host.dashboard',
            moduleCode: 'host',
            label: 'Dashboard',
            route: '/dashboard',
        ));
        $nav->register(new NavigationItem(
            id: 'host.reports',
            moduleCode: 'host',
            label: 'Reports',
            route: '/reports',
            permission: 'host.reports.view',
            group: 'Tools',
        ));
    }

    public function test_unrestricted_navigation_remains_visible(): void
    {
        $this->actingAs($this->user());

        $ids = $this->shellItemIds();

        $this->assertContains('host.dashboard', $ids);
    }

    public function test_authorized_restricted_item_is_visible(): void
    {
        Gate::define('host.reports.view', fn () => true);
        $this->actingAs($this->user());

        $ids = $this->shellItemIds();

        $this->assertContains('host.reports', $ids);
        $this->assertContains('host.dashboard', $ids);
        $this->assertArrayHasKey('Tools', (new AppShell())->navigationGroups);
    }

    public function test_unauthorized_restricted_item_is_hidden(): void
    {
        Gate::define('host.reports.view', fn () => false);
        $this->actingAs($this->user());

        $ids = $this->shellItemIds();

        $this->assertNotContains('host.reports', $ids);
        $this->assertContains('host.dashboard', $ids);
        $this->assertArrayNotHasKey('Tools', (new AppShell())->navigationGroups);
    }

    public function test_guest_does_not_see_restricted_item(): void
    {
        Gate::define('host.reports.view', fn () => true);

        $ids = $this->shellItemIds();

        $this->assertNotContains('host.reports', $ids);
        $this->assertContains('host.dashboard', $ids);
    }

    public function test_host_defined_laravel_ability_can_gate_navigation(): void
    {
        Gate::define('billing.export', fn ($user) => (string) $user->getAuthIdentifier() === '42');

        app(NavigationRegistryContract::class)->register(new NavigationItem(
            id: 'host.billing',
            moduleCode: 'host',
            label: 'Billing',
            route: '/billing',
            permission: 'billing.export',
        ));

        $this->actingAs($this->user(42));
        $this->assertContains('host.billing', $this->shellItemIds());

        $this->actingAs($this->user(7));
        $this->assertNotContains('host.billing', $this->shellItemIds());
    }

    public function test_registry_still_returns_restricted_items(): void
    {
        Gate::define('host.reports.view', fn () => false);
        $this->actingAs($this->user());

        $registryIds = array_map(
            static fn (NavigationItem $item) => $item->id,
            app(NavigationRegistryContract::class)->items(),
        );

        $this->assertContains('host.reports', $registryIds);
        $this->assertNotContains('host.reports', $this->shellItemIds());
    }

    public function test_visibility_does_not_import_rbac(): void
    {
        $shellSource = file_get_contents(
            base_path('app/Foundation/Experience/Components/AppShell.php'),
        );

        $this->assertStringNotContainsString('Modules\\Rbac', $shellSource);
        $this->assertStringNotContainsString('AuthorizationContract', $shellSource);
        $this->assertStringNotContainsString('rbac.', $shellSource);
        $this->assertStringNotContainsString("use Modules\\", $shellSource);
    }

    /**
     * @return string[]
     */
    private function shellItemIds(): array
    {
        return array_map(
            static fn (NavigationItem $item) => $item->id,
            (new AppShell())->navigationItems,
        );
    }

    private function user(int $id = 1): Authenticatable
    {
        return new class ($id) implements Authenticatable {
            use Authorizable;

            public function __construct(private readonly int $id) {}

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int|string
            {
                return $this->id;
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
