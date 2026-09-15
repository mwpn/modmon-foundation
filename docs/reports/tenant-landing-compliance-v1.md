# TenantLanding v1 Compliance Report (relocated)

The TenantLanding module was extracted from `modmon-foundation` into its
own repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-tenant-landing](https://github.com/mwpn/modmon-tenant-landing) —
see `docs/reports/tenant-landing-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-15 on this Foundation host lineage;
module tests and the full report live in `modmon-tenant-landing`).

Fresh GitHub-only proof (2026-09-15): Foundation `e39474e` +
TenantLanding `dc7e035` + TenantDomains `3deea87` + Tenancy `dab1597` +
Identity `3fa8792` (+ optional Branding `a5c1902` /
TenancyBranding `5d523ee`) into
`C:\laragon\www\modmon-tenant-landing-proof` — doctor gating, discovery
without schema mutation, explicit install, hostname hit via
`TenantDomainContract::resolve(host)` without request attrs, unknown/
inactive pass-through, no `TenantContext` mutation, optional branding
cascade + degrade, login CTA gate, disable/fresh-boot/re-enable,
fail-closed migration, host sources unchanged, 21 module tests PASS,
full host 124 passed / 1 skipped.
**modmon-tenant-landing v1 portable certification closed.**

**Released:** annotated tag `v1.0.0` at `7d577ac` pushed to
`mwpn/modmon-tenant-landing` (2026-09-15).

Provides `tenancy.landing`. Requires `tenancy.domain` only. Foundation
does not ship `Modules/TenantLanding`.

Install:

```bash
# After Identity + Tenancy + TenantDomains are installed:
git clone https://github.com/mwpn/modmon-tenant-landing.git /tmp/modmon-tenant-landing
cp -r /tmp/modmon-tenant-landing/Modules/TenantLanding ./Modules/TenantLanding
php artisan module:doctor tenant-landing
php artisan module:install tenant-landing
```
