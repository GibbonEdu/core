# Architecture Decision Records

Use an ADR for a decision that changes a durable boundary or creates upgrade
cost, including:

- adding or replacing a production dependency;
- choosing module, theme, or core ownership for a feature;
- adding a new core extension seam;
- changing schema ownership or integration boundaries;
- introducing a long-lived downstream core patch;
- changing deployment or upstream-sync strategy.

Copy `0000-template.md` to the next available four-digit number with a short
kebab-case title, for example `0002-add-a-core-extension-hook.md`. Open it as
`Proposed`; change it to `Accepted`, `Rejected`, `Deprecated`, or `Superseded`
through review. Do not rewrite an accepted decision to hide history. Add a new
ADR that supersedes it.

Keep ADRs concise. Link implementation pull requests, upstream issues, and the
affected section of `ARCHITECTURE.md`.
