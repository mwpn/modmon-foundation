<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Domain\DTOs;

/**
 * Raw tenant branding override read model for admin.
 * Null fields mean inherit from application Branding.
 * Asset URLs are resolved only when an override path is set.
 */
final readonly class TenantBrandingOverrideRead
{
    public function __construct(
        public int $tenantId,
        public bool $exists,
        public ?string $name,
        public ?string $logoUrl,
        public ?string $logoDarkUrl,
        public ?string $faviconUrl,
        public ?string $primaryColor,
        public ?string $accentColor,
        public ?string $loginTitle,
        public ?string $loginSubtitle,
    ) {}
}
