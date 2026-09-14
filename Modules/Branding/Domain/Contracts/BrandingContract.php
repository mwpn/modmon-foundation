<?php

declare(strict_types=1);

namespace Modules\Branding\Domain\Contracts;

use Modules\Branding\Domain\DTOs\BrandingData;
use Modules\Branding\Domain\DTOs\BrandingRead;

/**
 * Public branding.application capability contract.
 *
 * Cross-module consumers read application branding here only —
 * never via Branding Eloquent models or branding_* tables.
 */
interface BrandingContract
{
    /**
     * Current application branding. Returns sensible defaults when
     * the singleton row has not been configured yet (no seed required).
     */
    public function current(): BrandingRead;

    /**
     * Upsert the application branding singleton.
     */
    public function update(BrandingData $data): BrandingRead;
}
