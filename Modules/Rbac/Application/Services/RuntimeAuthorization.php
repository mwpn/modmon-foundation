<?php

declare(strict_types=1);

namespace Modules\Rbac\Application\Services;

use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\Container;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;

/**
 * Laravel Gate integration for the RBAC module.
 *
 * Two distinct responsibilities:
 * 1. Contribution — RBAC contributes its own administrative
 *    permission `rbac.roles.manage` via `ContributesPermissions`
 *    (see `RbacServiceProvider::permissionDefinitions()`).
 * 2. Enforcement — the runtime authorizes *any* permission id
 *    currently registered in the canonical Foundation
 *    `PermissionRegistry`, including permissions contributed by
 *    business/platform modules, without knowing their implementation.
 *
 * Registers a single `Gate::before()` callback while the module is
 * enabled. The callback answers for every currently registered
 * permission id and delegates the decision to the public
 * `AuthorizationContract`; abilities not registered in the registry
 * return `null` so the host Laravel Gate/policies remain free to
 * handle them.
 *
 * The registry is resolved from the container at call time — never
 * captured at boot — so the source of truth is always the live
 * registry and no permission set is ever snapshotted. Permissions of
 * a disabled module disappear from the registry and immediately cease
 * being treated as RBAC-managed abilities.
 *
 * Runtime semantics:
 * - RBAC enabled: `$user->can(...)`, `Gate::forUser(...)->allows(...)`
 *   and `Gate::allows(...)` resolve registered permissions through
 *   roles; unregistered abilities fall through to the host Gate.
 * - RBAC disabled: the Foundation lifecycle removes the module's
 *   permissions from the `PermissionRegistry`, so the callback returns
 *   null for every ability — RBAC leaves no active authorization
 *   contribution behind. In a fresh process a disabled module's
 *   provider never boots and the callback is never registered at all.
 * - Re-enable restores the behavior without re-registering the callback
 *   (guarded by the provider instance).
 *
 * Individual abilities are never registered, so `Gate::has()` behavior
 * is unchanged.
 */
final class RuntimeAuthorization
{
    private bool $registered = false;

    public function __construct(
        private readonly Gate $gate,
        private readonly Container $container,
    ) {}

    /**
     * Register the Gate callback. Idempotent: the provider registers it
     * at most once per application lifecycle.
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        $this->gate->before(function ($user, string $ability, array $arguments = []): ?bool {
            if ($user === null) {
                return null;
            }

            $permissions = $this->container->make(PermissionRegistryContract::class);

            if (! in_array($ability, $this->registeredPermissionIds($permissions), true)) {
                return null;
            }

            $userId = (string) $user->getAuthIdentifier();

            return $this->container
                ->make(AuthorizationContract::class)
                ->identityHasPermission($userId, $ability);
        });
    }

    /**
     * Permission ids currently registered in the Foundation
     * `PermissionRegistry` across all enabled modules.
     *
     * @return string[]
     */
    private function registeredPermissionIds(PermissionRegistryContract $permissions): array
    {
        return array_map(
            static fn (PermissionDefinition $permission) => $permission->id,
            $permissions->all(),
        );
    }
}
