# Settings v1 Compliance Report (relocated)

The Settings module was extracted from `modmon-foundation` into its own
repository after Phase 2 portability certification.

**Canonical location:** [mwpn/modmon-settings](https://github.com/mwpn/modmon-settings) —
see `docs/reports/settings-compliance-v1.md` in that repository.

**Result:** FULL COMPLIANCE with Module Authoring Standard v1 Portable Module
Definition of Done (certified 2026-09-13 on this Foundation host; module
tests and the full report live in `modmon-settings`).

Fresh GitHub-only proof (2026-09-13): clone Foundation `097f225` + copy
Settings from `mwpn/modmon-settings` `54b6867` into
`C:\laragon\www\modmon-settings-proof` — doctor/install/disable/enable,
contract round-trip, host sources unchanged, 28 module tests PASS.
**modmon-settings v1 portable certification closed.**

Provides `settings.runtime` (`RuntimeSettingsContract`). No Identity or
RBAC dependency.

Install:

```bash
git clone https://github.com/mwpn/modmon-settings.git /tmp/modmon-settings
cp -r /tmp/modmon-settings/Modules/Settings ./Modules/Settings
php artisan module:doctor settings
php artisan module:install settings
```
