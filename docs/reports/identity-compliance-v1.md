# Identity v1 Compliance Report (relocated)

The Identity module was extracted from `modmon-foundation` into its own
repository after Phase 6 portability certification.

**Canonical location:** [mwpn/modmon-identity](https://github.com/mwpn/modmon-identity) —
see `docs/reports/identity-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-08-13 on clean Foundation host).

Install:

```bash
git clone https://github.com/mwpn/modmon-identity.git /tmp/modmon-identity
cp -r /tmp/modmon-identity/Modules/Identity ./Modules/Identity
php artisan module:doctor identity
php artisan module:install identity
```
