# TenancyBranding v1 Compliance Report (relocated)

The TenancyBranding module was extracted from `modmon-foundation` into
its own repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-tenancy-branding](https://github.com/mwpn/modmon-tenancy-branding) —
see `docs/reports/tenancy-branding-compliance-v1.md` in that repository.

**Result:** Certification proof recording in progress after extract.

Provides `branding.tenant` (`TenantBrandingContract`). Requires
`branding.application`, `tenancy.tenant`, `tenancy.context`.
Foundation does not ship `Modules/TenancyBranding`.

Install:

```bash
# After Identity + Branding + Tenancy are installed:
git clone https://github.com/mwpn/modmon-tenancy-branding.git /tmp/modmon-tenancy-branding
cp -r /tmp/modmon-tenancy-branding/Modules/TenancyBranding ./Modules/TenancyBranding
php artisan module:doctor tenancy-branding
php artisan module:install tenancy-branding
```
