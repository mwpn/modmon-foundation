# Agent Workflow

## Purpose

Minimize repeated discovery and token waste while keeping agents safe.

## Normal Task

A new agent should normally need only:

1.  `AGENTS.md`
2.  `docs/current-state.md`
3.  `docs/requirements-locked.md`
4.  target module `README.md` + `module.json`
5.  one or two relevant architecture documents/ADRs

Do not tell an agent to read the entire repository by default.

## Task Prompt Template

``` text
Read AGENTS.md and docs/current-state.md first.
Read docs/requirements-locked.md.
Then read only the docs/ADRs relevant to this task and the target module README/manifest.

Task:
<single concrete task>

Constraints:
- preserve Portable Module Contract;
- no silent architecture redesign;
- no unrelated refactors;
- no new dependency without justification.

Acceptance:
<testable outcomes>

Verify:
<targeted tests/commands>

Update docs/current-state.md if repository reality changes.
```

## Module Authoring Task

For creating or modifying a portable module:

1.  `AGENTS.md`
2.  `docs/module-authoring-standard.md`
3.  `docs/templates/module-readme-template.md` and
    `docs/templates/module-json-template.json`
4.  target module `README.md` + `module.json`
5.  `module.json` + `README.md` of modules this module depends on

Do not read Foundation source unless the authoring standard is ambiguous
on a specific API.

After implementation, run the certification checklist (section 14 of
the authoring standard).

## Architecture Task

For an architecture change, additionally read: -
`docs/architecture.md` - `docs/portable-module-contract.md` -
`docs/dependency-rules.md` - relevant ADRs

Require a new/superseding ADR.

## Handoff

Every completed task should leave: - tests reflecting new invariants; -
current-state updated; - target module README updated if its public
contract changed; - exact remaining risks documented.

The goal is that the next agent reads state instead of rediscovering
history.
