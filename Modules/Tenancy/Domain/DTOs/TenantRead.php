<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\DTOs;

/**
 * Immutable tenant read model for cross-module boundaries.
 * Never expose Eloquent models from Tenancy.
 */
final readonly class TenantRead
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public bool $active,
        public ?\DateTimeImmutable $createdAt = null,
    ) {}
}
