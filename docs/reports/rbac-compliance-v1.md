# RBAC v1 Compliance Report (relocated)

The RBAC module was extracted from `modmon-foundation` into its own
repository after Phase 5 portability certification.

**Canonical location:** [mwpn/modmon-rbac](https://github.com/mwpn/modmon-rbac) —
see `docs/reports/rbac-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-08-14; **v1.0.1** owner-bootstrap patch
2026-09-16). Module tests and the full report live in `modmon-rbac`.

Requires Identity (`identity.user`) from [mwpn/modmon-identity](https://github.com/mwpn/modmon-identity).

Install + operational bootstrap:

```bash
git clone https://github.com/mwpn/modmon-identity.git /tmp/modmon-identity
cp -r /tmp/modmon-identity/Modules/Identity ./Modules/Identity
php artisan module:doctor identity
php artisan module:install identity

git clone https://github.com/mwpn/modmon-rbac.git /tmp/modmon-rbac
cp -r /tmp/modmon-rbac/Modules/Rbac ./Modules/Rbac
php artisan module:doctor rbac
php artisan module:install rbac

php artisan identity:user:create --name="Operator" --email=operator@example.com --password=secret-password
php artisan rbac:bootstrap-owner --email=operator@example.com
# login → GET /rbac/roles
```

`module:install rbac` does not seed privileges. Owner access requires the
explicit `rbac:bootstrap-owner` command (exactly one of `--email` / `--user`).
