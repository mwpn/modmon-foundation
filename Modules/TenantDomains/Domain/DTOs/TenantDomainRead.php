<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Domain\DTOs;

/**
 * Raw tenant domain mapping read model.
 */
final readonly class TenantDomainRead
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $hostname,
        public bool $isPrimary,
        public bool $active,
    ) {}
}
