# Inventory v1 Compliance Report (relocated)

The Inventory module was extracted from `modmon-foundation` into its own
repository after Phase 2 portability/compliance.

**Canonical location:** [mwpn/modmon-inventory](https://github.com/mwpn/modmon-inventory) —
see `docs/reports/inventory-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-14 on this Foundation host lineage;
module tests and the full report live in `modmon-inventory`).

Fresh GitHub-only proof (2026-09-14): clone Foundation `783d5f9` + copy
Inventory from `mwpn/modmon-inventory` `0e0ebf2` into
`C:\laragon\www\modmon-inventory-proof` — doctor/install/disable/enable,
stock smoke (`SKU-PROOF` onHand 7, 2 movements), HTTP 200 after normal
host Vite build, host sources unchanged, 22 module tests PASS, full host
124 passed / 1 skipped.
**modmon-inventory v1 portable certification closed.**

Provides `inventory.stock` (`StockContract`). No Identity, RBAC, or
Settings dependency. Foundation does not ship `Modules/Inventory`.

Install:

```bash
git clone https://github.com/mwpn/modmon-inventory.git /tmp/modmon-inventory
cp -r /tmp/modmon-inventory/Modules/Inventory ./Modules/Inventory
php artisan module:doctor inventory
php artisan module:install inventory
```
