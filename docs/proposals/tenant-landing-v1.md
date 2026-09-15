# TenantLanding v1 Proposal (Phase 0 locked → Phase 1)

**Status:** Phase 1 implemented in-host (`Modules/TenantLanding`)  
**Module repo (intended):** `mwpn/modmon-tenant-landing`  
**Host:** compatible Foundation Contract `^1.0`  
**Date:** 2026-09-15  
**Depends on:** certified TenantDomains (`tenancy.domain`) public contract only

## 1. Purpose

Smallest portable **platform** Experience module that renders a public
guest landing for hostname-resolved tenants without mutating
`TenantContext`, without CMS/SEO/custom CSS/JS, and without hard-requiring
Branding.

## 2. Provides / requires

| Capability | Contract |
|------------|----------|
| `tenancy.landing` | `TenantLandingContract` |

**Requires:** `tenancy.domain` only.

**Optional runtime:** `branding.tenant` → `branding.application` →
`TenantRead.name`; `identity.authentication` + `identity.login` for CTA.

## 3. Intercept semantics

- Module-owned middleware on Http Kernel `web` group while enabled.
- `/` + successful `TenantDomainContract::resolve(host)` (attribute fast
  path when present) → guest landing.
- Miss (unknown/inactive domain or tenant) → pass-through.
- Never `TenantContextContract::setCurrent` / `clear`.

## 4. Minimal model

Table `tenant_landing`: unique `tenant_id` (no FK), nullable
`headline` / `summary`, `show_login_cta` (default true). No seed.

## 5. Non-goals

CMS, custom CSS/JS, SEO, DNS/SSL, subscription, workspace, auto-bind
hostname tenant to user context, Foundation/certified-module edits.

## 6. Gaps noted in Phase 1

`Router::pushMiddlewareToGroup('web')` alone does not reliably attach
middleware for Laravel 13 HTTP dispatch (Kernel sync overwrites). Landing
uses `Http\Kernel::appendMiddlewareToGroup` instead. Contract
`resolve(host)` remains the public fallback when TenantDomains request
attributes are absent.
