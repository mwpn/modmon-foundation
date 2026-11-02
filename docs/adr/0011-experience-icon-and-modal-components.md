# ADR-0011: Shared icon and modal components in the Experience Kernel

Status: Accepted. Foundation Contract 1.2.0 → **1.3.0** (additive).

## Context

Most modules need icons and delete confirmations. Left to themselves:

-   module views confirm deletes with the browser's `confirm()` (looks
    different per browser, cannot be styled, no context in the message);
-   icons end up as inline `<svg>` copies or module-private sets;
    `NavigationItem::icon` accepts only raw SVG markup;
-   short add/edit forms (a few fields, one column) are either full
    pages or inline rows toggled with Alpine, built differently in each
    module.

A module cannot provide this. Modules depend on each other only through
capabilities, never through another module's views. A shared UI module
would turn a UI primitive into a hard dependency of every module and
break the portable-module rule (copy a module to a host without the UI
module → broken views). The Experience Kernel is mandatory on every host
and already ships the shared Blade components (`x-foundation::button`,
`card`, `badge`, `alert`, …); `docs/ui-composition.md` already lists
`modal` as one of them.

## Decision

Add three anonymous components to the Experience Kernel and raise the
Foundation Contract to **1.3.0**:

1.  **`<x-foundation::icon name="…" />`** — a curated outline icon set
    (24 × 24, `currentColor`) in `App\Foundation\Experience\Icons`. Unknown
    names render nothing. No icon package.
2.  **`<x-foundation::modal id="…">`** — opened with
    `$dispatch('open-modal', id)`, closed with `close-modal`, Esc or the
    backdrop; teleported to `<body>` (layout stacking contexts cannot
    cover it); a centred dialog on desktop and a bottom sheet on phones.
    With `action` it renders the form itself (CSRF, method spoofing, a
    hidden `_modal` field, Cancel/Submit). After a failed server-side
    validation the modal with that `_modal` id opens again with old input
    and the first error; `App\Foundation\Experience\Modal::old()` returns
    old input only for the modal that failed, and `Modal::checked()` the
    state of a checkbox (an unticked box is not submitted). A redirect
    `->with(Modal::REOPEN, $id)` opens the modal again after a successful
    save ("save and add another").
3.  **`<x-foundation::confirm-delete action="…">`** — a trigger button
    plus a confirmation modal with a DELETE form; replaces `confirm()`.

`NavigationItem::icon` also accepts an icon name from the set; raw SVG
markup keeps working.

Usage rule (authoring standard): modals for short forms only (about five
fields or fewer, one column, no multi-file upload, not the page's main
repeated task). Long forms stay full pages; destructive actions use
`confirm-delete`. Validation stays server-side.

## Consequences

-   Modules that use these components declare
    `compatibility.foundation: "^1.3"`; existing modules (`^1.0`–`^1.2`)
    are unaffected.
-   Modules migrate when they are next changed for a feature, not all at
    once.
-   An Experience implementation module (`x-experience::*`, see
    `ui-composition.md`) must provide the same three components if it
    replaces the Kernel's.
