# Settings v1 Compliance Report

**Module:** Settings (`Modules/Settings`)  
**Version:** 1.0.0  
**Standard:** Module Authoring Standard v1 §14–15 (portable Definition of Done)  
**Date:** 2026-09-13  
**Host:** this Foundation authoring host (`modmon-foundation`) — Settings
authored in-tree; Phase 2 is compliance only (no new features).

**Result:** **FULL COMPLIANCE** for Phase 1 scope (runtime store +
`RuntimeSettingsContract`). Admin UI / Experience contributions are
explicitly out of scope and marked N/A.

## Portability procedure

Isolated SQLite proof on this host (no MySQL mutation, no host source edits):

1. Force isolated `database/settings-compliance-proof.sqlite`.
2. Clear `storage/app/modules.json` (discovered-only).
3. Baseline host migrate.
4. Capture SHA-256 of watched host files.
5. `module:doctor settings` → all checks passed.
6. `module:install settings` → migrations applied; enabled;
   `settings.runtime` registered; contract bound.
7. `RuntimeSettingsContract::set/get` → `settings.app.name = CLI-Proof`.
8. `module:disable settings` → capability removed; row preserved.
9. `module:enable settings` → capability restored; value `CLI-Proof`.
10. Re-hash watched host files → **identical**.

Watched host files (all unchanged):

- `bootstrap/app.php`
- `routes/web.php`
- `config/app.php`
- `config/auth.php`
- `composer.json`
- `composer.lock`
- `app/Foundation/FoundationServiceProvider.php`
- `app/Foundation/Runtime/ModuleManager.php`

Automated coverage: `Modules/Settings/Tests/Feature/SettingsComplianceTest.php`
(same checklist + migration fail-closed + empty requires).

## Certification checklist

### Manifest & identity

| Check | Status | Notes |
|-------|--------|-------|
| Valid `module.json` | PASS | Schema 1, code `settings`, version `1.0.0`, type `platform` |
| Provider loadable | PASS | `Modules\Settings\SettingsServiceProvider` |

### Lifecycle

| Check | Status | Notes |
|-------|--------|-------|
| Discovered before install | PASS | State null; `settings_entries` absent; capability absent; contract unbound |
| `module:doctor settings` before install | PASS | All checks passed |
| `module:install settings` owns migration | PASS | Creates `settings_entries`; reports Migrations applied |
| `settings.runtime` after install | PASS | Capability + contract available |
| Contract works after install | PASS | get/set/has/forget/all (Phase 1 suite) |
| Disable removes runtime capability | PASS | `settings.runtime` false; Experience N/A (none declared) |
| Disable preserves rows | PASS | Keys survive disable |
| Re-enable restores capability + values | PASS | Capability true; persisted values returned |
| Migration failure fail-closed | PASS | Conflicting table → Migration failed; NOT installed; no capability |

### Boundaries

| Check | Status | Notes |
|-------|--------|-------|
| No Identity/RBAC dependency | PASS | `requires.capabilities: []` |
| No Identity/RBAC/Foundation Runtime/Experience imports | PASS | `SettingsBoundaryTest` |
| Public-contract only | PASS | Consumers use `RuntimeSettingsContract` |
| No unrelated host source edits | PASS | Watched SHA-256 identical across install/disable/enable |

### Contributions (Phase 1 scope)

| Check | Status | Notes |
|-------|--------|-------|
| Routes / nav / permissions / dashboard | N/A | None in Phase 1 (by design) |
| Capability `settings.runtime` | PASS | Registered on enable; removed on disable |

### Documentation & tests

| Check | Status | Notes |
|-------|--------|-------|
| README contract sections | PASS | Provides, requires, contract semantics, ownership, install |
| Module tests | PASS | `php artisan test Modules/Settings/Tests` |

## Foundation gaps

| Gap | Blocker? | Action |
|-----|----------|--------|
| No `ContributesSettings` schema contribution API | No | Reported in Phase 0; not required for store/contract. Do not patch until Architecture Change Protocol. |

No new Foundation contract gap discovered in Phase 2.

## Verdict

**FULL COMPLIANCE** for Settings Phase 1 portable store. Ready for later
extraction to `modmon-settings` after any remaining authoring phases
(admin UI optional; not required for this certification of the store).

## Install (copy-only)

```bash
cp -r Modules/Settings /path/to/host/Modules/Settings
php artisan module:doctor settings
php artisan module:install settings
```

No host source surgery. No Identity/RBAC required.
