<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Components;

use Illuminate\View\Component;

/**
 * Renders a single navigation item in the sidebar.
 */
class NavItem extends Component
{
    public bool $active;

    public function __construct(
        public string  $label,
        public string  $route,
        public ?string $icon = null,
        public ?string $activePattern = null,
    ) {
        $this->active = $activePattern
            ? request()->is(ltrim($activePattern, '/'))
            : request()->url() === url($route);
    }

    public function render()
    {
        return view('foundation::components.nav-item');
    }
}
