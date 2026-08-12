# Proposal: modmon-identity v1 — Portable Identity/Auth Platform Module

**Status:** Approved — 2026-08-12 (revised and approved; amendments to
ADR-0006 recorded). Implementation phases and tests in §19 and §21 are
authorized to proceed in a separate task.
**Date:** 2026-08-12
**Applies to:** Foundation Contract v1.0.0, Module Authoring Standard v1,
Portable Module Contract v1, Dependency Rules, ADR-0006

---

## 1. Summary

This proposal defines the smallest production-ready Identity v1 that
proves the portable-module architecture while remaining reusable across
future ModMon applications. It is a single installable platform module
(`modmon-identity`) that owns the user/auth domain per ADR-0006
(Strategy D): `users` and `password_reset_tokens` are adopted from the
Foundation 1.x host scaffolding, `sessions` remains Foundation-owned
infrastructure.

### 1.1 Revision — Decisions Applied (2026-08-12)

1.  **No `.env` mutation** during install/enable/disable. Auth model
    wiring happens at runtime from `IdentityServiceProvider` while the
    module is enabled. `AUTH_MODEL` remains an optional host override,
    not the module lifecycle mechanism.
2.  **`UserRegistered` removed.** Identity v1 publishes no events.
3.  **Admin Users UI/navigation scaffolding removed.**
4.  **`identity.users.*` and `identity.settings.*` permissions removed.**
5.  **No `identity_meta` table.** No state marker beyond Laravel's own
    migration tracking.
6.  **Minimal `identity:user:create` Artisan command** bootstraps users
    on a fresh host. Identity never calls the user "admin"; roles/admin
    semantics belong to future RBAC.

The accepted architecture is unchanged: capabilities `identity.user` and
`identity.authentication`, `UserQueryContract` + `UserReadModel`, schema
compatibility validation, `users`/`password_reset_tokens` ownership,
Foundation-owned `sessions`, login/logout/password reset, Foundation 1.x
adoption, and Foundation 2.x fresh-create behavior.

---

## 2. Design Goals and Non-Goals

### Goals

1.  Prove the portable-module architecture end to end: copy →
    `module:doctor` → `module:install` → configure → use, without host
    source edits.
2.  Provide working session authentication (login, logout, password
    reset) for ModMon hosts using Laravel 13's native auth machinery.
3.  Own `users` and `password_reset_tokens` per ADR-0006, with a safe,
    validated adoption path from the Foundation 1.x host scaffolding.
4.  Expose the smallest stable public contracts (capabilities,
    `UserQueryContract`, `UserReadModel`) that future platform modules
    (RBAC, Tenancy, Subscription) need.
5.  Preserve data across disable/enable; never destructively remove data
    (no uninstall in Foundation v1).
6.  Remain fully backward-compatible with Foundation v1.0.0 and the
    frozen v1.0.0 tag (no changes to runtime code, SDK, or the tag).

### Non-Goals

- RBAC, roles, permissions, authorization enforcement.
- Tenancy/workspaces and multi-tenant user scoping.
- Subscription/entitlements.
- User profiles beyond the auth-required fields.
- Social login, OAuth, 2FA, MFA, WebAuthn.
- API tokens / personal access tokens / Sanctum-style auth.
- Email verification enforcement.
- Account registration/public self-signup (a future concern).
- Admin/management UI and navigation in v1.
- Published events for workflows that do not yet exist.

---

## 3. Identity v1 Responsibilities (Exact)

Identity v1 is responsible for:

1.  **Ownership of the user/auth domain** — `users` and
    `password_reset_tokens` tables and the canonical `User` model
    (ADR-0006: "Identity becomes the authoritative and sole owner").
2.  **Legacy host scaffolding adoption** — on `module:install`, detect
    the Foundation 1.x host-created `users`/`password_reset_tokens`
    tables, validate schema compatibility (ADR-0006 Clarifications:
    detect-and-validate, not detect-and-assume), and record adoption
    through Laravel's migration tracking (the conditional migration runs
    exactly once).
3.  **Runtime auth wiring** — while the module is enabled,
    `IdentityServiceProvider` configures Laravel's `users` auth provider
    to resolve `Modules\Identity\Models\User`. No `.env` file is written
    at any lifecycle step. A host-provided `AUTH_MODEL` is respected as
    an optional override.
4.  **Session authentication flows** — login, logout, and password reset
    (request token, reset form, reset password) using Laravel's native
    `Auth`/`Password` facades.
5.  **Capability provision** — `identity.user` and
    `identity.authentication`.
6.  **Public contracts** — `UserQueryContract` and `UserReadModel` for
    read-only user lookup by other modules.
7.  **User bootstrapping** — a minimal `identity:user:create` Artisan
    command to create users on a fresh host. No roles, no admin
    semantics.
8.  **Compatibility reporting** — `module:doctor` must report clean
    diagnostics for a valid install.

## 4. What Explicitly Does NOT Belong in Identity v1

| Area | Reason |
|------|--------|
| Roles/permissions assignment, gates, policies, authorization | RBAC module |
| Tenant/workspace scoping of users | Tenancy module |
| Plan/entitlement gating | Subscription module |
| Extended profiles (avatar, bio, preferences) | Profile module (future) |
| Social login, OAuth, 2FA, MFA | Explicitly deferred |
| API tokens / personal access tokens | Deferred; no Foundation v1 support |
| Registration/public signup | Out of scope; no email verification infrastructure |
| Admin Users UI, navigation items, management pages | Removed in revision; nothing to manage yet |
| Published events | None in v1; do not publish events for non-existent workflows |
| `identity.users.*` / `identity.settings.*` permissions | Removed in revision; speculative |
| Session storage (`sessions` table) | Foundation-owned application infrastructure per ADR-0006 |
| Host `App\Models\User` | Never referenced by Identity |
| `identity_meta` marker table | Removed in revision; no concrete runtime requirement |
| `users` table in a fresh Foundation 2.x host | Created by Identity's own migration (see §11) |

---

## 5. Capabilities

### Provided

| Capability | Meaning |
|------------|---------|
| `identity.user` | A stable user domain is available (canonical User model, users table) |
| `identity.authentication` | Session login/logout/password-reset works on this host |

Future RBAC requires `identity.user`; Tenancy and Subscription require
`identity.user` (ADR-0006). Because the auth wiring is applied at
runtime while the module is enabled, the host's `web` guard works
without any `.env` edit.

### Required

*None.* Identity v1 must install on a bare compatible Foundation host.
This is what proves portability.

---

## 6. module.json Design

```json
{
    "schema": 1,
    "name": "Identity",
    "code": "identity",
    "version": "1.0.0",
    "type": "platform",
    "provider": "Modules\\Identity\\IdentityServiceProvider",
    "compatibility": {
        "php": "^8.3",
        "laravel": "^13.0",
        "foundation": "^1.0"
    },
    "requires": {
        "capabilities": []
    },
    "provides": [
        "identity.user",
        "identity.authentication"
    ]
}
```

- `requires.capabilities` empty → the module is the base of the platform
  chain (`Foundation → Identity → RBAC → Tenancy → Subscription`).
- No `requires.modules` (Foundation v1 limitation — capability-only).

---

## 7. Database / Table Ownership

Per ADR-0006 and the Authoring Standard §7 (module owns its tables):

| Table | Owner | Identity v1 action |
|-------|-------|--------------------|
| `users` | **Identity** (after adoption/creation) | Adopt existing (v1.x host) or create (fresh 2.x host) |
| `password_reset_tokens` | **Identity** | Adopt existing or create |
| `sessions` | **Foundation** | Never touched by Identity |

Migrations live in `Modules/Identity/Database/Migrations/` and run only
during explicit `module:install`. Table names stay `users` /
`password_reset_tokens` (not `identity_users`): ADR-0006 strategy D
requires adopting the existing schema so Laravel auth works unchanged.

**No `identity_meta` table.** Laravel's own migration tracking
(`migrations` table) guarantees the conditional adoption/creation
migration runs exactly once. Re-detection for diagnostics uses
`Schema::hasTable()` at `module:doctor` time.

---

## 8. Laravel 13 Authentication Integration

Identity uses Laravel's native machinery; it does not re-implement auth:

- **Guard/provider configuration files stay untouched.** `config/auth.php`
  keeps its defaults (`web` guard, `users` eloquent provider,
  `env('AUTH_MODEL', User::class)`).
- **Runtime provider configuration.** `IdentityServiceProvider::register()`
  sets `config(['auth.providers.users.model' =>
  Modules\Identity\Models\User::class])` while the module is enabled. If
  the host has explicitly set `AUTH_MODEL` in `.env`, that value is
  respected and Identity does not override it (optional host override).
- **Process-lifetime wiring.** The config change is in-memory only. On a
  request where Identity is not enabled (or after disable), the module
  provider is not booted and the config returns to the host default on
  the next request — there is nothing persistent to revert.
- **Auth flows** use `Auth::attempt()`, `Auth::logout()`, and
  `Password::broker('users')`; views are Blade under the `identity::`
  namespace.
- **Verification:** `MustVerifyEmail` is not implemented in v1
  (`email_verified_at` is preserved in the schema but unused).

---

## 9. User Model Ownership and AUTH_MODEL Strategy

- Identity ships `Modules\Identity\Models\User extends
  Illuminate\Foundation\Auth\User` (Authenticatable), with the same
  attribute set as the host model (name, email, password, casts incl.
  `password => 'hashed'`, `HasFactory`, `Notifiable`).
- Identity does **not** extend `App\Models\User` (ADR-0006: "Identity's
  model extends Authenticatable independently").
- **AUTH_MODEL is not the lifecycle mechanism.** It is never written by
  `module:install`, `module:enable`, or `module:disable`. It remains an
  optional host override: if the host sets it, the runtime config in
  §8 respects it.
- In a Foundation 2.x host there is no `App\Models\User`; the runtime
  provider configuration is the sole wiring while Identity is enabled.

---

## 10. Foundation v1 Legacy Adoption Strategy

Per ADR-0006, adoption is detect-validate-register, never
detect-and-assume:

### Install-time check (in the adoption migration, run by
`module:install`)

1.  Detect `Schema::hasTable('users')` and
    `Schema::hasTable('password_reset_tokens')`.
2.  If **neither** exists → create them (fresh Foundation 2.x path,
    §11). This also covers a v1.x host that somehow lacks scaffolding.
3.  If **both** exist → run schema compatibility validation (below). On
    success, skip `Schema::create` and record the adoption as completed
    — the migration is tracked by Laravel's `migrations` table, so it
    never runs again. On failure → abort install with a diagnostic; the
    host developer resolves the schema difference and retries.
4.  Mixed case (one exists, one does not) → treat as invalid state:
    abort with a diagnostic. Do not create just one.

### No data migration

Existing rows are adopted as-is. No column is added, dropped, or
re-typed. `users` rows with `password` hashes remain valid because the
auth provider uses the same `users` table and the same hashing algorithm
(config `BCRYPT_ROUNDS`).

### Schema compatibility checks required before adoption

The validator (`Infrastructure/Adoption/UsersTableSchemaValidator`)
checks, for each existing table, the exact columns ADR-0006 requires:

| Table | Required columns |
|-------|------------------|
| `users` | `id`, `name`, `email` (unique-capable), `password`, `remember_token`, `email_verified_at`, `created_at`, `updated_at` |
| `password_reset_tokens` | `email` (primary), `token`, `created_at` |

Column types must be compatible (e.g., `id` integer, `email` string).
Any mismatch or missing column aborts install with a diagnostic naming
the incompatibility.

---

## 11. Fresh Foundation 2.x Installation Behavior

On a fresh Foundation 2.x host (no host user scaffolding):

- `module:install identity` creates `users` and
  `password_reset_tokens` via Identity-owned migrations (standard
  Laravel `create_users_table`-equivalent, with `id`, `name`, `email`
  unique, `email_verified_at` nullable, `password`, `rememberToken()`,
  `timestamps()`).
- Auth wiring is the runtime provider configuration of §8; no `.env`
  change.
- `sessions` is created by the Foundation host migration (Foundation
  owns it), exactly as today.
- No adoption check runs because the tables do not exist.

The migration file is written so the same file works on both v1.x
(adopt) and 2.x (create) hosts — branch on `Schema::hasTable()` inside
the migration's `up()`.

---

## 12. Login / Logout / Password Reset Responsibilities

| Flow | Responsibility | Mechanism |
|------|----------------|-----------|
| Login | Identity | `Auth::attempt()`, session guard, redirect to `dashboard` |
| Logout | Identity | `Auth::logout()` + invalidate session + regenerate CSRF token |
| Password reset request | Identity | `Password::sendResetLink()` (email via `Notifications`), rate-limited |
| Password reset | Identity | `Password::reset()` with validated token, hash stored by cast |

Routes (see §13) are registered by the provider via
`ContributesRoutes`, mounted with the `web` middleware group (Foundation
limitation: all module routes use `web`).

---

## 13. Routes and Middleware

All under the module code prefix `identity` and the `identity::` view
namespace:

| Method | URI | Name | Middleware | Description |
|--------|-----|------|------------|-------------|
| GET | `/login` | `identity.login` | `guest` | Show login form |
| POST | `/login` | `identity.login.submit` | `guest` | Authenticate |
| POST | `/logout` | `identity.logout` | `auth` | Logout (CSRF) |
| GET | `/forgot-password` | `identity.password.request` | `guest` | Password reset request form |
| POST | `/forgot-password` | `identity.password.email` | `guest` | Send reset link |
| GET | `/reset-password/{token}` | `identity.password.reset` | `guest` | Reset form |
| POST | `/reset-password` | `identity.password.update` | `guest` | Apply reset |

- All routes use the `web` middleware group (Foundation loads module
  routes through `Route::middleware('web')`).
- Login/reset routes are `guest`; logout is `auth`.
- **No admin/management routes in v1.** There is no Users list/detail
  UI and no management navigation.

---

## 14. Contracts / DTOs / Events Exposed to Other Modules

### Contracts (in `Modules/Identity/Domain/Contracts/`)

| Contract | Method(s) | Purpose |
|----------|-----------|---------|
| `UserQueryContract` | `findById(int $id): ?UserReadModel`; `findByEmail(string $email): ?UserReadModel`; `exists(string $email): bool` | Read-only user lookup for RBAC/Tenancy/Subscription without model imports |

`UserReadModel` is a small immutable DTO:

```
Modules\Identity\Domain\ReadModels\UserReadModel
  - id: int
  - name: string
  - email: string
  - emailVerifiedAt: ?DateTimeImmutable
  - createdAt: ?DateTimeImmutable
```

No Eloquent model, password, or token ever crosses the module boundary.

### Events

**None in v1.** Identity does not publish events for workflows that do
not yet exist (e.g., user registration). When real workflows arrive
(e.g., a future registration flow), a `user.created`-style event can be
added in a later minor version — adding a new event is backward
compatible.

### DTOs shared across boundaries

Only `UserReadModel` in v1. No raw arrays or Eloquent models.

---

## 15. Boundaries for Future Modules

| Future module | Depends on | Boundary |
|---------------|------------|----------|
| RBAC | `identity.user` | consumes `UserQueryContract`; consumes permissions through the Foundation `PermissionRegistry` (module-agnostic — Identity declares no permissions in v1) |
| Tenancy | `identity.user` | `UserQueryContract`; owns `tenancy.*` capabilities and its own tables |
| Subscription | `identity.user` | `UserQueryContract`; own tables |
| Profile | `identity.user` (optional) | own `profiles` table referencing `users.id` as plain integer, no DB FK where possible (Authoring Standard §7) |

Cross-module rules applied:

- No importing Identity's internal Eloquent models.
- No `users` table queries from other modules.
- All reads through `UserQueryContract`.
- `user_id` columns in other modules' tables are plain integers/UUIDs
  without DB-level FK where possible.

---

## 16. Enable / Disable Semantics

### Disable

`module:disable identity` (Foundation `ModuleManager::disable()`):

1.  Unregisters capabilities (`identity.user`, `identity.authentication`).
2.  Routes of the module stop loading on subsequent requests (Foundation
    limitation: current-request routes stay active).
3.  The runtime auth provider configuration of §8 is not applied on
    subsequent requests because the module provider is no longer booted;
    auth falls back to the host default model. There is no `.env` value
    to revert.
4.  **Data is preserved**: `users`, `password_reset_tokens`, and any
    Identity-owned rows are untouched.
5.  Dependent modules (RBAC/Tenancy/Subscription) that require
    `identity.user` will fail dependency resolution on enable while
    Identity is disabled (DependencyResolver). This is correct behavior.

### Enable

1.  Re-validates dependencies (`requires.capabilities` empty → always
    passes).
2.  Restores capabilities and routes, and reapplies the runtime auth
    provider configuration.

---

## 17. Uninstall / Data Preservation Expectations

- Foundation v1 has **no `module:uninstall`** (Authoring Standard
  Appendix B; ADR-0006 "one-way transfer"). Identity v1 therefore never
  drops `users`/`password_reset_tokens`.
- If Identity is removed from a host, tables remain (they contain
  production data); auth falls back to the host default model
  configuration on the next request (Foundation 1.x hosts keep working
  auth; Foundation 2.x hosts have no auth until Identity or another
  identity provider is enabled — correct per ADR-0006).
- No destructive removal is implemented or planned in v1.

---

## 18. Security Requirements

1.  Passwords hashed via Laravel's `hashed` cast / `Hash::make()`
    (bcrypt, `BCRYPT_ROUNDS=12` default); never stored in plaintext.
2.  All auth forms are CSRF-protected (Laravel `web` group).
3.  Login rate limiting (`ThrottleRequests` on login submit, e.g.
    5/minute).
4.  Password reset tokens use Laravel's `Password::broker()` (one-time,
    expiring, throttled) — never hand-rolled tokens.
5.  Session fixation protection: `session()->regenerate()` on login;
    `invalidate()` + `regenerateToken()` on logout.
6.  **No `.env` mutation** at any lifecycle step. Auth model wiring is
    runtime-only configuration; `AUTH_MODEL` stays an optional host
    override and is never written by the module.
7.  No management routes exist in v1; nothing to gate beyond the auth
    middleware.
8.  No secrets, keys, or credentials in `module.json`, README, or code.
9.  Email addresses unique (DB unique index on `users.email`).
10. `identity:user:create` hashes the password via the model cast,
    enforces the unique email, and never assigns roles or admin flags.

---

## 19. Testing Strategy

Module tests live in `Modules/Identity/Tests/` (runnable via
`php artisan test --filter="Modules\\\\Identity"` or a dedicated
`Modules` testsuite added to `phpunit.xml` later):

| Test class | Covers |
|------------|--------|
| `Unit/IdentityManifestTest` | module.json valid against `ManifestValidator`; compatibility declared |
| `Unit/IdentityModelTest` | User model casts, fillable/hidden, Authenticatable contract |
| `Unit/UserReadModelTest` | DTO immutability |
| `Architecture/IdentityBoundaryTest` | No import of `App\Models\User`; no cross-module model imports; no host source edits; `.env` file untouched by install/enable/disable |
| `Feature/IdentityInstallTest` | install on a clean host (fresh DB): tables created, capabilities registered, module enabled, auth provider resolves to Identity's User at runtime, `.env` unchanged |
| `Feature/IdentityAdoptionTest` | v1.x host with existing `users`/`password_reset_tokens`: adoption succeeds, no data loss, tables not recreated |
| `Feature/IdentityAdoptionSchemaMismatchTest` | incompatible schema → install aborts with diagnostic |
| `Feature/IdentityLoginTest` | login success/failure, redirect, session regeneration |
| `Feature/IdentityLogoutTest` | logout invalidates session |
| `Feature/IdentityPasswordResetTest` | reset request + reset flow |
| `Feature/IdentityUserCreateCommandTest` | `identity:user:create` creates a user, hashes password, enforces unique email, assigns no roles |
| `Feature/IdentityDisableEnableTest` | disable preserves data and removes capabilities; enable restores them; auth wiring applied only while enabled |
| `Architecture/IdentityPortabilityTest` | copy → discover → doctor → install in a clean Foundation host clone |

Foundation's own suite (106 tests) must remain green; no Foundation test
is modified.

---

## 20. Portability Verification Strategy

The strongest proof (Authoring Standard §18) is a clean-host install:

1.  `git clone` a compatible ModMon Foundation v1 host (fresh clone or
    CI job).
2.  `composer install && npm install && php artisan migrate` (host
    creates `users`, `password_reset_tokens`, `sessions`).
3.  Copy `Modules/Identity/` into `Modules/`.
4.  `php artisan module:doctor identity` → all checks pass.
5.  `php artisan module:install identity` → adoption succeeds; state
    `enabled`; capabilities registered; `.env` byte-identical to before.
6.  `php artisan module:list` shows Identity as installed/enabled.
7.  Smoke: `identity:user:create` bootstraps a user; login flow renders
    and authenticates (session DB storage).
8.  Disable → data preserved, auth falls back to host default on next
    request; enable → restored.
9.  Run `Modules/Identity/Tests` in the clean host.

No unrelated host source file is edited during this whole sequence.

---

## 21. Implementation Phases

### Phase 1 — Skeleton and Manifest

- `Modules/Identity/module.json` (per §6), `IdentityServiceProvider`
  implementing `ContributesRoutes` (no routes yet), README from
  template.
- `module:doctor identity` green; module discovered.

### Phase 2 — Model and Public Contracts

- `Models/User`, `Domain/Contracts/UserQueryContract`,
  `Domain/ReadModels/UserReadModel`.
- Bind `UserQueryContract` in `register()`.
- Unit tests (`IdentityModelTest`, `UserReadModelTest`,
  `IdentityManifestTest`).

### Phase 3 — Migrations, Adoption, and Auth Wiring

- Migrations for `users` + `password_reset_tokens` (conditional create
  vs adopt), no `identity_meta`.
- `Infrastructure/Adoption/UsersTableSchemaValidator`.
- Runtime auth provider configuration in
  `IdentityServiceProvider::register()` (respecting an optional
  `AUTH_MODEL` host override).
- `IdentityInstallTest`, `IdentityAdoptionTest`,
  `IdentityAdoptionSchemaMismatchTest`, `IdentityBoundaryTest` (`.env`
  untouched).

### Phase 4 — Auth Flows and CLI

- Controllers, routes, views (login/logout/password reset), mailables.
- `identity:user:create` Artisan command (no roles/admin semantics).
- Session regeneration, rate limiting, CSRF.
- `IdentityLoginTest`, `IdentityLogoutTest`,
  `IdentityPasswordResetTest`, `IdentityUserCreateCommandTest`.

### Phase 5 — Lifecycle Polish

- README finalization (README contract §16 of Authoring Standard).
- `IdentityDisableEnableTest`.
- Architecture boundary tests finalized.

### Phase 6 — Portability Proof and Docs

- Clean-host portability run (CI or manual).
- `docs/current-state.md` update; compliance report
  `docs/reports/identity-compliance-v1.md`; supersede nothing.
- Full Foundation suite + module suite green.

---

## 22. Decisions

### Resolved by this revision

| # | Decision | Resolution |
|---|----------|------------|
| 1 | `AUTH_MODEL` write mechanism | **No `.env` mutation.** Runtime auth provider configuration from `IdentityServiceProvider`; `AUTH_MODEL` is an optional host override only |
| 2 | `identity_meta` marker | **Not created.** Laravel migration tracking + `Schema::hasTable()` detection suffice |
| 3 | `UserRegistered` event | **Removed.** No events in v1 |
| 4 | Admin Users UI/navigation | **Removed.** No management routes/pages in v1 |
| 5 | `identity.users.*` / `identity.settings.*` permissions | **Removed.** Identity declares no permissions in v1 |
| 6 | Admin bootstrap | Replaced by **`identity:user:create`**; no "admin" naming, no roles |
| 7 | Password reset email transport | **Host `config/mail.php` defaults.** No Identity-owned mail config |
| 8 | `email_verified_at` | **Preserved** in the schema (compatibility requirement); `MustVerifyEmail` **not enforced** in v1 |

### Amendment recorded in ADR-0006

The ADR-0006 mechanism conflict is resolved. ADR-0006 is amended
(2026-08-12) to replace the mandatory `AUTH_MODEL` environment-write
mechanism with the runtime auth-provider wiring rule; Strategy D and
all other ADR-0006 decisions are untouched. See
`docs/adr/0006-identity-module-users-table-ownership.md`,
"Amendment (2026-08-12) — Runtime Auth-Provider Wiring Replaces the
Mandatory AUTH_MODEL Environment Write".

---

## 23. Risks

1.  **Runtime config timing edge.** If the `users` auth provider is
    resolved before Identity's provider registers, the config change
    does not apply within that process. Mitigated by setting config in
    `register()` (before guards resolve) and covered by tests.
2.  **Same-process disable timing.** Auth wiring stays active for the
    current request after `disable()` — the same class of known
    Foundation v1 limitation as routes. Subsequent requests are correct.
3.  **Conditional adoption migration complexity.** One migration must
    behave correctly on v1.x (adopt) and 2.x (create) hosts. Mitigated
    by the schema validator and by Laravel's migration tracking.
4.  **No events and no permissions in v1.** Later minor versions can add
    both backward-compatibly; nothing in the public surface is
    prematurely locked.
5.  **Class autoloading of `Modules\Identity\...` after copy.** Works
    via PSR-4 (`Modules\` → `Modules/`); verified by the existing
    `module:make` provider test pattern.

---

## 24. Files Changed by This Proposal

This proposal itself is the only deliverable of this task:

- `docs/proposals/identity-v1.md` (revised — this document)

No runtime code, no module code, no other docs were changed. Nothing was
committed, pushed, or tagged.

---

## 25. Appendix — Relevant ADR-0006 Excerpts Applied

- Strategy D: Identity owns the user/auth domain; host scaffolding is
  transitional residue.
- Foundation 1.x: adopt existing tables (validate schema first); no data
  migration; host migration becomes inert.
- Foundation 2.x: Identity creates the tables; host has no scaffolding.
- `sessions` is Foundation-owned infrastructure with an optional
  coupling to Identity via nullable `user_id`.
- Downstream modules declare `identity.user` as a required capability.
- The auth wiring point is a configuration change, not a source edit.
  This revision implements it as runtime auth provider configuration
  (see §22, decision 4) rather than an `.env` write.
- Identity's model extends `Authenticatable` independently, never
  `App\Models\User`.
