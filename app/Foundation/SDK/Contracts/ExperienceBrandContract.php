<?php

declare(strict_types=1);

namespace App\Foundation\SDK\Contracts;

use App\Foundation\SDK\DTOs\ExperienceBrand;

/**
 * The brand of the current request (Contract 1.4.0, ADR-0012). Request
 * scoped. Modules contribute by decorating the binding:
 *
 *     $this->app->extend(ExperienceBrandContract::class, fn ($inner) => new MyBrand($inner));
 *
 * and returning `$inner->current()->overlay([...], ExperienceBrand::LEVEL_…)`.
 * Implementations must be cheap and must not throw.
 */
interface ExperienceBrandContract
{
    public function current(): ExperienceBrand;
}
