<?php

declare(strict_types=1);

namespace Modules\Branding\Domain\DTOs;

/**
 * Immutable application branding read model.
 * Exposes resolved public URLs — not storage-disk internals.
 */
final readonly class BrandingRead
{
    public function __construct(
        public string $name,
        public ?string $logoUrl,
        public ?string $logoDarkUrl,
        public ?string $faviconUrl,
        public ?string $primaryColor,
        public ?string $accentColor,
        public ?string $loginTitle,
        public ?string $loginSubtitle,
        public bool $configured = false,
    ) {}
}
