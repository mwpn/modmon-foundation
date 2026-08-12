<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Components;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use Illuminate\View\Component;

/**
 * The application shell component.
 *
 * Renders the TailAdmin-based layout with registry-driven navigation.
 */
class AppShell extends Component
{
    public array $navigationItems;

    public array $navigationGroups;

    public function __construct(
        ?string $workspace = null,
    ) {
        $nav = app(NavigationRegistryContract::class);
        $this->navigationItems  = $nav->items($workspace);
        $this->navigationGroups = $nav->grouped($workspace);
    }

    public function render()
    {
        return view('foundation::layouts.app');
    }
}
