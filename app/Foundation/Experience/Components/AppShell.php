<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Components;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

/**
 * The application shell component.
 *
 * Renders the TailAdmin-inspired layout with registry-driven navigation.
 * Items that declare `permission` are shown only when the current
 * authenticated user is allowed that ability through Laravel Gate.
 * The registry itself is unchanged — disabled-module removal still
 * happens there; this component only applies visibility at render time.
 *
 * Auth user label / logout action are resolved at runtime only. Foundation
 * does not import Identity or any auth module.
 */
class AppShell extends Component
{
    public array $navigationItems;

    public array $navigationGroups;

    public ?string $userLabel = null;

    public ?string $userInitial = null;

    public ?string $logoutUrl = null;

    public function __construct(
        ?string $workspace = null,
    ) {
        $nav = app(NavigationRegistryContract::class);
        $this->navigationItems  = $this->visible($nav->items($workspace));
        $this->navigationGroups = $this->visibleGroups($nav->grouped($workspace));

        $user = Auth::user();
        if ($user instanceof Authenticatable) {
            $this->userLabel = $this->resolveUserLabel($user);
            $this->userInitial = mb_strtoupper(mb_substr($this->userLabel, 0, 1));
            $this->logoutUrl = $this->resolveLogoutUrl();
        }
    }

    /**
     * @param  NavigationItem[]  $items
     * @return NavigationItem[]
     */
    private function visible(array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (NavigationItem $item) => $this->isVisible($item),
        ));
    }

    /**
     * @param  array<string, NavigationItem[]>  $groups
     * @return array<string, NavigationItem[]>
     */
    private function visibleGroups(array $groups): array
    {
        $filtered = [];

        foreach ($groups as $group => $items) {
            $visible = $this->visible($items);
            if ($visible !== []) {
                $filtered[$group] = $visible;
            }
        }

        return $filtered;
    }

    private function isVisible(NavigationItem $item): bool
    {
        if ($item->permission === null || $item->permission === '') {
            return true;
        }

        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return Gate::forUser($user)->allows($item->permission);
    }

    private function resolveUserLabel(Authenticatable $user): string
    {
        foreach (['name', 'email'] as $attribute) {
            $value = data_get($user, $attribute);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return (string) $user->getAuthIdentifier();
    }

    private function resolveLogoutUrl(): ?string
    {
        foreach (['logout', 'identity.logout'] as $name) {
            if (Route::has($name)) {
                return route($name);
            }
        }

        return null;
    }

    public function render()
    {
        return view('foundation::layouts.app');
    }
}
