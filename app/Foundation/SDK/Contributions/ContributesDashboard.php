<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contributions;

use App\Foundation\SDK\DTOs\DashboardWidget;

/**
 * Interface for modules that contribute dashboard widgets.
 */
interface ContributesDashboard
{
    /**
     * Return the dashboard widgets this module contributes.
     *
     * @return DashboardWidget[]
     */
    public function dashboardWidgets(): array;
}
