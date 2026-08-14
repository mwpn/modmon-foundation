<?php

declare(strict_types=1);

namespace Modules\Rbac\Http\Controllers;

use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Modules\Rbac\Domain\Exceptions\UnregisteredPermissionException;

/**
 * Role management surface.
 *
 * Every action is protected by `rbac.roles.manage` via the Laravel
 * Gate (Phase 2 integration) through the `can:` route middleware —
 * the single authorization path. No second authorization mechanism
 * exists.
 *
 * All data access goes through the public `RoleManagementContract` —
 * never direct queries against RBAC or Identity tables.
 */
final class RoleController extends RbacController
{
    public function __construct(
        private readonly RoleManagementContract $roles,
    ) {}

    public function index(): View
    {
        $roles = $this->roles->all();

        $permissionCounts = [];
        foreach ($roles as $role) {
            $permissionCounts[$role->id] = count($this->roles->rolePermissionIds((int) $role->id));
        }

        return view('rbac::roles.index', [
            'roles' => $roles,
            'permissionCounts' => $permissionCounts,
            'permissions' => $this->registeredPermissions(),
        ]);
    }

    public function create(): View
    {
        return view('rbac::roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:rbac_roles,name'],
        ]);

        $this->roles->createRole($validated['name']);

        return redirect()
            ->route('rbac.roles.index')
            ->with('status', "Role '{$validated['name']}' created.");
    }

    public function edit(int $roleId): View
    {
        $role = $this->roles->find($roleId);

        abort_if($role === null, 404);

        return view('rbac::roles.edit', [
            'role' => $role,
            'permissions' => $this->registeredPermissions(),
            'assignedPermissionIds' => $this->roles->rolePermissionIds($roleId),
            'assignedUserIds' => $this->roles->userIdsWithRole($roleId),
        ]);
    }

    public function update(Request $request, int $roleId): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:rbac_roles,name,'.$roleId],
        ]);

        $this->roles->updateRole($roleId, $validated['name']);

        return redirect()
            ->route('rbac.roles.edit', $roleId)
            ->with('status', 'Role updated.');
    }

    public function destroy(int $roleId): RedirectResponse
    {
        $this->roles->deleteRole($roleId);

        return redirect()
            ->route('rbac.roles.index')
            ->with('status', 'Role deleted.');
    }

    public function assignPermission(Request $request, int $roleId): RedirectResponse
    {
        $validated = $request->validate([
            'permission_id' => ['required', 'string'],
        ]);

        try {
            $this->roles->assignPermissionToRole($roleId, $validated['permission_id']);
        } catch (UnregisteredPermissionException $e) {
            return redirect()
                ->route('rbac.roles.edit', $roleId)
                ->withErrors(['permission_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('rbac.roles.edit', $roleId)
            ->with('status', 'Permission assigned.');
    }

    public function removePermission(Request $request, int $roleId): RedirectResponse
    {
        $validated = $request->validate([
            'permission_id' => ['required', 'string'],
        ]);

        $this->roles->removePermissionFromRole($roleId, $validated['permission_id']);

        return redirect()
            ->route('rbac.roles.edit', $roleId)
            ->with('status', 'Permission removed.');
    }

    /**
     * Permission ids currently registered in the Foundation
     * `PermissionRegistry` (live, not snapshotted).
     *
     * @return array{id: string, label: string, moduleCode: string}[]
     */
    private function registeredPermissions(): array
    {
        $permissions = app(PermissionRegistryContract::class)->all();

        return array_map(
            static fn (PermissionDefinition $permission) => [
                'id' => $permission->id,
                'label' => $permission->label,
                'moduleCode' => $permission->moduleCode,
            ],
            $permissions,
        );
    }
}
