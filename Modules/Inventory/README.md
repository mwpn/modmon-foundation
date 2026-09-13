# Inventory

Minimal stock item catalog and on-hand quantity for ModMon hosts.
Phase 1 delivers the `inventory.stock` core, public `StockContract`,
admin HTTP surface, and Experience contributions.

## Type

`business`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability         | Description                                                                 |
|--------------------|-----------------------------------------------------------------------------|
| `inventory.stock`  | Stock items and on-hand quantity are available via `StockContract`.         |

## Requires

*None* (`requires.capabilities: []`) — installs on a bare compatible
Foundation host.

## Security / composition notes (Phase 1)

- Permission contribution (`ContributesPermissions`) registers ability
  metadata for RBAC/Gate consumers; it does **not** by itself secure HTTP
  routes.
- Phase 1 Inventory routes intentionally have **no** Identity `auth` or
  RBAC/`can` middleware so the module remains standalone without
  `requires` on Identity/RBAC.
- Navigation declares `permission: inventory.items.view` for Experience
  shell filtering when Gate can answer; guests/unauthorized users may
  still hit routes by URL until a host composes authz middleware or a
  later Inventory phase adds it.
- This is a **composition limitation**, not a Foundation gap. Do not
  patch Foundation for Inventory-only auth wiring.

## Optional Integrations

| Capability                 | Behavior when available                                                                 |
|----------------------------|-----------------------------------------------------------------------------------------|
| `identity.user`            | Host may wrap Inventory routes with session `auth`. Not required in Phase 1.            |
| `authorization.permission` | Declared permissions are assignable via RBAC when installed; Gate enforces when wired.  |

Phase 1 does **not** consume `settings.runtime`.

## Installation

```bash
cp -r Modules/Inventory /path/to/host/Modules/Inventory
php artisan module:doctor inventory
php artisan module:install inventory
```

## Public Contracts

| Contract        | Status      |
|-----------------|-------------|
| `StockContract` | Implemented |

Methods: `exists`, `find` → `StockItemRead`, `onHand`, `adjust(sku, delta, reason)`.
Reason is required. Eloquent models are never returned. SKU is the
stable public identity and is **immutable** after create.

## Permissions

| Permission ID              | Label          |
|----------------------------|----------------|
| `inventory.items.view`     | View inventory |
| `inventory.items.manage`   | Manage items   |
| `inventory.stock.adjust`   | Adjust stock   |

## Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/inventory/items` | `inventory.items.index` |
| GET | `/inventory/items/create` | `inventory.items.create` |
| POST | `/inventory/items` | `inventory.items.store` |
| GET | `/inventory/items/{sku}/edit` | `inventory.items.edit` |
| PUT | `/inventory/items/{sku}` | `inventory.items.update` |
| POST | `/inventory/items/{sku}/adjust` | `inventory.items.adjust` |

No delete route — deactivate via `active` so movement history is preserved.

## Database Ownership

| Table | Description |
|-------|-------------|
| `inventory_items` | SKU (immutable), name, unit, quantity, active |
| `inventory_stock_movements` | delta, quantity_after, reason (FK restrict on delete) |

## Navigation / Dashboard

- Nav: “Inventory” → `/inventory/items` (`permission: inventory.items.view`)
- Widget: low-stock / out-of-stock count (`inventory::widgets.low-stock`)

## Domain invariants

- Delta must be non-zero; reason required (non-empty)
- Resulting quantity cannot be negative
- Unknown SKU rejected on adjust
- `quantity_after = quantity_before + delta`
- Adjust runs in a DB transaction with row `lockForUpdate`; failure rolls
  back both quantity change and movement insert
- SKU immutable after creation; admin update cannot change it
- No hard-delete of items in admin UI; deactivate preserves movements

## Testing

```bash
php artisan test Modules/Inventory/Tests
```

## Version History

| Version | Foundation | Description |
|---------|------------|-------------|
| 1.0.0   | ^1.0       | Phase 0 scaffold + Phase 1 stock core (+ invariant harden). |
