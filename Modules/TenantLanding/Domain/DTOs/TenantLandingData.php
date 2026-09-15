<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Domain\DTOs;

/**
 * Write payload for TenantLandingContract::update().
 */
final readonly class TenantLandingData
{
    public function __construct(
        public ?string $headline = null,
        public ?string $summary = null,
        public bool $showLoginCta = true,
    ) {}
}
