# ADR-0006 — Identity Module Users-Table Ownership

Status: Accepted

## Context

Foundation v1.0.0 ships with Laravel's default user scaffolding in the
host application:

```
database/migrations/0001_01_01_000000_create_users_table.php
  → creates: users, password_reset_tokens, sessions

app/Models/User.php
  → extends Illuminate\Foundation\Auth\User (Authenticatable)

config/auth.php
  → 'model' => env('AUTH_MODEL', App\Models\User::class)
  → guards, providers, password brokers all wired to this model/table
```

The Module Authoring Standard v1 (section 7) states that "the module
that creates a table owns that table" and identifies an unresolved
ownership conflict: when `modmon-identity` is implemented, it must
resolve who owns the user/auth domain.

Foundation v1 has zero references to `App\Models\User`, `Auth::`, or
any authentication mechanism in its Runtime, SDK, Experience, or
Infrastructure layers. The `FoundationServiceProvider` does not touch
auth. No existing module references the User model.

This ADR evaluates four strategies against eight criteria.

### The Architectural Tension

Two locked requirements create a genuine tension:

**R5** declares that Identity/Auth is an *installable platform module,
not a mandatory foundation internal*.

Yet Foundation v1.0.0 ships with a functioning user/auth domain: a
`users` migration, a User model, auth configuration, password reset
tokens, and session management — all in the host. This is not
Foundation infrastructure in the Runtime/SDK/Experience sense; it is
Laravel's default application scaffolding that was never removed during
Foundation v1 development because no module existed to replace it.

The question is not merely "who owns the `users` table" but "does the
Foundation host own an identity domain, or is the identity domain
entirely the responsibility of an installable module?"

### The Capability Architecture Question

Under the capability-driven architecture (R9, ADR-0003), modules that
need user identity should declare that dependency through
`requires.capabilities: ["identity.user"]` — not by silently assuming
a `users` table exists. If RBAC requires a user concept, it should
require the `identity.user` capability. If Tenancy assigns tenants to
users, it should require `identity.user`.

Any strategy that allows downstream modules to reference `user_id`
without requiring `identity.user` creates an implicit dependency on
host scaffolding that bypasses the capability system. This is
architecturally equivalent to importing another module's Eloquent
model — it works, but it violates the declared integration pattern.

## Strategies

### Strategy A — Claim the `users` Table

Move the host migration and `App\Models\User` into `modmon-identity`.
Delete the host migration. Identity owns `users`,
`password_reset_tokens`, and `sessions`. The host `config/auth.php`
points to Identity's User model.

### Strategy B — Work Alongside the Host Table

Leave the host migration, `App\Models\User`, and `config/auth.php`
exactly as they are. Identity creates additional tables
(`identity_profiles`, etc.) and accesses the host `users` table through
a contract. Identity enhances but does not replace the host auth domain.

### Strategy C — Create a Separate `identity_users` Table

Identity creates its own `identity_users` table. The host `users` table
remains. Auth configuration is re-wired to Identity's model. A data
migration strategy maps existing users to the new table.

### Strategy D — Identity Owns the User/Auth Domain; Host Scaffolding is Residue

Recognize that the host's user scaffolding (`users` migration,
`App\Models\User`, auth config wiring) is *architectural residue* from
Laravel's default project template — not an intentional Foundation
design decision. It was never removed because Foundation v1 had no
Identity module to replace it.

Under this strategy:

1. `modmon-identity` owns the user/auth domain: the `users` table,
   `password_reset_tokens`, the User model, auth guard/provider
   configuration, registration, login, and profile management. The
   `sessions` table is application infrastructure with an optional
   coupling to Identity (see Clarifications).

2. The host scaffolding is explicitly marked as **transitional
   compatibility infrastructure** that exists only until Identity is
   installed. It is documented as residue, not as a Foundation contract.

3. A versioned migration path removes the host scaffolding:
   - Foundation 1.1 deprecates the host user scaffolding and documents
     Identity as the canonical owner.
   - Foundation 1.1's `module:install identity` includes a migration
     that detects and validates the existing `users` and
     `password_reset_tokens` tables' schema compatibility before
     adopting them (no data migration — same tables, transferred
     ownership upon successful validation).
   - Foundation 2.0 removes the host scaffolding entirely. A Foundation
     2.0 host without Identity has no `users` table and no auth — which
     is correct, because Identity is an installable module.

4. Downstream modules (RBAC, Tenancy, Subscription) declare
   `requires.capabilities: ["identity.user"]` as the capability
   architecture demands. They do not silently assume a `users` table
   exists.

## Evaluation

### 1. True Module Portability

**A (Claim):** Poor. Requires host file deletion during installation.
Violates the portable module contract.

**B (Alongside):** Surface-level good. Identity installs without host
edits. But Identity does not truly own its domain — it is a supplement
to a host-owned identity system. The module is portable in the
filesystem sense but not in the domain-ownership sense.

**C (Separate table):** Moderate. Module is self-contained but creates
redundancy and requires auth re-wiring (a host edit).

**D (Residue):** Good within the migration path. In Foundation 1.1,
Identity installs portably: the host scaffolding is documented as
transitional, and `module:install identity` adopts the existing table
without file deletion — the host migration remains on disk but is
inert (its tables already exist). In Foundation 2.0, Identity is fully
portable because the host has no user scaffolding to conflict with.

### 2. Clean Database Ownership

**A (Claim):** Theoretically clean but mechanically fragile. The table
exists before the module arrives; adoption requires conditional logic.

**B (Alongside):** Muddled. The `users` table is owned by the host but
semantically belongs to the Identity domain. `identity_profiles` has a
`user_id` that references a table Identity does not own. This is a
permanent ownership exception with no migration path toward
resolution.

**C (Separate table):** Creates two user tables. The `sessions` table
still references `users.id`. Ambiguous for downstream modules.

**D (Residue):** Clean once the migration completes. In Foundation 1.1,
Identity adopts the existing `users` table — ownership transfers from
host to module via an explicit documented step. In Foundation 2.0,
Identity creates the table from scratch (no host migration exists).
There is one owner at every point in the lifecycle.

### 3. Compatibility with Laravel 13 Authentication

**A (Claim):** Complex. Must reproduce Laravel's default auth
bootstrapping.

**B (Alongside):** Native compatibility — but at the cost of the host
permanently owning the auth model. Identity can never fully control
authentication (login flows, password policies, session management)
because the host model and config remain authoritative.

**C (Separate table):** Requires full auth re-wiring. `sessions` and
`password_reset_tokens` still reference the old table.

**D (Residue):** In Foundation 1.1, Identity provides its own User
model that extends `Authenticatable`. The host `config/auth.php`
already supports `env('AUTH_MODEL')` — Identity's install sets this
environment variable (a configuration change, not a source edit).
Laravel's guards, password resets, and sessions work because Identity
uses the same `users` table with the same schema. In Foundation 2.0,
Identity owns the auth config directly.

### 4. Ability to Use ModMon Without Identity Installed

**A (Claim):** Broken. No `users` table without Identity.

**B (Alongside):** Works. The host has a functioning auth system. But
this means the Foundation ships a user/auth domain as a built-in,
contradicting R5.

**C (Separate table):** Partial. Host auth works, but uninstalling
Identity after it was installed leaves broken auth config.

**D (Residue):** In **Foundation 1.x**, the host scaffolding remains
as documented transitional infrastructure. A host without Identity has
basic Laravel auth — the same as today. The difference from Strategy B
is *intent and documentation*: this is explicitly marked as temporary
compatibility, not as a permanent Foundation feature.

In **Foundation 2.0**, a host without Identity has no auth. This is
*correct* per R5: Identity is an installable module, not a foundation
internal. A Foundation 2.0 application that needs authentication
installs `modmon-identity`. This parallels how a Foundation host
without `modmon-rbac` has no role/permission system — that is not a
deficiency; it is the architecture working as designed.

### 5. Future RBAC / Tenancy / Subscription Composition

**A (Claim):** Rigid. Every platform module requires Identity.

**B (Alongside):** Appears composable but is architecturally
inconsistent. If RBAC references `user_id` without requiring
`identity.user`, it depends on the host's implicit user scaffolding —
this is a hidden dependency that bypasses the capability system. If the
host scaffolding were ever removed (or if a host were created without
it), RBAC would silently break because no capability check guards the
assumption.

The alternative — RBAC requires `identity.user` even under Strategy B —
is technically possible but creates an odd situation where RBAC requires
`identity.user`, which is provided by a module that merely enhances a
host-owned table. The capability would mean "the host has a users table
and Identity has enhanced it" rather than "Identity provides the
authoritative user domain."

**C (Separate table):** Ambiguous. Which `user_id` is canonical?

**D (Residue):** Architecturally clean. RBAC, Tenancy, and Subscription
declare `requires.capabilities: ["identity.user"]` because they
genuinely require an Identity module to function. In Foundation 1.x,
this means Identity must be installed first — which is the natural
platform module dependency chain documented in AGENTS.md and
PROJECT_INSTRUCTIONS.md:

```
Foundation → Identity → RBAC → Tenancy → Subscription
```

This is not a limitation; it is the composition model working correctly.
No platform module silently depends on host scaffolding. Every
dependency is declared, validated at install time, and enforced by the
capability system.

### 6. Fresh-Host Installation

**A (Claim):** Complex. Non-standard Laravel app or conditional
migration logic.

**B (Alongside):** Simple. Standard `php artisan migrate`, then
`module:install identity`. But the `users` table exists twice
conceptually — once as host infrastructure, once as Identity's domain
concern.

**C (Separate table):** Redundant tables on fresh install.

**D (Residue):** In Foundation 1.x: `php artisan migrate` creates the
host scaffolding tables (including `users`). `module:install identity`
validates their schema compatibility and adopts them — no new table
creation, no data migration. The adoption is a detect-validate-register
operation: Identity's migration confirms the existing tables match the
expected schema, skips `Schema::create`, and records ownership.

In Foundation 2.0: `php artisan migrate` does not create `users` or
`password_reset_tokens` (the host migration is restructured or
removed). `module:install identity` creates `users` and
`password_reset_tokens` via its own migrations. `sessions` remains
Foundation infrastructure, created by a Foundation migration. Clean.

### 7. Upgrade / Migration Safety

**A (Claim):** Risky. Data migration of production users table.

**B (Alongside):** Safe for data but permanently defers the ownership
question. There is no upgrade path from "host owns users" to "Identity
owns users" because Strategy B declares the host ownership as permanent.

**C (Separate table):** Risky. Production data migration.

**D (Residue):** Safe when staged correctly:

- **v1.0 → 1.1:** No data migration. The `users` table stays where it
  is. Identity's migration detects the existing tables and validates
  their schema compatibility (see Clarifications). If validation
  passes, Identity adopts the tables and becomes their authoritative
  owner — no schema changes, no data migration. If validation fails,
  the migration aborts with a diagnostic. The host migration file is
  not deleted; it becomes inert (its tables already exist, so
  re-running migrate does nothing). The host scaffolding files
  (`App\Models\User`, `config/auth.php` defaults) are deprecated in
  documentation.

- **1.x → 2.0:** The host migration file and `App\Models\User` are
  removed from the Foundation template. Existing installations that
  have Identity installed are unaffected (Identity's migration handles
  table creation). Existing installations upgrading to 2.0 without
  Identity would lose their users migration file — but this is a major
  version boundary, and the upgrade guide would state: "Install
  modmon-identity before upgrading to Foundation 2.0, or manually
  retain your users migration."

### 8. Minimum Host Coupling

**A (Claim):** High. Requires host file deletion.

**B (Alongside):** Low host coupling for installation, but *permanent
conceptual coupling*: the Foundation host is forever responsible for
the identity domain. This means Foundation is not truly
domain-agnostic — it has an opinion about users and authentication
baked into its host template.

**C (Separate table):** Moderate. Auth config must be changed.

**D (Residue):** Decreasing over time:

- Foundation 1.x: same coupling as Strategy B (host scaffolding
  exists), but documented as transitional. Identity installation
  requires only `AUTH_MODEL` env var change.
- Foundation 2.0: zero host coupling. The Foundation template has no
  opinion about users or authentication. Identity is a pure module.

## Evaluation Summary

| Criterion                     | A (Claim) | B (Alongside) | C (Separate) | D (Residue) |
|-------------------------------|-----------|---------------|--------------|-------------|
| True module portability       | Poor      | Surface only  | Moderate     | **Good (staged)** |
| Clean database ownership      | Fragile   | Muddled       | Redundant    | **Clean (staged)** |
| Laravel 13 auth compat        | Complex   | Native        | Re-wiring    | **Native (1.x), Owned (2.0)** |
| ModMon without Identity       | Broken    | Works*        | Partial      | **Works (1.x), Correct (2.0)** |
| RBAC/Tenancy/Subscription     | Rigid     | Inconsistent† | Ambiguous    | **Capability-correct** |
| Fresh-host installation       | Complex   | Simple        | Redundant    | **Simple (1.x), Clean (2.0)** |
| Upgrade/migration safety      | Risky     | Safe‡         | Risky        | **Safe (staged)** |
| Minimum host coupling         | High      | Permanent     | Moderate     | **Decreasing to zero** |

\* Works but contradicts R5: Foundation ships a user/auth domain.
† RBAC either bypasses capabilities (hidden dep) or requires a
  capability from a module that does not truly own the domain.
‡ Safe only because it permanently defers the ownership question.

## Strategy B — Specific Contradictions

Strategy B was the original recommendation. On deeper analysis it has
two structural problems:

**Contradiction 1: R5 violation in spirit.** R5 states that
Identity/Auth is an installable platform module, not a mandatory
foundation internal. But if the Foundation host permanently owns
`users`, `password_reset_tokens`, `sessions`, `App\Models\User`, and
the auth provider wiring, then the Foundation *is* the identity
provider. `modmon-identity` does not own Identity — it merely
decorates a host-owned identity domain with supplementary tables.
This is not what R5 envisions. R5 envisions that Identity is a *module
you install to get user management*, not a module you install to
slightly enhance user management that the Foundation already provides.

**Contradiction 2: Capability bypass.** If RBAC can reference `user_id`
without requiring `identity.user` (because the host always has a
`users` table), then the capability system is decorative for the most
fundamental platform dependency. Every module that needs users would
rely on an undeclared host assumption rather than a declared capability.
This erodes the capability architecture: if the most common dependency
(users) bypasses capabilities, developers will naturally bypass
capabilities for other dependencies too.

The alternative — requiring `identity.user` even under Strategy B —
creates a semantic oddity: the capability means "Identity has enhanced
the host's built-in user system" rather than "an identity service is
available." The capability becomes an enhancement flag rather than an
availability declaration.

## SemVer Analysis: Foundation 1.x vs 2.0

### What SemVer requires

Under Semantic Versioning 2.0.0:

- **Minor version** (1.x): add functionality in a backward-compatible
  manner. Deprecation is allowed.
- **Major version** (2.0): incompatible changes to the public API /
  contract.

### What constitutes the Foundation Contract?

The Foundation Contract is defined by:

1. The SDK contracts, DTOs, and contribution interfaces.
2. The Runtime lifecycle semantics.
3. The `CompatibilityChecker::FOUNDATION_VERSION` constant.
4. The module.json schema.
5. The Artisan commands and their behavior.

The host application scaffolding — `App\Models\User`, `config/auth.php`,
`database/migrations/0001_01_01_000000_create_users_table.php` — is
**not** part of the Foundation Contract. It is part of the *host
application template*. No Foundation Runtime, SDK, or Experience code
references it. No module manifest declares compatibility with it. No
test validates it as a Foundation invariant.

### Assessment

**Deprecating the host user scaffolding in Foundation 1.1** is
backward-compatible. The files remain present. Existing code continues
to work. A deprecation notice in documentation and a `foundation:doctor`
warning are additive changes. Modules compiled against Foundation ^1.0
continue to function. This is a valid minor-version change.

**Removing the host user scaffolding in Foundation 2.0** is a
breaking change to the host template (not to the Foundation Contract
itself, but to what a fresh `git clone` contains). This is appropriate
for a major version boundary. A Foundation 2.0 host that needs auth
installs `modmon-identity` — just as it would install `modmon-rbac`
for role-based access.

The question "does removing a default migration break the Foundation
Contract?" has a clear answer: **no**, because the Foundation Contract
is the SDK/Runtime/Experience boundary, not the host application
template. But it does change the *developer experience of a fresh
clone*, which justifies a major version boundary for the removal step.

### Intermediate option: Foundation 1.1 only

If Foundation 2.0 is far away, Strategy D can be partially realized
within 1.x:

1. Foundation 1.1 deprecates the host scaffolding in documentation.
2. Identity's install adopts the existing tables.
3. `foundation:doctor` warns about deprecated host scaffolding when
   Identity is installed.
4. The host scaffolding files remain in the repository but are
   documented as transitional.

This gives all practical benefits of Strategy D within Foundation 1.x.
The Foundation 2.0 cleanup is a future housekeeping step, not a
blocker.

## Decision

**Strategy D — Identity Owns the User/Auth Domain; Host Scaffolding
is Architectural Residue.**

This is the only strategy that satisfies all locked requirements
simultaneously:

- **R5:** Identity is an installable module, not a foundation internal.
  The host scaffolding is transitional residue, not an intentional
  Foundation feature.
- **R9:** Downstream modules declare `identity.user` as a required
  capability. No hidden dependencies on host scaffolding.
- **R10:** Identity owns its migrations, routes, views, tests, and
  contributions — including the `users` table.
- **R3:** Identity is portable. In Foundation 1.x it adopts an existing
  table. In Foundation 2.0 it creates its own.

## Clarifications

### Schema Compatibility on Adoption

Identity's adoption of an existing `users` table must not rely solely on
`Schema::hasTable('users')`. A table named `users` could have been
modified by the host developer (columns added, removed, or re-typed) in
ways that are incompatible with what Identity requires. The adoption
migration must validate schema compatibility before proceeding:

1. **Required columns:** Identity's migration checks that the existing
   `users` table contains the columns it depends on (`id`, `name`,
   `email`, `password`, `remember_token`, `email_verified_at`,
   `created_at`, `updated_at`) with compatible types.
2. **Required related tables:** The same column-level validation applies
   to `password_reset_tokens` (`email`, `token`, `created_at`).
3. **Fail-safe behavior:** If schema validation fails, the migration
   must abort with a clear diagnostic message identifying the
   incompatibility, rather than silently adopting a table whose schema
   it cannot work with. The developer then resolves the schema
   difference before retrying.

The exact validation mechanism (a migration helper, a module doctor
check, or both) is an implementation detail for the Identity module. The
architectural requirement is: **detect-and-validate, not
detect-and-assume**.

### Session Storage vs Identity Domain

The host migration `0001_01_01_000000_create_users_table.php` creates
three tables in a single file: `users`, `password_reset_tokens`, and
`sessions`. These do not all belong to the same domain.

**Identity domain tables** — `users` and `password_reset_tokens` are
intrinsically part of the user/auth domain. They have no meaning outside
of an identity system. Identity owns them unconditionally upon adoption.

**Session storage** — The `sessions` table is generic application
infrastructure. Its primary purpose is HTTP session persistence; it
exists and functions without any user system (unauthenticated sessions
are valid). The `user_id` column on `sessions` is a nullable foreign
key: it is populated only when a session is associated with an
authenticated user.

Consequently, `sessions` is **not** an Identity-owned table. It is
application infrastructure that has an *optional coupling* to Identity
via its nullable `user_id` column. When Identity is installed,
`sessions.user_id` references Identity's user domain. When Identity is
not installed (Foundation 1.x with host scaffolding, or Foundation 2.0
without Identity), `sessions.user_id` remains null for all rows and the
table functions as a pure session store.

This distinction updates the earlier statement in this ADR that Identity
owns `sessions`. The corrected ownership is:

```
Identity owns:          users, password_reset_tokens
Foundation owns:        sessions (application infrastructure)
Optional coupling:      sessions.user_id → users.id (when Identity is present)
```

Foundation's host migration may continue to create `sessions` in
Foundation 2.0 — it is not identity scaffolding residue, it is
legitimate application infrastructure. Alternatively, session storage
could become its own infrastructure concern. Either way, `sessions` is
not part of the Identity domain boundary.

### Authoritative Ownership After Adoption

Once Identity successfully adopts a legacy host user schema — meaning
the schema compatibility validation passes and Identity's migration
records the adoption — **Identity becomes the authoritative and sole
owner of the `users` and `password_reset_tokens` tables.** From that
point forward:

1. All schema changes to `users` and `password_reset_tokens` are made
   exclusively through Identity's migrations, never through host
   migrations or ad-hoc changes.
2. The host scaffolding files (`App\Models\User`, the original host
   migration) are inert artifacts. They must not be used to alter the
   tables Identity now owns.
3. Other modules that need user data access it through Identity's
   published capabilities (`identity.user`), not by querying the
   `users` table directly.
4. `foundation:doctor` should confirm Identity's ownership and warn if
   the host scaffolding files still exist (as a cleanup reminder, not
   as a runtime error).

The adoption is a one-way transfer: there is no mechanism or intention
to return ownership of these tables to the host. If Identity is
disabled, the tables remain (they contain production data) but are
unmanaged until Identity is re-enabled or an explicit data-migration
strategy is applied. If Identity is uninstalled, the data question is
handled by Identity's uninstall procedure (documented in its README),
not by the Foundation reverting to host ownership.

## Consequences

### Foundation 1.1 Changes (Backward-Compatible)

1. The host scaffolding files remain in the repository.
2. Documentation marks them as **transitional compatibility
   infrastructure**, not as Foundation Contract.
3. `foundation:doctor` gains a new diagnostic: if `modmon-identity` is
   installed, warn that host user scaffolding is deprecated.
4. `modmon-identity`'s migration detects existing tables and validates
   schema compatibility before adoption (see Clarifications):
   - Table exists and schema is compatible → adopt (no schema change,
     no data migration). Identity becomes authoritative owner.
   - Table exists but schema is incompatible → abort with diagnostic.
   - Table does not exist → create it (future Foundation 2.0 path).
5. `CompatibilityChecker::FOUNDATION_VERSION` bumps to `1.1.0`.

### Identity Module Design

Identity creates and owns:

```
users                   — adopted from host or created fresh
password_reset_tokens   — adopted from host or created fresh
identity_profiles       — extended user data
identity_social_logins  — OAuth/social provider records
```

Note: `sessions` is application infrastructure owned by Foundation, not
by Identity. Identity has an optional coupling to `sessions` via the
nullable `sessions.user_id` column (see Clarifications above).

Identity provides capabilities:

```
identity.user
identity.authentication
```

Identity contributes:

- Routes: registration, login, logout, password reset, profile
- Navigation: user menu items
- Permissions: `identity.users.view`, `identity.users.manage`, etc.
- Auth model: `Modules\Identity\Models\User extends Authenticatable`

Identity's install sets `AUTH_MODEL` in `.env` (configuration change,
not source edit). On disable, `AUTH_MODEL` reverts to the host default
(`App\Models\User`). In Foundation 1.x, basic auth continues to work
via the host model. In Foundation 2.0, disabling Identity disables
authentication entirely (correct behavior — no Identity module means
no auth).

### Downstream Module Dependencies

RBAC, Tenancy, and Subscription declare:

```json
"requires": {
    "capabilities": ["identity.user"]
}
```

This means they cannot be installed without Identity. This is
correct — these modules genuinely need users to function. The
dependency is explicit, validated at install time, and visible in
`module:doctor` output.

A business module that needs a `user_id` column (e.g., `audit_logs`)
also declares `identity.user` as a required capability. There are no
implicit user-table assumptions anywhere in the module ecosystem.

### Host `App\Models\User` in Foundation 1.x

The host model remains as a fallback. Identity's model extends
`Authenticatable` independently (not extending `App\Models\User`).
When Identity is installed, `AUTH_MODEL` points to Identity's model.
When Identity is not installed, `AUTH_MODEL` defaults to the host
model.

In Foundation 2.0, `App\Models\User` is removed from the host
template. Fresh clones do not have it.

### Foundation 2.0 Changes (Future, Breaking)

1. Remove `database/migrations/0001_01_01_000000_create_users_table.php`.
2. Remove `app/Models/User.php`.
3. Update `config/auth.php` to have no default model (or require
   `AUTH_MODEL` env var).
4. Update upgrade guide: "Install modmon-identity before upgrading."
5. Bump `CompatibilityChecker::FOUNDATION_VERSION` to `2.0.0`.

These are host template changes, not Foundation Contract changes.
Modules declaring `"foundation": "^1.0"` would need to declare
`"foundation": "^1.0 || ^2.0"` or `"foundation": ">=1.0 <3.0"` if
they are compatible with both.

### Costs

1. **Slightly more complex Identity migration.** The schema detection,
   compatibility validation, and conditional adoption logic adds
   complexity to the migration. This is a one-time cost justified by
   the safety guarantee that Identity never adopts an incompatible
   schema.

2. **RBAC/Tenancy/Subscription require Identity.** This is a real
   constraint — you cannot install RBAC without Identity. But this
   reflects genuine reality: RBAC without users is meaningless. The
   composition chain `Identity → RBAC → Tenancy → Subscription` is
   the documented product assembly path.

3. **Foundation 1.x hosts without Identity have deprecated
   scaffolding.** This is an intermediate state, not a permanent one.
   It is clearly documented and does not affect runtime behavior.

4. **Foundation 2.0 is a breaking change for host templates.** This
   is acceptable under SemVer. The Foundation Contract (SDK, Runtime,
   Experience) can remain backward-compatible even as the host
   template changes.

### Risks

1. **If Foundation 2.0 is never built**, the host scaffolding remains
   as documented residue indefinitely. This is acceptable — the
   deprecation documentation and `foundation:doctor` warning prevent
   confusion, and Identity still works correctly in Foundation 1.x.

2. **Third-party code referencing `App\Models\User` directly** will
   break in Foundation 2.0. The upgrade guide must be clear.

## Rejected Alternatives

### A — Claim the `users` Table (Immediate Transfer)

Rejected. Requires host file deletion during module installation,
violating the portable module contract (R3). Creates a Foundation host
that cannot function without Identity (violating R5). Fragile
conditional migration logic for existing installations.

### B — Work Alongside the Host Table (Permanent Host Ownership)

Rejected. Contradicts R5 in spirit: the Foundation permanently ships
an identity domain, making Identity a decorator rather than an owner.
Forces a choice between bypassing the capability system (hidden
host-table dependency) or using capabilities for a module that does not
truly own its domain. Creates a permanent ownership exception with no
migration path. The architectural inconsistency compounds as more
modules depend on the implicit host `users` table.

### C — Create a Separate `identity_users` Table

Rejected. Creates redundant user tables, requires auth re-wiring (host
source edit), introduces ambiguity for downstream modules, and carries
significant data-migration risk for existing installations.
