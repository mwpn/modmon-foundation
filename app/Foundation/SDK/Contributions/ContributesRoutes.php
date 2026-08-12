<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contributions;

/**
 * Interface for modules that contribute routes.
 *
 * Module service providers implementing this interface will have
 * their routes loaded automatically when the module is enabled.
 */
interface ContributesRoutes
{
    /**
     * Return the path to the module's route file(s).
     *
     * @return string|string[]
     */
    public function routeFiles(): string|array;
}
