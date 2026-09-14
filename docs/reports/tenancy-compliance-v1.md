# Tenancy v1 Compliance Report

**Module:** `Modules/Tenancy` (planned extract: `mwpn/modmon-tenancy`)  
**Foundation host lineage:** `mwpn/modmon-foundation`  
**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable
Module Definition of Done (certified 2026-09-14).  
**Phases covered:** 0 (design) → 1 (core) → 2 (minimal Experience) → 3
(portability/compliance). No feature additions in Phase 3.

## Provides / requires

| | |
|--|--|
| Provides | `tenancy.tenant`, `tenancy.membership`, `tenancy.context` |
| Requires | `identity.user` only |
| Does not require | RBAC, Settings, Subscription, `identity.authentication` |

Public contracts: `TenantContract`, `MembershipContract`,
`TenantContextContract` (DTO/read models only).

Tenancy ≠ SaaS: no billing, plans, quotas, domain provisioning, or
database-per-tenant.

## Fresh clean-host proof (2026-09-14)

Windows paths; no host source patches:

1. Clone `mwpn/modmon-foundation` → `C:\laragon\www\modmon-tenancy-proof`
   (HEAD `64c91fb` — Example only).
2. Copy `Modules/Identity` from authoring host; copy `Modules/Tenancy`
   from authoring host at `f9e5bc3` (Phase 2 surface).
3. `composer install`, SQLite `.env`, `key:generate`, baseline migrate.
4. Normal host Vite bootstrap: `npm install` + `npm run build` (required
   for Blade/`@vite` HTTP rendering; not a Tenancy install side effect).
5. Tenancy discovered → `module:doctor tenancy` **FAIL**
   (`Missing capabilities: identity.user`); **no** tenancy schema.
6. `module:install identity` → PASS; doctor tenancy → **PASS**; still
   no tenancy schema.
7. `module:install tenancy` → PASS (`Migrations applied`); three
   capabilities + contracts available; no `authorization.permission` /
   `settings.runtime`.
8. Core smoke: tenant `proof`, membership, context set; HTTP
   `/tenancy/tenants`, edit, `/tenancy/context` → **200**.
9. Permissions / nav / active-tenants widget present when enabled.
10. disable → capabilities + Experience contributions off; tenant /
    membership / context **rows preserved**.
11. enable → capabilities / contributions / contracts / HTTP / data
    restored.
12. Watched host SHA-256 unchanged → PASS
    (`bootstrap/app.php`, `routes/web.php`, `config/app.php`,
    `config/auth.php`, `composer.json`, `composer.lock`,
    `FoundationServiceProvider.php`, `ModuleManager.php`).
13. Executable proof script: `proof-tenancy.php` on the proof host
    (not part of the module distribution).

## Authoring-host executable suite

`TenancyComplianceTest` models the same lifecycle in-process (including
migration fail-closed and `identity.user`-only requires).

## Security / composition note

Contributed permissions do **not** automatically secure HTTP routes.
Phase 2 routes have no `auth`/`can` middleware (same posture as
Inventory). Not a Foundation gap.

## Foundation gaps

**None.** No Foundation runtime changes required for Tenancy Phase 0–3.

## Install (after extract)

```bash
git clone https://github.com/mwpn/modmon-identity.git /tmp/modmon-identity
cp -r /tmp/modmon-identity/Modules/Identity ./Modules/Identity
php artisan module:install identity

git clone https://github.com/mwpn/modmon-tenancy.git /tmp/modmon-tenancy
cp -r /tmp/modmon-tenancy/Modules/Tenancy ./Modules/Tenancy
php artisan module:doctor tenancy
php artisan module:install tenancy
```

**modmon-tenancy v1 portable certification closed** on this Foundation
host lineage (module still lives under authoring `Modules/Tenancy`
until extract).
