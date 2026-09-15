<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Internal Eloquent model — never returned from public contracts.
 * tenant_id is a plain int (Tenancy id); no FK to Tenancy tables.
 */
class TenantLandingPage extends Model
{
    protected $table = 'tenant_landing';

    protected $fillable = [
        'tenant_id',
        'headline',
        'summary',
        'show_login_cta',
    ];

    protected function casts(): array
    {
        return [
            'show_login_cta' => 'boolean',
        ];
    }
}
