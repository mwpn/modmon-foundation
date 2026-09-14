<?php

declare(strict_types=1);

namespace Modules\Branding\Domain\DTOs;

/**
 * Write payload for BrandingContract::update().
 *
 * Asset fields accept module-owned storage-relative paths (or null to
 * clear). Controllers map uploaded files to paths before calling update.
 */
final readonly class BrandingData
{
    public function __construct(
        public string $name,
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
