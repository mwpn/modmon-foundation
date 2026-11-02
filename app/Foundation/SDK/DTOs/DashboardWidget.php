<?php

declare(strict_types=1);

namespace App\Foundation\SDK\DTOs;

/**
 * Represents a dashboard widget contribution from a module.
 */
final readonly class DashboardWidget
{
    /**
     * Contract 1.5.0 (ADR-0013): widgets for this slot float over every page
     * of the app shell (end of <body>); each widget positions and hides itself.
     */
    public const SHELL_FLOATING = 'shell.floating';

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
