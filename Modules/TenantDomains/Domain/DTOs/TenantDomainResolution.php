<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Domain\DTOs;

use Modules\Tenancy\Domain\DTOs\TenantRead;

/**
 * Successful hostname → tenant resolution (request-scoped use).
 */
final readonly class TenantDomainResolution
{
    public function __construct(
        public int $tenantId,
        public string $hostname,
        public bool $isPrimary,
        public TenantRead $tenant,
    ) {}
}
