<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Domain\DTOs;

/**
 * Immutable landing config read model (null headline/summary = derive at render).
 */
final readonly class TenantLandingRead
{
    public function __construct(
        public int $tenantId,
        public ?string $headline,
        public ?string $summary,
        public bool $showLoginCta,
        public bool $configured = false,
    ) {}
}
