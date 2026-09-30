# Workspace

Portable authenticated workspace composition for ModMon hosts. Binds
Foundation `ActiveWorkspaceContract` to `workspace.owner` or
`workspace.tenant` using resolved domain request attributes (when
present) or Tenancy current-tenant context. Does **not** mutate
`TenantContext` and does **not** require TenantDomains.

## Type

`platform`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.1`     |

Requires Foundation Contract **1.1.0+** so `module:doctor` /
`module:install` reject hosts that still report
`CompatibilityChecker::FOUNDATION_VERSION` `1.0.0`, even when
`ActiveWorkspaceContract` already exists on disk. Constraint is
evaluated before provider boot or install.

## Provides

| Capability         | Description |
|--------------------|-------------|
| `workspace.owner`  | Owner/platform authenticated Experience workspace. |
| `workspace.tenant` | Tenant authenticated Experience workspace. |

## Requires

| Capability         | Why |
|--------------------|-----|
| `tenancy.context`  | Fallback selection from current-tenant context. |

Does **not** require `tenancy.domain`, Branding, RBAC, or Identity auth
capability (host may wrap `/dashboard` with `auth` as usual).

## Optional Integrations

| Signal | Behavior when available |
|--------|-------------------------|
| Request attribute `tenant_domain.tenant_id` | When set by TenantDomains (or compatible middleware), selects `workspace.tenant` without importing TenantDomains. |

## Installation

```bash
php artisan module:install identity
php artisan module:install tenancy

cp -r modmon-workspace/Modules/Workspace /path/to/host/Modules/Workspace
php artisan module:doctor workspace
php artisan module:install workspace
```

Copying the module never runs migrations or mutates runtime state.

### Disable / Enable

```bash
php artisan module:disable workspace
php artisan module:enable workspace
```

Disable removes capabilities and Experience contributions. Foundation
returns to `DefaultActiveWorkspace` (`workspace.default`) on a fresh
boot. Tenancy/domain data is preserved.

## Active workspace selection

1. Request attribute `tenant_domain.tenant_id` present and positive → `workspace.tenant`
2. Else authenticated user with non-null `TenantContextContract::currentTenantId` → `workspace.tenant`
3. Else → `workspace.owner`

## Experience contributions

| Kind | Id | Workspace |
|------|-----|-----------|
| Nav | `workspace.owner.home` | `workspace.owner` |
| Nav | `workspace.tenant.home` | `workspace.tenant` |
| Widget | `workspace.owner.marker` | `workspace.owner.dashboard.main` |
| Widget | `workspace.tenant.marker` | `workspace.tenant.dashboard.main` |

Platform nav items with `workspace: null` remain global.

## Public contracts

None beyond rebinding Foundation `ActiveWorkspaceContract` while enabled.

## Version History

| Version | Description |
|---------|-------------|
| 1.0.0   | Context/domain-driven ActiveWorkspace binder + minimal Experience markers. Requires Foundation `^1.1`. |
