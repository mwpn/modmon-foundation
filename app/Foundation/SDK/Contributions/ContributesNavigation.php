<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contributions;

use App\Foundation\SDK\DTOs\NavigationItem;

/**
 * Interface for modules that contribute navigation items.
 */
interface ContributesNavigation
{
    /**
     * Return the navigation items this module contributes.
     *
     * @return NavigationItem[]
     */
    public function navigationItems(): array;
}
