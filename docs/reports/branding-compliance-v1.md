# Branding v1 Compliance Report (relocated)

The Branding module was extracted from `modmon-foundation` into its own
repository after Phase 1 portability/compliance.

**Canonical location:** [mwpn/modmon-branding](https://github.com/mwpn/modmon-branding) —
see `docs/reports/branding-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE pending final GitHub-only proof SHA recording
in `modmon-branding` (extract complete; Foundation no longer ships
`Modules/Branding`).

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
