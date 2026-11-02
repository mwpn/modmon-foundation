<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\DTOs;

/**
 * Immutable membership read model.
 *
 * Membership means belonging only — not authentication and not
 * authorization/roles (RBAC). Subject is an Identity user id.
 */
final readonly class MembershipRead
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $userId,
        public ?\DateTimeImmutable $joinedAt = null,
    ) {}
}
