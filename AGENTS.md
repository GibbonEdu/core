# Repository Guide for Coding Agents

This repository is a downstream fork of Gibbon Core. The primary engineering
constraint is to preserve a low-conflict upgrade path from Gibbon upstream.
Correctness, privacy, and maintainability take precedence over speed.

## Required Context

Before changing code:

1. Read `ARCHITECTURE.md`, then the nearest relevant code and tests.
2. Read `docs/FORK_MAINTENANCE.md` for branch and upstream-sync rules.
3. Inspect `git status` and the current diff. Preserve changes you did not make.
4. Check the official Gibbon developer docs when an extension or migration
   contract is unclear. Do not infer a new convention from one legacy file.

`AGENTS.md` is the canonical AI instruction file. Keep tool-specific files as
thin pointers to this file so instructions cannot silently diverge.

## Change Placement

Choose the least invasive supported extension point in this order:

1. Use existing configuration, settings, permissions, or hooks when they meet
   the requirement without code changes.
2. Put school-specific workflows and new product features in an additional
   module, ideally maintained in its own repository and installed under
   `modules/<Module Name>`.
3. Put visual overrides in an additional theme under `themes/<Theme Name>`.
4. Change Gibbon Core only for a generally useful upstream fix, a required
   extension seam, or behavior that cannot be implemented safely as a module.

When a core seam is unavoidable, keep the seam small and place the actual
feature in a module. Do not copy a core module and maintain it as a fork.

General bug fixes in upstream-owned files must be self-contained and free of
downstream branding or assumptions so they can be proposed upstream. Record
long-lived core deltas in the inventory in `docs/FORK_MAINTENANCE.md`.

Do not edit these as part of normal feature work:

- `vendor/`: Composer-managed and gitignored.
- `i18n/` and `resources/build/`: Git submodules with separate histories.
- Generated/minified assets when the source asset is available.
- `uploads/`, `.env`, or `config.php`: runtime data or local secrets.
- Historical entries in `CHANGEDB.php`: migrations are append-only.

## Architecture Rules

Gibbon v31 is intentionally hybrid. Legacy page scripts and globals coexist
with PSR-4 classes, a DI container, gateways, Twig, and reusable UI services.
Match the modern pattern used by nearby code without broad rewrites.

- Bootstrap through `gibbon.php`; do not create a second application bootstrap.
- Obtain services and gateways from `$container` where the surrounding code
  does so. Put reusable domain/data logic in namespaced classes, not page files.
- Keep page scripts thin: authorize, validate, call domain/data code, and render
  or redirect. Do not introduce new business logic into `functions.php`.
- Use `Gibbon\Domain` gateways or parameterized connection methods for database
  access. Never concatenate request data into SQL.
- Additional module classes use
  `Gibbon\Module\<ModuleNameWithoutSpaces>\...` and live in the module's `src/`
  directory. The current module namespace is registered at request time.
- Use module `templates/`, `css/module.css`, and `js/module.js` for module-owned
  presentation. Use a theme only for cross-application presentation changes.
- Avoid new dependencies between modules. If cross-module behavior is required,
  prefer a stable core service or a documented hook.
- Follow `.editorconfig` and the established Gibbon coding standards. New
  classes are PSR-4 namespaced, one class per file, with the repository license
  header used by neighboring PHP files.
- Wrap user-facing text with the existing translation helpers. Additional
  modules must use their module translation domain.

## Security and Data

This system stores student, family, staff, medical, attendance, and financial
data. Treat every change as privacy-sensitive.

- Enforce both authentication and the specific action/role permission. When
  calling `isActionAccessible()`, hard-code the action path.
- Preserve the established CSRF-token and nonce flow for mutating forms and
  process scripts.
- Validate identifiers and enumerated values server-side. Do not trust hidden
  fields, query strings, filenames, MIME types, or client-side validation.
- Bind every untrusted SQL value. Use existing gateways and upload handlers
  rather than adding ad hoc SQL or filesystem logic.
- Escape untrusted output with the local HTML/Twig convention. Do not render
  request or database content as raw markup without a reviewed sanitization
  boundary.
- Do not log credentials, session tokens, or unnecessary personal data.

## Database Changes

- A custom module owns its schema in its own `manifest.php` and `CHANGEDB.php`.
  Do not add module schema to the root `CHANGEDB.php`.
- Root core migrations are reserved for upstream-ready core changes.
- Append a new statement to the current version block; never edit a migration
  that another installation may already have run.
- Separate statements with `;end`, as required by Gibbon's updater.
- Pair schema changes with version metadata, upgrade coverage, and a rollback
  or recovery note. Gibbon migrations themselves are one-way.
- Test migrations against a disposable database and back up real data before an
  upgrade.

## Setup and Commands

Docker is the portable development path for this checkout:

```bash
./up.sh
./up.sh logs
```

`./up.sh` creates `.env` from `ops/.env-example` when needed and prints the
actual local URL. `./up.sh down` removes Docker volumes, and `./up.sh reset`
also deletes `config.php`; both are destructive to the local installation.

Useful checks once the containers are running:

```bash
docker compose --project-directory . exec -T app composer test:phpunit
docker compose --project-directory . exec -T app composer test:phpstan
docker compose --project-directory . exec -T app sh -lc \
  'cd tests && ../vendor/bin/codecept run unit Services/FormatTest.php'
```

Run `composer test` only against a disposable test database configured as in
`.github/workflows/ci.yml`; the install and acceptance suites mutate data.

If front-end build sources are changed, initialize the submodules and use the
commands defined by `resources/build/package.json`. Do not guess build commands
while the submodule is uninitialized.

## Verification

Scale verification to the change:

- Documentation only: check links, commands, spelling, and `git diff --check`.
- Domain/service/gateway change: focused unit tests plus PHPStan.
- Page, permission, form, or workflow change: focused acceptance coverage and a
  manual role/permission check.
- Schema or installer change: clean install and upgrade from the previous
  supported version using a disposable database.
- CSS, JavaScript, template, or theme change: build assets when applicable and
  inspect the affected page at narrow and wide viewports.

Before finishing, review the complete diff against the task's base branch. Note
tests that were not run and why.

## Review Rules

During review, flag these as architecture or correctness issues:

- A downstream-only feature edits core when a module, theme, setting, or hook
  can implement it.
- A core patch mixes an extension seam with downstream feature behavior.
- Permission checks, CSRF/nonce checks, validation, output escaping, or SQL
  binding are missing.
- An existing migration is changed instead of appending a corrective migration.
- Runtime data, secrets, dependency output, submodule output, or generated files
  are committed unintentionally.
- Tests cover the happy path but omit role restrictions, invalid input, or the
  upgrade path relevant to the change.
