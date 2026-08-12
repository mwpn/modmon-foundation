# ADR-0001 --- Application Composition Foundation

Status: Accepted

## Context

The goal is to reuse more than code snippets. New products should be
assembled from compatible modules without repeatedly rebuilding
authentication, SaaS tenancy, subscription, dashboards and common
business capabilities.

A conventional "Laravel app with a Modules folder" does not guarantee
portability.

## Decision

Build a Laravel 13 modular-monolith composition foundation with a stable
Foundation Contract and portable self-describing modules.

The mandatory foundation is limited to Runtime, SDK, Infrastructure and
Experience Kernel.

Identity, RBAC, SaaS/Tenancy, Subscription and product/business
functionality are installable modules.

## Consequences

Positive: - products can be composed from a growing internal module
library; - host applications require less repeated coding; - agents have
stable contracts; - platform modules can be reused across business
verticals.

Costs: - module boundaries and compatibility must be maintained
carefully; - module public contracts need tests; - cross-module
shortcuts must be rejected.

## Rejected Alternatives

-   one large Core module containing all generic features;
-   independent codebases per SaaS product;
-   microservices;
-   direct cross-module model coupling.
