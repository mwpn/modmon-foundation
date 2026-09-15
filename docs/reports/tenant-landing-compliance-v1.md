# TenantLanding v1 Compliance Report (relocated)

The TenantLanding module was extracted from `modmon-foundation` into its
own repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-tenant-landing](https://github.com/mwpn/modmon-tenant-landing) —
see `docs/reports/tenant-landing-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-15 on this Foundation host lineage;
module tests and the full report live in `modmon-tenant-landing`).

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
