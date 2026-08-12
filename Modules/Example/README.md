# Example Module

Non-business portable module proof for Foundation v1.

## Purpose

Proves the complete portable module lifecycle:

- Discovery via `module.json`
- Manifest validation
- Compatibility diagnostics (`module:doctor example`)
- Explicit installation (`module:install example`)
- Route contribution (`/example`, `/example/about`)
- Navigation contribution (sidebar link)
- Dashboard widget contribution (welcome card, stats card)
- Permission declaration (`example.view`, `example.manage`)
- Capability provision (`example.demo`)
- Disable removes runtime contributions, preserves data
- Re-enable restores contributions

## Manifest

See `module.json` for the full manifest.

## Contributions

| Type        | Details                                    |
|-------------|--------------------------------------------|
| Routes      | `/example`, `/example/about`               |
| Navigation  | "Example" sidebar item (Modules group)     |
| Widgets     | Welcome card, stats card                   |
| Permissions | `example.view`, `example.manage`           |
| Capabilities| `example.demo`                             |
| Migrations  | `example_entries` table                    |
