# TenancyBranding v1 Compliance Report (relocated)

The TenancyBranding module was extracted from `modmon-foundation` into
its own repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-tenancy-branding](https://github.com/mwpn/modmon-tenancy-branding) —
see `docs/reports/tenancy-branding-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-15 on this Foundation host lineage;
module tests and the full report live in `modmon-tenancy-branding`).

Fresh GitHub-only proof (2026-09-15): Foundation `458daf5` +
TenancyBranding `5d523ee` + Branding `a5c1902` + Tenancy `dab1597` +
Identity `3fa8792` into `C:\laragon\www\modmon-tenancy-branding-proof` —
doctor gating, install/disable/enable, inherit/override merge, fail-closed
migration, host sources unchanged, 18 module tests PASS, full host 124
passed / 1 skipped.
**modmon-tenancy-branding v1 portable certification closed.**

Provides `branding.tenant`. Requires `branding.application`,
`tenancy.tenant`, `tenancy.context`. Foundation does not ship
`Modules/TenancyBranding`.

Install:

```bash
git clone https://github.com/mwpn/modmon-tenancy-branding.git /tmp/modmon-tenancy-branding
cp -r /tmp/modmon-tenancy-branding/Modules/TenancyBranding ./Modules/TenancyBranding
php artisan module:doctor tenancy-branding
php artisan module:install tenancy-branding
```
