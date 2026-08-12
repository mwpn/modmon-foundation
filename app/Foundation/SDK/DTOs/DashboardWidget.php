<?php

declare(strict_types=1);

namespace App\Foundation\SDK\DTOs;

/**
 * Represents a dashboard widget contribution from a module.
 */
final readonly class DashboardWidget
{
    public function __construct(
        public string  $id,
        public string  $moduleCode,
        public string  $slot,
        public string  $view,
        public ?string $permission = null,
        public int     $order = 100,
        public array   $data = [],
    ) {}
}
