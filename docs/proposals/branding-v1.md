# Branding v1 Proposal (Phase 0 locked → Phase 1)

**Status:** Extracted — `mwpn/modmon-branding` v1.0.0 certified portable  
**Module repo:** `mwpn/modmon-branding`  
**Host:** compatible Foundation Contract `^1.0`  
**Date:** 2026-09-15

## 1. Purpose

Ship the smallest portable **platform** Branding module that owns
application-wide visual identity (name, logos, favicon, colors, generic
login copy) behind a public contract — without Tenancy, Settings,
Identity, or Foundation coupling.

## 2. Provides / requires

| Capability | Contract |
|------------|----------|
| `branding.application` | `BrandingContract` |

**Requires:** `[]`

Does **not** require `settings.runtime`, Identity, RBAC, or Tenancy.
Persistence is module-owned (typed singleton table), not Settings KV.

## 3. Minimal domain + ownership

Table `branding_application` (singleton, **no seed**, **no tenant_id**):

- `name` (nullable until configured; defaults via contract)
- `logo_path`, `logo_dark_path`, `favicon_path` (module-owned paths)
- `primary_color`, `accent_color` (validated hex)
- `login_title`, `login_subtitle` (optional generic copy)

DTO exposes **resolved public URLs**, not raw storage internals as the
primary consumer API.

## 4. Experience

Phase 1: minimal admin edit + field-scoped image upload; routes, nav,
`branding.manage` permission; optional Blade mark/css-vars components.
No AppShell or Identity edits. No dashboard widget required.

## 5. Non-goals

Tenant branding, custom domains, TenantLanding, Subscription, theme/page
builder, arbitrary CSS/JS, media manager framework, Foundation changes.

## 6. Foundation gaps

None expected for Phase 1 (same posture as Settings/Inventory/Tenancy).
Report and stop if a real Foundation gap appears.

## 7. Tenant override (future)

Separate **integration** module (e.g. TenancyBranding) owns overrides;
Branding core stays application-scoped.
