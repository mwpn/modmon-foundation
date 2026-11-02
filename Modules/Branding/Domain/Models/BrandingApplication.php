<?php

declare(strict_types=1);

namespace Modules\Branding\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Branding-owned Eloquent model — internal only.
 * Singleton row in branding_application (created on first update).
 */
class BrandingApplication extends Model
{
    protected $table = 'branding_application';

    protected $fillable = [
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
