# ADR-0013: Experience floating slot

Status: Accepted. Foundation Contract 1.4.0 → **1.5.0** (additive).

## Context

A module may need to show a small, closable element in a corner of every
authenticated page — a system notice, an upgrade nudge, a support
widget — not only the dashboard. A module can only contribute to the
Experience through:

-   navigation items;
-   dashboard widgets (`workspace.*.dashboard.*` slots);
-   the brand hook.

Nothing renders module views on every page of the app shell, and the
Kernel must not know which business module wants this.

## Decision

Add one shell slot, `shell.floating`, and raise the Contract to
**1.5.0**:

-   `DashboardWidget::SHELL_FLOATING = 'shell.floating'`. Modules
    register a `DashboardWidget` for that slot, the same way they
    contribute dashboard widgets.
-   The app shell renders the slot's widgets in order at the end of
    `<body>`, on every authenticated page, outside the content grid.
-   The slot only gives the position. Each widget decides for itself:
    -   whether it shows (permission, workspace, its own rules);
    -   where it floats: one corner each, never covering primary
        actions;
    -   how it is closed.
-   A floating widget renders nothing when it has nothing to say. It
    must be cheap (it runs on every page) and must not throw.

The guest shell has no floating slot.

## Consequences

-   The first module that needs a persistent corner notice (a billing
    nudge, a maintenance banner, a support widget) uses the slot instead
    of asking for a new Kernel hook.
-   Modules using the slot declare `compatibility.foundation: "^1.5"`;
    other modules are unaffected.
-   One more place where module views run on each request. The rules
    above keep that cheap.
