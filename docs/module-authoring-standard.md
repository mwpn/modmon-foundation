# Module Authoring Standard

**Applies to:** Foundation Contract v1.0.0  
**Status:** Canonical (agent entrypoint)  
**Last updated:** 2026-08-13

Extended reference: [`module-authoring-standard-v1.md`](module-authoring-standard-v1.md)  
Templates: [`templates/module-json-template.json`](templates/module-json-template.json), [`templates/module-readme-template.md`](templates/module-readme-template.md)  
Proven references: `Modules/Example/` (in-repo), `modmon-identity` (sibling repo)

---

## Agent Brief

To scaffold a new module, an agent needs only:

| Input | Example (RBAC) | Notes |
|-------|----------------|-------|
| **Name** | `Rbac` | PascalCase or Title Case |
| **Type** | `platform` | `platform`, `business`, or `integration` |
| **Purpose** | Role and permission management | One or two sentences for README |
| **Provides** | `authorization.permission` | Stable capabilities this module exposes |
| **Requires** | `identity.user` | Capabilities that must exist before install |

Scaffold command:

```bash
php artisan module:make Rbac \
  --type=platform \
  --purpose="Role and permission management for ModMon hosts." \
  --provides=authorization.permission \
  --requires=identity.user
```

Then implement contributions, migrations, routes, views, and tests per sections below.
Copy `Modules/Example/` patterns for contributions; copy `modmon-identity` for platform-module lifecycle tests.

---

## Workflow

```
module:make (or copy template)
    ↓
implement provider + contributions + migrations/routes/views
    ↓
module:doctor {code}
    ↓
module:install {code}
    ↓
module tests + disable/enable cycle
    ↓
README + certification checklist (section 12)
    ↓
publish to modmon-{code} repository
```

Copying a module into `Modules/` must **never** run migrations or mutate `storage/app/modules.json`.

---

## 1. Module Identity

| Concept | Rule | Example |
|---------|------|---------|
| **Name** | Human-readable, PascalCase/Title Case | `Identity`, `Water Billing` |
| **Code** | `^[a-z][a-z0-9\-]*$`, unique in host | `identity`, `water-billing` |
| **Directory** | `Modules/{PascalCase}/` | `Modules/WaterBilling/` |
| **Namespace** | `Modules\{Directory}\` | `Modules\Identity\` |
| **Provider** | `Modules\{Directory}\{Directory}ServiceProvider` | `Modules\Rbac\RbacServiceProvider` |
| **Version** | SemVer `MAJOR.MINOR.PATCH` | `1.0.0` |
| **Type** | `platform` \| `business` \| `integration` | RBAC → `platform` |
| **Git repo** | `modmon-{code}` (recommended) | `modmon-rbac` |

Symlinks under `Modules/` are rejected. Only real directories are discovered.

---

## 2. Directory Structure

### Required minimum

```
Modules/{ModuleName}/
├── module.json
└── {ModuleName}ServiceProvider.php
```

`module:make` also generates `README.md`. Add layers only when needed:

```
Modules/{ModuleName}/
├── README.md
├── Http/Controllers/
├── Routes/web.php
├── Database/Migrations/
├── Resources/views/
├── Domain/Contracts/          # public cross-module interfaces
├── Tests/Feature/
└── Tests/Architecture/
```

Do **not** create empty `Domain/`, `Application/`, or `Infrastructure/` folders.

---

## 3. module.json

Canonical template: [`templates/module-json-template.json`](templates/module-json-template.json)

```json
{
    "schema": 1,
    "name": "Rbac",
    "code": "rbac",
    "version": "1.0.0",
    "type": "platform",
    "provider": "Modules\\Rbac\\RbacServiceProvider",
    "compatibility": {
        "php": "^8.3",
        "laravel": "^13.0",
        "foundation": "^1.0"
    },
    "requires": {
        "capabilities": ["identity.user"]
    },
    "provides": ["authorization.permission"]
}
```

### Fields

| Field | Required | Notes |
|-------|----------|-------|
| `schema` | yes | Currently `1` |
| `name`, `code`, `version`, `provider` | yes | Validated by `ManifestValidator` |
| `type` | no | Default `business` |
| `compatibility` | strongly recommended | See section 4 |
| `requires.capabilities` | no | Enforced at install/enable |
| `provides` | no | Single provider per capability |

Validation runs at discovery time — before any module code is booted.

---

## 4. Compatibility

Always declare all three constraints:

```json
"compatibility": {
    "php": "^8.3",
    "laravel": "^13.0",
    "foundation": "^1.0"
}
```

`CompatibilityChecker` evaluates these during `module:doctor`, `module:install`, and `module:enable`. Omitting a constraint skips that check and risks silent incompatibility.

Foundation version constant: `CompatibilityChecker::FOUNDATION_VERSION` (`1.0.0`).

---

## 5. Capabilities (`provides` / `requires`)

Capabilities express **stable functional availability** between modules without direct code imports.

### Naming

Lowercase dot notation: `{domain}.{feature}`

```
identity.user
identity.authentication
authorization.permission
tenancy.tenant
example.demo
```

### Provides

List capabilities your module registers when installed/enabled. One module owns each capability; collisions fail at install/enable.

Typical platform modules provide 1–5 capabilities representing semantic contracts, not individual CRUD operations.

### Requires

List capabilities that **must** be available before install/enable. Example: RBAC requires `identity.user`.

### Optional integrations

Check at runtime via `CapabilityRegistryContract::has($id)` — do **not** put optional deps in `requires`.

### Prohibited at boundaries

- Import another module's Eloquent models
- Query another module's tables directly
- Resolve another module's internal services
- Edit host sidebar, dashboard shell, global routes, or global permission lists

Use contracts in `Domain/Contracts/`, DTOs, events, or capability lookup instead.

---

## 6. Ownership and Boundaries

Each module owns:

- `module.json` and service provider
- migrations, routes, views, tests
- permission/navigation/dashboard declarations
- internal domain/application code
- event classes it publishes

Each module does **not** own:

- global shell layout or hardcoded navigation in Foundation
- another module's tables or internal classes
- host application source (no install-time edits outside `Modules/`)

Permissions are **declared** by modules; enforcement belongs to RBAC (when installed).

---

## 7. Service Provider

Single entry point declared in `module.json`. Implements contribution interfaces as needed:

| Interface | Method | Purpose |
|-----------|--------|---------|
| `ContributesRoutes` | `routeFiles()` | Route file path(s) |
| `ContributesNavigation` | `navigationItems()` | Sidebar items |
| `ContributesDashboard` | `dashboardWidgets()` | Dashboard widgets |
| `ContributesPermissions` | `permissionDefinitions()` | Permission metadata |

Namespace: `App\Foundation\SDK\Contributions\`

Foundation registers contributions automatically — do **not** register routes, nav, widgets, or permissions manually in `register()`/`boot()`.

### register()

- Bind internal contracts to implementations
- `mergeConfigFrom()` for module config

### boot()

- `loadViewsFrom(__DIR__ . '/Resources/views', '{code}')`
- Event listeners internal to the module

Reference implementation: `Modules/Example/ExampleServiceProvider.php`.

---

## 8. Lifecycle

### States

| State | Meaning |
|-------|---------|
| **discovered** | Valid manifest on disk; not in `modules.json` |
| **enabled** | Installed and active (install goes directly here in v1) |
| **disabled** | Deactivated; data preserved |

### Commands

```bash
php artisan module:doctor {code}    # diagnose; no state change
php artisan module:install {code}   # migrate + enable
php artisan module:disable {code}   # remove runtime contributions
php artisan module:enable {code}    # restore contributions
php artisan module:list
php artisan foundation:doctor
```

### install sequence

1. Validate manifest and compatibility
2. Verify required capabilities and provider class
3. Run `Database/Migrations/` (explicit only — never on copy)
4. Persist state to `storage/app/modules.json`
5. Register capabilities and boot contributions

### disable semantics

Removed at disable:

- capabilities from `CapabilityRegistry`
- navigation from `NavigationRegistry`
- widgets from `WorkspaceRegistry`
- permissions from `PermissionRegistry`

**Preserved:** database tables and rows, module files, `modules.json` entry (state → `disabled`).

**Known limitation:** routes stay active for the current HTTP request after disable; subsequent requests exclude them (Laravel route collection immutability).

### enable semantics

Re-validates dependencies, checks capability collisions, restores all contributions.

Boot order follows capability dependencies via `DependencyResolver` (topological sort; cycles rejected).

---

## 9. Migrations

- Location: `Database/Migrations/`
- Run only during `module:install`
- Table creator owns the table — no altering another module's tables
- Prefer table names prefixed with module code (e.g., `rbac_roles`)
- Copying module files must not trigger migrations

---

## 10. Routes

- File: `Routes/web.php` (Foundation loads via `web` middleware group)
- Prefix routes with module domain: `/rbac/...`, `/inventory/...`
- Controllers: `Http/Controllers/`, namespace `Modules\{Name}\Http\Controllers\`
- Apply middleware in the route file; do not register global middleware

Implement `ContributesRoutes::routeFiles()` returning the path(s).

---

## 11. Views

- Location: `Resources/views/`
- Load in `boot()`: `$this->loadViewsFrom(__DIR__ . '/Resources/views', '{code}')`
- Reference as `{code}::path.to.view`
- Widget views: `Resources/views/widgets/`
- Use Foundation design-system Blade components where applicable

---

## 12. Contributions Quick Reference

### Permissions

ID pattern: `{code}.{action}` or `{code}.{resource}.{action}`

```php
new PermissionDefinition(
    id: 'rbac.roles.manage',
    moduleCode: 'rbac',
    label: 'Manage Roles',
    group: 'RBAC',
);
```

### Navigation

```php
new NavigationItem(
    id: 'rbac.main',
    moduleCode: 'rbac',
    label: 'Roles',
    route: '/rbac/roles',
    group: 'Platform',
    order: 20,
    activePattern: 'rbac*',
);
```

### Dashboard widgets

```php
new DashboardWidget(
    id: 'rbac.summary',
    moduleCode: 'rbac',
    slot: 'workspace.default.dashboard.stats',
    view: 'rbac::widgets.summary',
    order: 10,
);
```

Slot convention: `workspace.{name}.dashboard.{top|stats|main}`. Use `workspace.default.*` unless a workspace capability is required.

---

## 13. Tests (minimum)

Module tests live in `Modules/{Name}/Tests/`. Run with:

```bash
php artisan test --filter="Modules\\{Name}"
```

| Area | What to prove |
|------|---------------|
| Manifest | `module.json` passes `ManifestValidator` |
| Discovery | Module appears in `ModuleDiscovery` without errors |
| Install | `module:install` succeeds on compatible host |
| Capabilities | `provides` registered; `requires` enforced |
| Routes | Declared routes respond after install |
| Migrations | Owned tables exist after install |
| Contributions | Nav/widgets/permissions present when declared |
| Disable | Contributions removed; **data remains** |
| Re-enable | Contributions restored |
| Boundary | No imports of other modules' internal classes |

Reference test suites: `Modules/Example/` (patterns), `modmon-identity/Modules/Identity/Tests/` (platform lifecycle).

Foundation regression tests for `module:make`: `tests/Feature/Foundation/ModuleMakeCommandTest.php`.

---

## 14. Portability Certification

A module is portable only if it installs into a **clean compatible Foundation host** with no unrelated host source edits.

### Checklist

**Manifest & identity**

- [ ] Valid `module.json` (schema, code, version, provider, compatibility)
- [ ] Provider class exists and is loadable

**Lifecycle**

- [ ] `module:doctor {code}` — all checks pass
- [ ] `module:install {code}` — succeeds
- [ ] `module:disable {code}` — contributions removed
- [ ] `module:enable {code}` — contributions restored
- [ ] Data survives disable/enable

**Boundaries**

- [ ] No cross-module model imports
- [ ] No host sidebar/dashboard/route surgery
- [ ] Required capabilities declared; missing deps fail gracefully

**Documentation**

- [ ] `README.md` follows [`templates/module-readme-template.md`](templates/module-readme-template.md)

**Testing**

- [ ] Minimum tests (section 13) pass

**Portability proof**

- [ ] Installs in clean host from copy/Git only

---

## 15. Definition of Done

A module task is done when:

1. `module:doctor` and `module:install` succeed on a compatible host.
2. All declared contributions work; disable removes them; re-enable restores them.
3. Migrations run only on install; data preserved on disable.
4. Minimum tests pass.
5. README documents provides, requires, permissions, routes, tables, and install steps.
6. Certification checklist (section 14) is satisfied.
7. No Foundation architecture changes unless authorized by ADR.
8. `docs/current-state.md` updated if repository reality changed.

---

## 16. Foundation v1 Limitations

1. No `module:uninstall` — disable is terminal for removal of runtime behavior.
2. No `module:update` — replace files manually, run new migrations.
3. Capability-only dependencies (`requires.modules` does not exist).
4. Single provider per capability.
5. All module routes use `web` middleware group.
6. No `ContributesEvents` — register listeners in `boot()`.
7. No runtime settings module yet — use config + env until `modmon-settings`.
8. Route deactivation timing (see section 8).

Full appendix: [`module-authoring-standard-v1.md` Appendix B](module-authoring-standard-v1.md#appendix-b-known-foundation-v1-limitations).

---

## 17. SDK Quick Reference

```
App\Foundation\SDK\Contributions\ContributesRoutes
App\Foundation\SDK\Contributions\ContributesNavigation
App\Foundation\SDK\Contributions\ContributesDashboard
App\Foundation\SDK\Contributions\ContributesPermissions

App\Foundation\SDK\DTOs\NavigationItem
App\Foundation\SDK\DTOs\DashboardWidget
App\Foundation\SDK\DTOs\PermissionDefinition

App\Foundation\SDK\Contracts\CapabilityRegistryContract  # optional runtime lookup
```

Do not read Foundation source unless this document is ambiguous on a specific API.
