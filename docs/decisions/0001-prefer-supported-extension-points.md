# 0001: Prefer Supported Extension Points

- Status: Accepted
- Date: 2026-08-21
- Owners: Downstream maintainers
- Related: `ARCHITECTURE.md`, `AGENTS.md`

## Context

This repository is a downstream fork of Gibbon Core. Direct changes to
upstream-owned files must be reconciled whenever a new Gibbon release changes
the same code. Gibbon officially supports additional modules, module hooks, and
themes, and recommends modules over core modifications for custom features.

## Decision Drivers

- Minimize conflicts and manual rework during upstream upgrades.
- Give downstream features an independent install and release lifecycle.
- Keep generally useful core fixes suitable for upstream contribution.
- Preserve Gibbon's action permissions, translations, and upgrade conventions.

## Considered Options

1. Implement downstream behavior with additional modules, hooks, and themes.
2. Modify core modules and root entry points directly.
3. Copy a core module and maintain the copy as a downstream replacement.

## Decision

Use existing settings first, then an additional module for downstream behavior
and an additional theme for application-wide presentation. Keep long-lived
extensions in independent repositories whenever practical.

If an extension cannot work without a core change, add the smallest generic
extension seam to core and keep the feature implementation in the extension.
Develop general core defects as isolated, upstream-ready commits. A direct
downstream core customization requires a delta-inventory entry and, when it
changes a durable boundary, another ADR.

Do not copy and fork a bundled core module as an extension strategy.

## Consequences

### Positive

- Most feature development does not overlap upstream-owned files.
- Modules and themes can be versioned and tested against multiple core releases.
- Remaining core differences are small enough to review and propose upstream.

### Negative

- Extensions require their own packaging, versioning, installation, and upgrade
  tests.
- Some deeply integrated features may first need a new generic core hook or
  service boundary.
- Cross-module assumptions must be avoided or expressed through stable hooks.

## Upstream and Upgrade Impact

The default path creates no core delta. Generic seams and core fixes should be
submitted upstream and remain in the core delta inventory until an adopted
release contains them. Every upstream upgrade must verify extension version
compatibility and exercise install/update behavior.

## Validation

- The core diff contains only upstreamable fixes, generic seams, and inventoried
  exceptions.
- Each extension can be installed and upgraded through Gibbon's module/theme
  lifecycle.
- Permission, translation, schema upgrade, and supported hook behavior are
  covered for the extension.
