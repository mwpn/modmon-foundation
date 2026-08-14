<?php

declare(strict_types=1);

namespace Modules\Rbac\Http\Controllers;

use App\Http\Controllers\Controller;

/**
 * Base controller for the RBAC admin surface.
 *
 * All management actions are protected by `rbac.roles.manage` through
 * the Laravel Gate (Phase 2 integration) — see the route file. No
 * second authorization path exists.
 */
abstract class RbacController extends Controller
{
}
