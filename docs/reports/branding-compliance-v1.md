# Branding v1 Compliance Report (Phase 1 authoring host)

**Status:** Phase 1 implemented and proven on the Foundation authoring
host. External GitHub-only clean-host certification and extract to
`mwpn/modmon-branding` remain pending.

**Proposal:** [`docs/proposals/branding-v1.md`](../proposals/branding-v1.md)

## Provides / requires

| Capability | Contract | Requires |
|------------|----------|----------|
| `branding.application` | `BrandingContract` | `[]` |

No Settings, Identity, RBAC, or Tenancy dependency.

## Authoring-host proof (2026-09-15)

1. `module:doctor branding` — PASS (discovered; no schema mutation).
2. `module:install branding` — PASS (`Migrations applied`); table
   `branding_application` exists with **0 rows** (no seed).
3. `BrandingContract::current()` before configure → defaults from
   `config('app.name')`, `configured=false`.
4. Admin HTTP `/branding` edit + validated colors + logo upload → PASS.
5. Contributions: nav `branding.edit`, permission `branding.manage`.
6. `module:disable branding` → capability/contributions off; row
   preserved; `module:enable branding` → restored.
7. `php artisan test Modules/Branding/Tests` → **15 passed**.
8. Watched host Foundation sources unchanged in compliance test.
9. **No Foundation runtime/SDK/Experience patches.**

## Foundation gaps

**None for Phase 1.**

## Next

Extract to `modmon-branding`, run fresh GitHub-only portability proof,
then remove `Modules/Branding` from the Foundation tree (same posture as
Identity/Settings/Inventory/Tenancy).
