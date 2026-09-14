# Inventory v1 Compliance Report (relocated)

The Inventory module was extracted from `modmon-foundation` into its own
repository after Phase 2 portability/compliance on the authoring host.

**Canonical location:** [mwpn/modmon-inventory](https://github.com/mwpn/modmon-inventory) —
see `docs/reports/inventory-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done **on the Foundation authoring host** (Phase 2,
2026-09-13). Module tests and the full report live in `modmon-inventory`.

**Certification status:** final GitHub-only portable certification is
**not closed** until a fresh copy proof PASSes (Foundation host that does
not ship Inventory + copy `Modules/Inventory` only).

Provides `inventory.stock` (`StockContract`). No Identity, RBAC, or
Settings dependency. Foundation does not ship `Modules/Inventory`.

Install:

```bash
git clone https://github.com/mwpn/modmon-inventory.git /tmp/modmon-inventory
cp -r /tmp/modmon-inventory/Modules/Inventory ./Modules/Inventory
php artisan module:doctor inventory
php artisan module:install inventory
```
