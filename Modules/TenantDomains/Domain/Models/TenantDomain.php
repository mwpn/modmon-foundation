<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TenantDomains-owned Eloquent model — internal only.
 * tenant_id is a plain integer (no FK to Tenancy tables).
 */
class TenantDomain extends Model
{
    protected $table = 'tenancy_domains';

    protected $fillable = [
        'tenant_id',
        'hostname',
        'is_primary',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
