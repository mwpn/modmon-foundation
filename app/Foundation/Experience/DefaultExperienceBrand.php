<?php

declare(strict_types=1);

namespace App\Foundation\Experience;

use App\Foundation\SDK\Contracts\ExperienceBrandContract;
use App\Foundation\SDK\DTOs\ExperienceBrand;

/**
 * Kernel default brand: the application name only (ADR-0012).
 */
final class DefaultExperienceBrand implements ExperienceBrandContract
{
    public function current(): ExperienceBrand
    {
        return new ExperienceBrand(name: (string) config('app.name', 'ModMon'));
    }
}
