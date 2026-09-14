<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TenancyBranding-owned Eloquent model — internal only.
 * tenant_id is a plain integer (no FK to Tenancy tables).
 */
class TenantBrandingOverride extends Model
{
    protected $table = 'tenancy_branding_overrides';

    protected $fillable = [
        'tenant_id',
        'name',
        'logo_path',
        'logo_dark_path',
        'favicon_path',
        'primary_color',
        'accent_color',
        'login_title',
        'login_subtitle',
    ];
}
