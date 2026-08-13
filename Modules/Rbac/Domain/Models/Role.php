<?php

declare(strict_types=1);

namespace Modules\Rbac\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RBAC-owned role. Permission and user assignments are intentionally
 * normalized into `rbac_role_permission` / `rbac_user_role` — this
 * model exposes no Eloquent relation API, keeping the RBAC boundary
 * narrow.
 */
final class Role extends Model
{
    protected $table = 'rbac_roles';

    protected $fillable = ['name'];

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
