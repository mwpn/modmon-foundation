# Tenancy v1 Compliance Report (relocated)

The Tenancy module was extracted from `modmon-foundation` into its own
repository after Phase 3 portability/compliance.

**Canonical location:** [mwpn/modmon-tenancy](https://github.com/mwpn/modmon-tenancy) —
see `docs/reports/tenancy-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-14 on this Foundation host lineage;
module tests and the full report live in `modmon-tenancy`).

Fresh GitHub-only proof (2026-09-14): clone Foundation `49ab75e` +
Identity `3fa8792` + Tenancy `18e4e9c` into
`C:\laragon\www\modmon-tenancy-ext-proof` — doctor/install/disable/enable,
HTTP 200 after normal host Vite build, host sources unchanged, 35 module
tests PASS, full host 124 passed / 1 skipped.
**modmon-tenancy v1 portable certification closed.**

Provides `tenancy.tenant`, `tenancy.membership`, `tenancy.context`.
Requires `identity.user` only (typically `mwpn/modmon-identity`). No
RBAC/Settings/Subscription dependency. Foundation does not ship
`Modules/Tenancy`.

Install:

```bash
git clone https://github.com/mwpn/modmon-identity.git /tmp/modmon-identity
cp -r /tmp/modmon-identity/Modules/Identity ./Modules/Identity
php artisan module:install identity

git clone https://github.com/mwpn/modmon-tenancy.git /tmp/modmon-tenancy
cp -r /tmp/modmon-tenancy/Modules/Tenancy ./Modules/Tenancy
php artisan module:doctor tenancy
php artisan module:install tenancy
```
