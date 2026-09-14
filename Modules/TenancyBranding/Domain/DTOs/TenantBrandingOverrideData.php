<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Domain\DTOs;

/**
 * Write payload for TenantBrandingContract::updateOverride().
 *
 * Null text/color fields persist as inherit. Asset clears set path null.
 * Incoming asset paths must stay under tenancy-branding/{tenantId}/.
 */
final readonly class TenantBrandingOverrideData
{
    public function __construct(
        public ?string $name = null,
        public ?string $logoPath = null,
        public ?string $logoDarkPath = null,
        public ?string $faviconPath = null,
        public ?string $primaryColor = null,
        public ?string $accentColor = null,
        public ?string $loginTitle = null,
        public ?string $loginSubtitle = null,
        public bool $clearLogo = false,
        public bool $clearLogoDark = false,
        public bool $clearFavicon = false,
    ) {}
}
