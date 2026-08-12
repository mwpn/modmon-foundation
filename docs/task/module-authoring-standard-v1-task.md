ModMon Foundation v1.0.0 has now been verified locally, pushed, and frozen.

Canonical repository:
https://github.com/mwpn/modmon-foundation.git

Frozen baseline:

* Tag: v1.0.0
* Commit: ad9caf38c4ce67234f17ca908ec2c0617beba56a
* Laravel: 13.24.0
* PHP: 8.3.31
* Local verification: 93 passed, 1 OS-dependent symlink test skipped, 177 assertions
* Production Vite build: PASS
* foundation:doctor: PASS

The next milestone is NOT to build Identity, RBAC, Tenancy, Subscription, or any other real module yet.

The next milestone is:

# ModMon Module Authoring Standard v1

## Goal

Define the canonical contract and workflow for building portable ModMon modules so future agents can create reusable modules without rediscovering or redesigning the architecture.

The central rule is:

> A module is not authored for one application.
> A module is authored against the ModMon Foundation Contract.

A compliant module should be portable between compatible ModMon applications without editing unrelated host source.

Target developer experience:

```
create module
    ↓
implement module contract
    ↓
module:doctor
    ↓
module verification/certification
    ↓
publish to Git
    ↓
copy/fetch into another compatible ModMon host
    ↓
module:install
    ↓
configure
    ↓
use
```

## Phase 1 — Inspect the frozen implementation

Inspect tag v1.0.0 and existing canonical documentation.

Do not redesign the Foundation.

Determine the actual APIs available for module authors, including:

* module.json
* module discovery
* compatibility declarations
* capabilities
* dependency resolution
* ModuleServiceProvider lifecycle
* contribution interfaces
* navigation
* dashboard/workspace widgets
* permissions
* routes
* migrations
* module state
* enable/disable behavior
* diagnostics
* existing Example module

Use implementation reality as the source of truth where documentation is ambiguous.

## Phase 2 — Define Module Authoring Standard v1

Create a canonical document such as:

```
docs/module-authoring-standard-v1.md
```

It must define at minimum:

### 1. Module identity

Define canonical rules for:

* module name
* module code
* PHP namespace
* semantic version
* module type
* directory naming
* Git repository naming

Recommended repository convention:

```
modmon-{module-code}
```

Examples:

```
modmon-identity
modmon-rbac
modmon-settings
modmon-tenancy
modmon-subscription
modmon-inventory
```

### 2. module.json

Specify the canonical manifest schema with examples.

Clearly distinguish:

* required fields
* optional fields
* compatibility
* provides
* requires
* provider
* module type
* version

Document validation and compatibility semantics.

Do not invent manifest fields unsupported by Foundation v1 unless explicitly proposed as a future extension.

### 3. Module directory structure

Define:

* required minimum structure
* optional directories
* naming conventions

Do not force empty architecture layers.

A simple module should remain simple.

### 4. Module Service Provider

Define exactly what module providers may and may not do.

Explain boot/register responsibilities and contribution registration.

Providers must not become God classes.

### 5. Capability design

Define canonical capability naming.

Examples:

```
identity.user
identity.authentication
authorization.permission
tenancy.tenant
subscription.entitlement
inventory.stock
```

Define:

* when to provide a capability
* when to require one
* capability ownership
* collision rules
* capability granularity
* required vs optional integrations

Avoid both overly broad and excessively granular capabilities.

### 6. Cross-module communication

Establish strict rules for:

* contracts/interfaces
* DTOs
* events
* query contracts
* capability lookup

Explicitly prohibit:

* importing another module's internal Eloquent models
* reaching into another module's internal repositories/services
* cross-module database table assumptions
* filesystem-path coupling
* hidden service-container string dependencies

Explain when to use synchronous contracts versus asynchronous/domain events.

### 7. Database ownership

Define:

> The module that creates a table owns that table.

Specify:

* migration ownership
* table naming
* foreign-reference strategy across modules
* no cross-module migrations that mutate another module's tables
* disable semantics
* data preservation
* destructive removal/uninstall policy

Pay special attention to the future Identity module because Laravel's default host currently contains the users migration/model.

Do NOT modify that yet.

Instead document this as a concrete ownership issue that must be resolved before implementing modmon-identity.

### 8. Routes and HTTP

Define:

* route ownership
* route naming
* URL prefix recommendations
* middleware usage
* controllers
* request validation
* API vs web routes

Respect the known Foundation v1 route lifecycle limitation:
route collection changes fully take effect on subsequent requests.

### 9. Permissions

Define permission naming convention, e.g.:

```
module.resource.action
```

Modules declare permissions but must not own the global authorization implementation.

The future RBAC module should consume permission definitions rather than business modules depending directly on RBAC internals.

### 10. Navigation

Define how modules contribute navigation without modifying the host sidebar.

Cover:

* workspace
* group
* ordering
* permission visibility
* route references

### 11. Dashboard/workspace contributions

Define how modules contribute widgets to workspaces/slots.

Business modules must not assume Owner/Tenant dashboards exist unless declared through capabilities/contracts.

### 12. Settings/configuration

Define distinction between:

* static developer configuration
* runtime application/module settings
* secrets

Do not hardcode a dependency on a future Settings module unless the capability is explicitly required.

### 13. Events

Define event ownership and naming.

Events are public integration surfaces once other modules depend on them.

Specify payload/DTO stability expectations.

### 14. Module lifecycle

Document exact semantics for:

```
discovered
installed
enabled
disabled
```

And operations:

```
copy
doctor
install
enable
disable
```

Copying a module must never mutate persistent state automatically.

Disable must preserve module data.

### 15. Versioning and compatibility

Define SemVer policy for modules.

Clarify:

* patch
* minor
* major
* Foundation compatibility
* Laravel compatibility
* breaking capability/contract changes

### 16. README contract

Every reusable module repository must contain a standardized README describing:

* purpose
* type
* compatibility
* provides
* requires
* optional integrations
* installation
* configuration
* permissions
* routes/public endpoints where relevant
* events published
* events consumed
* public contracts
* database ownership
* workspace/navigation contributions
* testing
* versioning

A future agent should be able to understand how to integrate a module primarily from module.json + README without broad source discovery.

### 17. Testing standard

Define minimum tests required for a portable module.

At minimum consider:

* manifest validation
* compatibility
* discovery
* installation
* capability registration
* dependencies
* routes
* migrations
* contributions
* disable
* re-enable
* data preservation
* architecture boundary tests

### 18. Portable Module Definition of Done

Create an explicit certification checklist.

A module must not be called portable merely because it works inside its development application.

The strongest proof should include installing the module into a clean compatible ModMon Foundation host without unrelated host edits.

## Phase 3 — Agent workflow

Update AGENTS.md and/or agent workflow documentation only where necessary.

Future agents creating modules should follow roughly:

```
read AGENTS.md
    ↓
read module-authoring-standard-v1.md
    ↓
inspect module.json + README of relevant modules
    ↓
inspect only relevant Foundation public contracts
    ↓
implement
    ↓
verify
    ↓
update module README/state
    ↓
report
```

The goal is to minimize expensive broad repository rediscovery.

## Phase 4 — Evaluate authoring tooling

Evaluate whether Foundation v1 needs small tooling such as:

```
php artisan module:make Foo
```

and/or:

```
php artisan module:verify foo
```

Do NOT automatically implement them.

First determine whether they are justified by repeated authoring requirements.

If implementation would alter the frozen Foundation Contract or significantly broaden scope, document the proposal instead.

If a tiny backward-compatible implementation provides substantial value, clearly explain it before making changes.

Do not create speculative framework machinery.

## Phase 5 — Example module review

Use Modules/Example as the reference implementation.

Audit whether Example conforms to the new Authoring Standard.

If documentation-only changes are sufficient, prefer documentation-only changes.

Do not turn Example into a complex fake business application.

## Important constraints

* Foundation v1.0.0 is frozen.
* Do not rewrite Foundation architecture.
* Do not implement real platform/business modules yet.
* Do not implement Identity.
* Do not implement RBAC.
* Do not implement Settings.
* Do not implement Tenancy.
* Do not implement Subscription.
* Do not add package dependencies without concrete justification.
* Do not introduce DDD/CQRS/event sourcing abstractions merely for architectural purity.
* Prefer simple Laravel-native mechanisms.
* Preserve backward compatibility with Foundation v1.0.0.
* Do not modify the v1.0.0 tag.
* Work on main after the v1.0.0 baseline.
* Every proposed standard must match what the current runtime can actually support.
* Clearly separate CURRENT v1 requirements from FUTURE proposals.

## Deliverables

At completion provide:

1. Module Authoring Standard v1.
2. Portable Module Definition of Done / certification checklist.
3. Standard module README template.
4. Canonical module.json example/template.
5. Agent workflow updates if required.
6. Example module compliance report.
7. Any proposed authoring tooling, with justification.
8. Any unresolved Foundation-contract limitations discovered.
9. Exact files changed.
10. Test/build status.
11. Git diff/status.

Do not commit, push, tag, or publish anything.

Stop after producing the reviewed working tree and final report so it can be inspected before acceptance.
