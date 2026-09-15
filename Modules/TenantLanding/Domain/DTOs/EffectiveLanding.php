<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Domain\DTOs;

/**
 * Guest landing presentation model (resolved branding + config + optional login).
 */
final readonly class EffectiveLanding
{
    public function __construct(
        public int $tenantId,
        public string $tenantCode,
        public string $brandName,
        public ?string $logoUrl,
        public ?string $logoDarkUrl,
        public ?string $faviconUrl,
        public ?string $primaryColor,
        public ?string $accentColor,
        public string $headline,
        public ?string $summary,
        public ?string $loginUrl,
    ) {}
}
