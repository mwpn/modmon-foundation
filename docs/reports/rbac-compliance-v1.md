# RBAC v1 Compliance Report (relocated)

The RBAC module was extracted from `modmon-foundation` into its own
repository after Phase 5 portability certification.

**Canonical location:** [mwpn/modmon-rbac](https://github.com/mwpn/modmon-rbac) —
see `docs/reports/rbac-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-08-14 on this Foundation host; module
tests and the full report live in `modmon-rbac`).

Requires Identity (`identity.user`) from [mwpn/modmon-identity](https://github.com/mwpn/modmon-identity).

Install:

```bash
git clone https://github.com/mwpn/modmon-identity.git /tmp/modmon-identity
cp -r /tmp/modmon-identity/Modules/Identity ./Modules/Identity
php artisan module:doctor identity
php artisan module:install identity

git clone https://github.com/mwpn/modmon-rbac.git /tmp/modmon-rbac
cp -r /tmp/modmon-rbac/Modules/Rbac ./Modules/Rbac
php artisan module:doctor rbac
php artisan module:install rbac
```
