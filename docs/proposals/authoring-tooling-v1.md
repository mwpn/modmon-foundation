# Proposed Authoring Tooling for Foundation v1

**Status:** Proposal (not implemented)  
**Date:** 2026-08-12

## Summary

This document evaluates whether Foundation v1 needs authoring tooling
commands and recommends which to implement.

## Evaluated Commands

### 1. `php artisan module:make {Name}`

**Purpose:** Scaffold a new module directory with `module.json`, service
provider, README, route file, and test stub.

**Justification:** Every new module requires the same boilerplate:
`module.json` with correct schema, a service provider implementing the
right interfaces, a route file, and a README. The module authoring
standard defines exact naming conventions (code format, namespace,
directory structure) that a generator can enforce automatically.

**Assessment:** **Recommended for implementation.** The scaffolding is
small, backward-compatible, and prevents the most common authoring
mistakes (wrong code format, wrong namespace, missing compatibility
declarations). It does not alter the Foundation Contract — it is purely
a developer convenience command.

**Proposed behavior:**

```bash
php artisan module:make Inventory
```

Would create:

```
Modules/Inventory/
├── module.json           # Pre-filled with schema, code, version, provider
├── InventoryServiceProvider.php  # Stub implementing ContributesRoutes
├── README.md             # From template
├── Routes/
│   └── web.php           # Stub route file
├── Http/
│   └── Controllers/
│       └── InventoryController.php  # Stub controller
├── Database/
│   └── Migrations/       # Empty directory
├── Resources/
│   └── views/            # Empty directory
└── Tests/
    └── InventoryLifecycleTest.php  # Stub lifecycle test
```

**Scope:** Small. One new Artisan command (~100-150 lines). No changes
to Foundation Contract, SDK, or runtime behavior.

**Risk:** None. The command generates files; it does not modify the
runtime or existing modules.

### 2. `php artisan module:verify {code}`

**Purpose:** Run the portable module certification checklist (section 18
of the authoring standard) programmatically.

**Assessment:** **Recommended for future consideration, not for
immediate implementation.**

Rationale: Most of the certification checklist items are already covered
by `module:doctor` (manifest validation, compatibility, capabilities,
provider existence, state) and by the module's own tests (routes,
migrations, contributions, disable/enable). The remaining items
(architecture boundary checks, cross-module import detection,
portability proof in a clean host) are complex to automate reliably.

A partial `module:verify` that duplicates `module:doctor` adds no value.
A comprehensive one that checks architecture boundaries would require
static analysis tooling (e.g., parsing PHP imports) that is beyond the
scope of a small backward-compatible addition.

**Recommendation:** Defer `module:verify` until at least two real
modules have been authored using the standard. At that point, the
repeated manual verification steps will clarify which checks provide
the most value when automated.

## Recommendation

Implement `module:make` as a small backward-compatible addition.
Defer `module:verify` as a future proposal.

**Important:** If implementing `module:make` would require modifying
the frozen Foundation Contract or significantly broadening scope, it
should remain a proposal only. The implementation should be limited to
a single new Artisan command file with no changes to existing Foundation
runtime classes.
