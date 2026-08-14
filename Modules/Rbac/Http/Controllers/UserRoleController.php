<?php

declare(strict_types=1);

namespace Modules\Rbac\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;

/**
 * User ↔ role assignment surface.
 *
 * Protected by `rbac.roles.manage` via the Laravel Gate (Phase 2
 * integration) through the `can:` route middleware — the single
 * authorization path.
 *
 * User resolution uses ONLY the public `UserQueryContract`
 * (`findById`) — the identity contract defines the capabilities. The
 * contract offers id/email lookup but no search/listing API, so this
 * surface selects a single user by id. No Identity model/controller
 * internals are imported.
 */
final class UserRoleController extends RbacController
{
    public function __construct(
        private readonly RoleManagementContract $roles,
        private readonly UserQueryContract $users,
    ) {}

    public function assign(Request $request, int $roleId): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $userId = (string) $validated['user_id'];

        if ($this->users->findById((int) $userId) === null) {
            return redirect()
                ->route('rbac.roles.edit', $roleId)
                ->withErrors(['user_id' => "User '{$userId}' was not found."]);
        }

        $this->roles->assignRoleToUser($userId, $roleId);

        return redirect()
            ->route('rbac.roles.edit', $roleId)
            ->with('status', 'Role assigned to user.');
    }

    public function remove(Request $request, int $roleId): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $this->roles->removeRoleFromUser((string) $validated['user_id'], $roleId);

        return redirect()
            ->route('rbac.roles.edit', $roleId)
            ->with('status', 'Role removed from user.');
    }
}
