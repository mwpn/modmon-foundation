# Branding v1 Compliance Report (relocated)

The Branding module was extracted from `modmon-foundation` into its own
repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-branding](https://github.com/mwpn/modmon-branding) —
see `docs/reports/branding-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-15 on this Foundation host lineage;
module tests and the full report live in `modmon-branding`).

Fresh GitHub-only proof (2026-09-15): clone Foundation `0ea4eb7` + copy
Branding from `mwpn/modmon-branding` `19455a5` into
`C:\laragon\www\modmon-branding-proof` — doctor/install/disable/enable,
default `current()` without seed row, HTTP 200 after normal host Vite
build, fail-closed migration, host sources unchanged, 17 module tests
PASS, full host 124 passed / 1 skipped.
**modmon-branding v1 portable certification closed.**

Provides `branding.application` (`BrandingContract`). Requires nothing.
No Identity/Tenancy/Settings/RBAC dependency. Foundation does not ship
`Modules/Branding`.

Install:

```bash
git clone https://github.com/mwpn/modmon-branding.git /tmp/modmon-branding
cp -r /tmp/modmon-branding/Modules/Branding ./Modules/Branding
php artisan module:doctor branding
php artisan module:install branding
```
