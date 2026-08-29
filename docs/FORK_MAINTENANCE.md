# Downstream Fork Maintenance

This runbook keeps downstream work easy to identify, review, and replay when a
new Gibbon release is adopted.

## Repository Model

- `origin` is the team's fork.
- `upstream` should point to `https://github.com/GibbonEdu/core.git`.
- An upstream tracking branch or tag must remain an exact upstream reference.
- A downstream integration branch contains the small set of core deltas needed
  by this installation.
- Additional modules and themes should have independent repositories and
  release versions whenever practical.

Configure the official remote once per clone:

```bash
git remote add upstream https://github.com/GibbonEdu/core.git
git fetch upstream --prune --tags
git remote -v
```

If `upstream` already exists, verify its URL instead of replacing it.

## Classify Every Change

Before implementation, assign one category:

| Category | Destination | Expected lifetime in core fork |
| --- | --- | --- |
| Configuration | Runtime settings or deployment configuration | None. |
| Downstream feature | Additional module or theme | None. |
| Upstream bug fix | Focused core commit and upstream PR | Until included in an adopted release. |
| Extension seam | Generic core commit plus downstream module | Until included upstream, if accepted. |
| Unavoidable core customization | Focused core commit and delta inventory entry | Long-lived and actively maintained. |

If a change does not fit a category, write an ADR before coding.

## Branch and Commit Policy

- Start each feature or fix on its own branch from the intended integration
  base. Do not work directly on an exact upstream tracking branch.
- Keep one behavior change per commit where practical. Separate a generic core
  seam from the module that consumes it.
- Never mix an upstream sync, generated files, dependency updates, and feature
  code in one commit.
- Do not rebase a shared downstream integration branch. Rebase unpublished
  feature branches only when it improves review and no one else depends on them.
- Use merge commits when bringing a new upstream release into the published
  downstream history; this records which upstream state was adopted.

## Core Delta Inventory

Add one row for every committed, downstream-only change to upstream-owned files.
Remove the row when the delta is dropped or arrives in an adopted release.

| ID | Area and files | Reason it cannot be an extension | Upstream issue/PR | Removal condition | Owner |
| --- | --- | --- | --- | --- | --- |
| _Example D-001_ | _`path/to/file.php`_ | _Missing hook_ | _URL or N/A_ | _Release containing hook_ | _Team_ |

The inventory is not a substitute for small commits and tests. It is an index
for upgrade work.

## Adopt an Upstream Release

Use a clean worktree and a dedicated upgrade branch. Replace placeholders with
the actual downstream branch and upstream release tag/branch.

```bash
git status --short
git fetch origin --prune
git fetch upstream --prune --tags
git switch <downstream-integration-branch>
git pull --ff-only origin <downstream-integration-branch>
git switch -c upgrade/<new-version>
git merge --no-ff <upstream-release-tag-or-branch>
```

Resolve conflicts by intent:

1. Accept upstream for files with no recorded downstream behavior.
2. Reapply the smallest current form of each inventory delta.
3. Prefer a newly available upstream extension point over preserving an old
   core patch.
4. Update or remove delta rows and ADRs as decisions change.
5. Never resolve generated dependency/submodule conflicts by copying a stale
   downstream directory over the new upstream state.

Then install the locked dependencies, run the installer/update path on a copy of
production-like data, run automated tests and PHPStan, and manually exercise
every remaining delta.

Useful review commands:

```bash
git diff --stat <previous-upstream-ref>...HEAD
git diff --name-status <new-upstream-ref>...HEAD
git log --left-right --cherry-pick --oneline <new-upstream-ref>...HEAD
git diff --check
```

The second command is the current downstream footprint after the upgrade. Every
upstream-owned file in that list should be explained by an upstreamable commit
or an inventory entry.

## Sync Rules

- `gh repo sync` or a fast-forward is suitable only for a branch that contains
  no downstream commits.
- Never use force sync on the downstream integration branch.
- Sync frequently enough that conflicts are small, but perform upgrades in a
  branch so production can remain on the previous tested version.
- Back up the database and runtime files before applying Gibbon's one-way
  migrations.
- Version additional modules independently and verify compatibility with the
  target Gibbon release before upgrading production.

## Definition of Done for an Upgrade

- The merge ancestry identifies the adopted upstream release.
- Composer dependencies and Git submodules match the adopted revision.
- Clean install and upgrade paths complete on disposable environments.
- Relevant unit, acceptance, PHPStan, and manual role tests pass.
- The server log has no new warnings, deprecations, or uncaught exceptions.
- The core delta inventory matches the actual diff.
- Removed upstream deltas are deleted rather than carried forward inertly.
- Deployment notes include backup, rollout, and recovery steps.

Reference: [GitHub's official fork-sync guidance](https://docs.github.com/en/pull-requests/how-tos/work-with-forks/syncing-a-fork).
