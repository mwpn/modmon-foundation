# ADR-0004 --- Experience Is Composed

Status: Accepted

## Context

A reusable foundation may serve non-SaaS apps, SaaS owner panels, tenant
panels and future workspaces. A single business-aware dashboard would
couple the foundation to one product type.

## Decision

Experience Kernel provides composition registries and shared UI
contracts.

Owner Dashboard, Tenant Dashboard and similar workspaces are installable
modules/capabilities.

TailAdmin is a visual implementation/reference, not a business-module
dependency.

## Consequences

Feature modules can contribute navigation/widgets/settings to compatible
workspaces without editing global shells.

## Rejected Alternatives

-   one hardcoded dashboard for all roles/products;
-   cloning TailAdmin layout into every module;
-   putting owner/tenant business rules inside the Foundation dashboard.
