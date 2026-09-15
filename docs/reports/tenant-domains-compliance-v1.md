# TenantDomains v1 Compliance Report (relocated)

The TenantDomains module was extracted from `modmon-foundation` into its
own repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-tenant-domains](https://github.com/mwpn/modmon-tenant-domains) —
see `docs/reports/tenant-domains-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-15 on this Foundation host lineage;
module tests and the full report live in `modmon-tenant-domains`).

Fresh GitHub-only proof (2026-09-15): Foundation `1deeb96` +
TenantDomains `e0ed83a` + Tenancy `dab1597` + Identity `3fa8792` into
`C:\laragon\www\modmon-tenant-domains-proof` — doctor gating, discovery
without schema mutation, explicit install, hostname normalize/primary,
middleware attrs without `TenantContext` mutation, disable/fresh-boot/
re-enable, fail-closed migration, host sources unchanged, 16 module
tests PASS, full host 124 passed / 1 skipped.
**modmon-tenant-domains v1 portable certification closed.**

Provides `tenancy.domain`. Requires `tenancy.tenant` only. Foundation
does not ship `Modules/TenantDomains`.

Install:

```bash
# After Identity + Tenancy are installed:
git clone https://github.com/mwpn/modmon-tenant-domains.git /tmp/modmon-tenant-domains
cp -r /tmp/modmon-tenant-domains/Modules/TenantDomains ./Modules/TenantDomains
php artisan module:doctor tenant-domains
php artisan module:install tenant-domains
```
