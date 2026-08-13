# Identity

Portable platform module that owns the user/auth domain for ModMon
hosts: the `users` and `password_reset_tokens` tables, the canonical
User model, and runtime Laravel auth-provider wiring (ADR-0006
Strategy D).

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
```

The migration adopts existing `users`/`password_reset_tokens` tables on
a Foundation 1.x host (after strict schema validation) or creates them
fresh on a Foundation 2.x host. Existing user data is never modified.
The `sessions` table is Foundation-owned and is never touched.

## Configuration

No static configuration required.

### Environment Variables

| Variable    | Description                                                              | Default     |
|-------------|--------------------------------------------------------------------------|-------------|
| `AUTH_MODEL` | Optional explicit host override for the auth model. When set, it takes precedence over Identity's runtime wiring. | *(unset — Identity wires its own model)* |

### Runtime Settings

Requires modmon-settings (not yet available in Foundation v1).

## Permissions

*None* — Identity v1 declares no permissions. RBAC (future) will consume
permissions through the module-agnostic Foundation `PermissionRegistry`.

## Routes

| Method | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/login` | `identity.login` | `guest` |
| POST | `/login` | `identity.login.submit` | `guest`, `throttle:5,1` |
| POST | `/logout` | `identity.logout` | `auth` |
| GET | `/forgot-password` | `identity.password.request` | `guest` |
| POST | `/forgot-password` | `identity.password.email` | `guest` |
| GET | `/reset-password/{token}` | `identity.password.reset` | `guest` |
| POST | `/reset-password` | `identity.password.update` | `guest` |

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

| Table                  | Owner    | Description                                  |
|------------------------|----------|----------------------------------------------|
| `users`                | Identity | Canonical user table (adopted or created).   |
| `password_reset_tokens`| Identity | Password reset tokens (adopted or created).  |
| `sessions`             | Foundation | Session storage; Identity never touches it. |

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
| Model behavior | ✓ |
| UserReadModel | ✓ |
| UserQueryContract binding | ✓ |
| Fresh-table creation | ✓ |
| Legacy adoption (compatible) | ✓ |
| Preservation of existing data | ✓ |
| Incompatible schema rejection | ✓ |
| Partial-table-state rejection | ✓ |
| Runtime auth provider wiring | ✓ |
| AUTH_MODEL host override | ✓ |
| `.env` untouched | ✓ |
| Architecture boundary | ✓ |
| Login / logout / password reset | ✓ |
| Guest redirect portability (no host edit) | ✓ |
| `identity:user:create` | ✓ |

## Version History

| Version | Foundation | Description                                  |
|---------|------------|----------------------------------------------|
| 1.0.0   | ^1.0       | Phases 1–4: ownership/adoption, runtime auth wiring, session auth flows, `identity:user:create`. |
