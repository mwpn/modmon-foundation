<?php

declare(strict_types=1);

namespace App\Foundation\SDK\DTOs;

/**
 * Represents a single diagnostic check result.
 */
final readonly class ModuleDiagnostic
{
    public function __construct(
        public string $check,
        public bool   $passed,
        public string $message,
    ) {}
}
