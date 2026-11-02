<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\DTOs;

/**
 * Immutable snapshot of the current tenant context for a subject.
 *
 * Null tenant fields mean "no current tenant selected".
 */
final readonly class TenantContextRead
{
    public function __construct(
        public int $userId,
        public ?int $tenantId,
        public ?string $tenantCode = null,
        public ?string $tenantName = null,
    ) {}
}
