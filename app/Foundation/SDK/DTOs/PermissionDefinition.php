<?php

declare(strict_types=1);

namespace App\Foundation\SDK\DTOs;

/**
 * Represents a permission declared by a module.
 */
final readonly class PermissionDefinition
{
    public function __construct(
        public string  $id,
        public string  $moduleCode,
        public string  $label,
        public ?string $group = null,
        public ?string $description = null,
    ) {}
}
