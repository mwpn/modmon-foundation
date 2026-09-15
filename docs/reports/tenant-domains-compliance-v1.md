# TenantDomains v1 Compliance Report (relocated)

The TenantDomains module was extracted from `modmon-foundation` into its
own repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-tenant-domains](https://github.com/mwpn/modmon-tenant-domains) —
see `docs/reports/tenant-domains-compliance-v1.md` in that repository.

**Result:** Certification proof recording in progress after extract.

Provides `tenancy.domain`. Requires `tenancy.tenant` only.
Foundation does not ship `Modules/TenantDomains`.

Install:

```bash
# After Identity + Tenancy are installed:
git clone https://github.com/mwpn/modmon-tenant-domains.git /tmp/modmon-tenant-domains
cp -r /tmp/modmon-tenant-domains/Modules/TenantDomains ./Modules/TenantDomains
php artisan module:doctor tenant-domains
php artisan module:install tenant-domains
```
