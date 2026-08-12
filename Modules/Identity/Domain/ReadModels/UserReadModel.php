<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\ReadModels;

/**
 * Immutable read model representing a user, safe to cross module
 * boundaries. Never contains the password or tokens.
 */
final readonly class UserReadModel
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?\DateTimeImmutable $emailVerifiedAt = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {}
}
