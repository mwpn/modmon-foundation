<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Illuminate\Contracts\Auth\Access\Gate;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Tests\Feature\RbacTestCase;

/**
 * Phase 2 — Laravel Gate integration.
 *
 * While RBAC is enabled, its contributed permissions resolve through
 * the Laravel authorization runtime (`$user->can()`, `Gate::allows()`)
 * without any caller importing RBAC internals. While RBAC is disabled
 * no authorization contribution remains active, and re-enabling
 * restores the behavior without touching preserved assignment data.
 *
 * The runtime authorizes *any* permission id currently registered in
 * the canonical Foundation `PermissionRegistry` — including
 * permissions contributed by business/platform modules (here
 * `rbac-test.permission` under module `rbac-test-fixture`) — and
 * returns null for unregistered abilities so the host Gate stays in
 * control.
 */
class RbacGateIntegrationTest extends RbacTestCase
{
    public function test_gate_allows_user_with_role_and_permission(): void
    {
        $this->installRbac();

        $roleId = $this->createGrantedRole(2);

        $user = new GateTestUser(2);

        $this->assertTrue($user->can('rbac.roles.manage'));
        $this->assertTrue(app(Gate::class)->forUser($user)->allows('rbac.roles.manage'));
        $this->assertSame([$roleId], $this->roles()->userRoleIds('2'));
    }

    public function test_gate_denies_user_without_permission(): void
    {
        $this->installRbac();

        $roleId = $this->roles()->createRole('viewer');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $user = new GateTestUser(2);

        $this->assertFalse($user->can('rbac.roles.manage'));
        $this->assertFalse(app(Gate::class)->forUser($user)->allows('rbac.roles.manage'));
    }

    public function test_gate_denies_user_without_any_role(): void
    {
        $this->installRbac();

        $this->userQuery->seed(2);

        $this->assertFalse((new GateTestUser(2))->can('rbac.roles.manage'));
    }

    public function test_gate_denies_unknown_user(): void
    {
        $this->installRbac();

        $this->assertFalse((new GateTestUser(999))->can('rbac.roles.manage'));
    }

    public function test_gate_denies_guest(): void
    {
        $this->installRbac();

        $this->assertFalse(app(Gate::class)->forUser(null)->allows('rbac.roles.manage'));
    }

    public function test_gate_does_not_answer_non_rbac_abilities(): void
    {
        $this->installRbac();

        $user = new GateTestUser(2);

        // 'inventory.stock.view' is not an RBAC-contributed permission —
        // the callback must not answer it (null), and the Gate has no
        // other definition for it, so it is denied by default.
        $this->assertFalse($user->can('inventory.stock.view'));
    }

    public function test_rbac_does_not_pollute_gate_abilities(): void
    {
        $this->installRbac();

        // The integration uses a Gate::before callback — the abilities
        // map itself stays untouched.
        $this->assertFalse(app(Gate::class)->has('rbac.roles.manage'));
    }

    public function test_gate_allows_externally_contributed_permission_when_assigned(): void
    {
        $this->installRbac();

        // 'rbac-test.permission' is contributed by module
        // 'rbac-test-fixture' (not by rbac). RBAC must authorize it
        // through roles without knowing the contributing module.
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac-test.permission');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $user = new GateTestUser(2);

        $this->assertTrue($user->can('rbac-test.permission'));
        $this->assertTrue(app(Gate::class)->forUser($user)->allows('rbac-test.permission'));
    }

    public function test_gate_denies_externally_contributed_permission_when_not_assigned(): void
    {
        $this->installRbac();

        $this->userQuery->seed(2);

        $this->assertFalse((new GateTestUser(2))->can('rbac-test.permission'));
    }

    public function test_unregistered_contribution_stops_being_rbac_managed(): void
    {
        $this->installRbac();

        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac-test.permission');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $user = new GateTestUser(2);

        $this->assertTrue($user->can('rbac-test.permission'));

        // Removing the contributing module's permissions from the live
        // registry (as the Foundation disable lifecycle does) makes the
        // ability no longer RBAC-managed immediately — no snapshot.
        $this->permissions()->removeByModule('rbac-test-fixture');

        $this->assertFalse($user->can('rbac-test.permission'));
        $this->assertFalse(app(Gate::class)->forUser($user)->allows('rbac-test.permission'));
    }

    public function test_unknown_ability_remains_available_to_host_gate_handling(): void
    {
        $this->installRbac();

        // Register a host-defined ability through the normal Gate API.
        // 'inventory.stock.view' is not in the PermissionRegistry, so
        // RBAC returns null and the host definition decides.
        app(Gate::class)->define('inventory.stock.view', fn ($user) => $user !== null);

        $user = new GateTestUser(2);

        $this->assertTrue($user->can('inventory.stock.view'));
        $this->assertTrue(app(Gate::class)->forUser($user)->allows('inventory.stock.view'));

        // A host deny rule must also be respected.
        app(Gate::class)->define('inventory.stock.edit', fn () => false);

        $this->assertFalse((new GateTestUser(2))->can('inventory.stock.edit'));
    }

    public function test_own_permission_continues_to_work_alongside_external_permissions(): void
    {
        $this->installRbac();

        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');
        $this->roles()->assignPermissionToRole($roleId, 'rbac-test.permission');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $user = new GateTestUser(2);

        $this->assertTrue($user->can('rbac.roles.manage'));
        $this->assertTrue($user->can('rbac-test.permission'));
    }

    public function test_disable_removes_runtime_authorization(): void
    {
        $this->installRbac();
        $this->createGrantedRole(2);

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();

        $user = new GateTestUser(2);

        $this->assertFalse($user->can('rbac.roles.manage'));
        $this->assertFalse(app(Gate::class)->forUser($user)->allows('rbac.roles.manage'));
        $this->assertFalse(app(Gate::class)->has('rbac.roles.manage'));
    }

    public function test_re_enable_restores_runtime_authorization(): void
    {
        $this->installRbac();
        $this->createGrantedRole(2);

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:enable', ['code' => 'rbac'])->assertSuccessful();

        $user = new GateTestUser(2);

        $this->assertTrue($user->can('rbac.roles.manage'));
        $this->assertTrue(app(Gate::class)->forUser($user)->allows('rbac.roles.manage'));
    }

    public function test_disable_re_enable_preserves_assignments(): void
    {
        $this->installRbac();
        $roleId = $this->createGrantedRole(2);

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:enable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertDatabaseHas('rbac_roles', ['id' => $roleId, 'name' => 'admin']);
        $this->assertDatabaseHas('rbac_role_permission', [
            'role_id' => $roleId,
            'permission_id' => 'rbac.roles.manage',
        ]);
        $this->assertDatabaseHas('rbac_user_role', ['user_id' => '2', 'role_id' => $roleId]);
    }

    private function installRbac(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();
    }

    /**
     * Create role 'admin' with rbac.roles.manage granted to user $userId.
     */
    private function createGrantedRole(int $userId): int
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');
        $this->userQuery->seed($userId);
        $this->roles()->assignRoleToUser((string) $userId, $roleId);

        return $roleId;
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }

    private function permissions(): PermissionRegistryContract
    {
        return app(PermissionRegistryContract::class);
    }
}
