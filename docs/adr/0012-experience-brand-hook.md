# ADR-0012: Experience brand hook

Status: Accepted. Foundation Contract 1.3.0 → **1.4.0** (additive).

## Context

A host running the Foundation may want its sign-in and app shell to show
its own name, logo and colour instead of a bare `config('app.name')` —
and, on a multi-tenant host, let a tenant override that brand on its own
hostname.

Today nothing can do that:

-   the Experience Kernel's guest shell and app shell print
    `config('app.name')`, an initial letter and a fixed tagline;
-   branding data naturally lives in installable modules (application
    branding, tenant branding, a tenant's own profile module), but no
    Experience surface reads them — the Kernel must not depend on those
    modules.

Several modules may know part of the brand for a request (application
branding, a tenant's own data, an operator's per-tenant override), and
their service providers register in installation order, which says
nothing about which source should win.

## Decision

Add an Experience brand hook to Foundation and raise the Contract to
**1.4.0**:

-   `App\Foundation\SDK\DTOs\ExperienceBrand` — name, logo / dark logo /
    favicon URLs, primary colour (`#rrggbb`), headline and tagline for
    the sign-in panel, the app shell's mark style (`logo_name`, `logo`,
    `name`) and a shorter panel name, and the tenant id when the brand is
    tenant-specific.
-   `App\Foundation\SDK\Contracts\ExperienceBrandContract::current()` —
    request-scoped (`scoped` binding). The Kernel's default returns
    `config('app.name')` and nothing else.
-   Modules contribute by **decorating** the binding
    (`$app->extend(ExperienceBrandContract::class, …)`) and returning
    `$inner->current()->overlay([...], level, tenantId)`. Each field
    keeps the level it was set at; a value replaces it only from the
    same or a higher level, so the result does not depend on
    registration order. Levels: `LEVEL_APPLICATION` (10, application
    branding), `LEVEL_TENANT` (20, the tenant's own data), and
    `LEVEL_TENANT_OVERRIDE` (30, an operator's explicit per-tenant
    branding).
-   The guest shell and app shell render the brand: name, logo (initial
    tile as fallback), favicon, sign-in headline/tagline (also on
    phones), and the primary colour as the `--color-brand-*` scale
    (`color-mix` from one colour; no build step).
-   Shell texts go through `__()`, so a host can ship its own `lang/`
    translations without touching the Kernel.

## Consequences

-   Branding-style modules become visible through the shells without the
    Kernel knowing them by name.
-   Modules decorating the brand declare `compatibility.foundation:
    "^1.4"` — or, when the brand is an optional extra, guard the
    decoration with `interface_exists(ExperienceBrandContract::class)`
    and keep their older constraint. Other modules are unaffected.
-   A brand provider must be cheap (it runs once per request that
    renders a shell) and must not throw; it returns its inner brand when
    it has nothing to add.

## Amendment: rendering the mark

No contract change. The shell and the sign-in page follow `markStyle`
the same way:

-   `logo`: the logo at its own width. On the dark sign-in panel it sits
    on a light plate unless `logoDarkUrl` is set. On a phone the plate
    appears only in dark mode. The collapsed sidebar shows the square
    mark instead.
-   `logo_name`: the square mark (`faviconUrl`, else the logo) next to
    the name. A wide logo lockup never squeezes into a square spot.
-   `name`: the name only. An initial tile marks the collapsed sidebar.

Brand image URLs should be host-relative. The same shell may run on
several hostnames (a tenant subdomain, a custom domain, a companion
app's host).
