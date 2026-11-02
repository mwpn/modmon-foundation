<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal Eloquent model for persisted per-user current tenant.
 * Never returned from public contracts.
 */
class TenantContext extends Model
{
    protected $table = 'tenancy_contexts';

    protected $fillable = [
        'user_id',
        'tenant_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
