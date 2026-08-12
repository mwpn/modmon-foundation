<?php

declare(strict_types=1);

namespace App\Foundation\SDK\DTOs;

/**
 * Represents a navigation contribution from a module.
 */
final readonly class NavigationItem
{
    public function __construct(
        public string  $id,
        public string  $moduleCode,
        public string  $label,
        public string  $route,
        public ?string $icon = null,
        public ?string $permission = null,
        public ?string $workspace = null,
        public ?string $group = null,
        public int     $order = 100,
        public ?string $activePattern = null,
    ) {}
}
