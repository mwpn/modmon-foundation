<?php

declare(strict_types=1);

namespace App\Foundation\Experience\Components;

use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\DTOs\DashboardWidget;
use Illuminate\View\Component;

/**
 * Renders all widgets registered to a specific dashboard slot.
 */
class DashboardSlot extends Component
{
    /** @var DashboardWidget[] */
    public array $widgets;

    public function __construct(
        public string $slot,
    ) {
        $registry = app(WorkspaceRegistryContract::class);
        $this->widgets = $registry->widgetsForSlot($slot);
    }

    public function render()
    {
        return view('foundation::components.dashboard-slot');
    }
}
