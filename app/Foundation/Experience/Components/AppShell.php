<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Components;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;

/**
 * The application shell component.
 *
 * Renders the TailAdmin-based layout with registry-driven navigation.
 * Items that declare `permission` are shown only when the current
 * authenticated user is allowed that ability through Laravel Gate.
 * The registry itself is unchanged — disabled-module removal still
 * happens there; this component only applies visibility at render time.
 */
class AppShell extends Component
{
    public array $navigationItems;

    public array $navigationGroups;

    public function __construct(
        ?string $workspace = null,
    ) {
        $nav = app(NavigationRegistryContract::class);
        $this->navigationItems  = $this->visible($nav->items($workspace));
        $this->navigationGroups = $this->visibleGroups($nav->grouped($workspace));
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

    public function render()
    {
        return view('foundation::layouts.app');
    }
}
