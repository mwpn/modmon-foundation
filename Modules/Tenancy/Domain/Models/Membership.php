<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal Eloquent model — belonging only, no role column.
 * Never returned from public contracts.
 */
class Membership extends Model
{
    protected $table = 'tenancy_memberships';

    protected $fillable = [
        'tenant_id',
        'user_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
