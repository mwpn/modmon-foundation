# Identity

Portable platform module that owns the user/auth domain for ModMon
hosts: the `users` and `password_reset_tokens` tables, the canonical
User model, runtime Laravel auth-provider wiring, and session
authentication flows (login, logout, password reset) per ADR-0006
Strategy D.

## Type

`platform`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability                | Description                                                       |
|---------------------------|-------------------------------------------------------------------|
| `identity.user`           | A stable user domain is available (canonical User model, users table). |
| `identity.authentication` | Session authentication (login/logout/password reset) is provided by this host. |

## Requires

*None* — this module must install on a bare compatible Foundation host.
It is the base of the platform chain
(`Foundation → Identity → RBAC → Tenancy → Subscription`).

## Optional Integrations

*None* — this module has no optional integrations in v1.

## Installation

```bash
# Copy the module into a compatible ModMon Foundation host
cp -r modmon-identity/Modules/Identity /path/to/host/Modules/Identity

# Verify compatibility
php artisan module:doctor identity

# Install and enable
php artisan module:install identity

# Bootstrap a user on a fresh host (optional)
php artisan identity:user:create \
  --name="Site Owner" \
  --email=owner@example.com \
  --password="your-secure-password"
```

The migration adopts existing `users`/`password_reset_tokens` tables on
a Foundation 1.x host (after strict schema validation) or creates them
fresh on a Foundation 2.x host. Existing user data is never modified.
The `sessions` table is Foundation-owned and is never touched.

### Disable / Enable

```bash
php artisan module:disable identity
php artisan module:enable identity
```

- **Disable** unregisters capabilities; subsequent requests do not load
  Identity routes, auth wiring, guest redirect, or
  `identity:user:create`. Data in `users` /
  `password_reset_tokens` is preserved. There is no `.env` value to
  revert.
- **Enable** restores capabilities, routes, runtime auth wiring, and
  the CLI command.
- Foundation v1 has **no `module:uninstall`**; Identity never drops its
  tables.

## Configuration

### Static Configuration

No static configuration required. Guard/provider files
(`config/auth.php`) stay untouched; Identity wires the auth model at
runtime while enabled.

### Environment Variables

| Variable    | Description                                                              | Default     |
|-------------|--------------------------------------------------------------------------|-------------|
| `AUTH_MODEL` | Optional explicit host override for the auth model. When set, it takes precedence over Identity's runtime wiring. | *(unset — Identity wires its own model)* |

Identity never writes `.env` during install, enable, or disable.

### Runtime Settings

Requires modmon-settings (not yet available in Foundation v1).

## Permissions

*None* — Identity v1 declares no permissions. RBAC (future) will consume
permissions through the module-agnostic Foundation `PermissionRegistry`.

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/login` | `identity.login` | Show the login form |
| POST | `/login` | `identity.login.submit` | Authenticate (throttle: 5/minute) |
| POST | `/logout` | `identity.logout` | Log out and invalidate the session |
| GET | `/forgot-password` | `identity.password.request` | Password reset request form |
| POST | `/forgot-password` | `identity.password.email` | Send password reset link |
| GET | `/reset-password/{token}` | `identity.password.reset` | Password reset form |
| POST | `/reset-password` | `identity.password.update` | Apply the new password |

While Identity is enabled, the module wires Laravel's auth middleware
guest redirect to `identity.login` at runtime (no `bootstrap/app.php`
edit, no global `login` route-name alias). When Identity is disabled, a
fresh request process does not load the provider, so Foundation falls
back to Laravel's default and never depends on `identity.login`.

## Events Published

*None* — Identity v1 publishes no events.

## Events Consumed

*None* — Identity v1 consumes no external events.

## Public Contracts

| Contract                                            | Description                                           |
|-----------------------------------------------------|-------------------------------------------------------|
| `Modules\Identity\Domain\Contracts\UserQueryContract` | Read-only user lookup for RBAC/Tenancy/Subscription without importing Identity internals. |

Consumers use `UserReadModel` (immutable DTO). No Eloquent model,
password, or token crosses the module boundary.

## Database Ownership

| Table                  | Owner      | Description                                  |
|------------------------|------------|----------------------------------------------|
| `users`                | Identity   | Canonical user table (adopted or created).   |
| `password_reset_tokens`| Identity   | Password reset tokens (adopted or created).  |
| `sessions`             | Foundation | Session storage; Identity never touches it.  |

### Cross-Module References

No cross-module database references in v1. Future modules reference
`users.id` as a plain integer without database-level foreign keys where
possible.

## Navigation Contributions

*None* — Identity v1 contributes no navigation items.

## Dashboard Contributions

*None* — Identity v1 contributes no dashboard widgets.

## Testing

```bash
php artisan test --filter="Modules\\Identity"
```

### Test Coverage

| Area | Status |
|------|--------|
| Manifest validation | ✓ |
| Compatibility declaration | ✓ |
| Discovery | ✓ |
| Installation | ✓ |
| Capability registration | ✓ |
| Routes | ✓ |
| Migrations (fresh create + legacy adopt) | ✓ |
| Contributions (routes/views/commands; no nav/widgets/permissions) | ✓ |
| Disable / enable | ✓ |
| Data preservation | ✓ |
| Architecture boundary | ✓ |
| Runtime auth provider wiring | ✓ |
| AUTH_MODEL host override | ✓ |
| `.env` untouched | ✓ |
| Login / logout / password reset | ✓ |
| Guest redirect portability (no host edit) | ✓ |
| `identity:user:create` | ✓ |
| Disable / enable lifecycle | ✓ |
| Portability proof (clean-host + suite) | ✓ |

## Version History

| Version | Foundation | Description                                  |
|---------|------------|----------------------------------------------|
| 1.0.0   | ^1.0       | Phases 1–6 complete: portable Identity/Auth platform module (ownership, auth flows, lifecycle, clean-host portability proof). |
