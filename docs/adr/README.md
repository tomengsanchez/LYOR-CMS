# Architectural Decision Records (ADRs)

Short, durable records of **why** PAPeR made a lasting technical choice. They complement:

| Artifact | Purpose |
|----------|---------|
| [DOCUMENTATION.md](../DOCUMENTATION.md) | Full software engineering doc map |
| [CHANGES.md](../CHANGES.md) / monthly `changes/` | What shipped and when |
| [DevelopmentHistory/](../DevelopmentHistory/) | Day-by-day implementation notes |
| [DEVELOPMENTGUIDE.md](../DEVELOPMENTGUIDE.md), [API_CONTRACT.md](../API_CONTRACT.md), etc. | How the system works now |
| [ERD.md](../ERD.md) | Schema relationships (Mermaid) |
| [UML.md](../UML.md) | Structure & behaviour (Mermaid UML) |
| **ADRs (this folder)** | Why a binding choice was made, and what was rejected |

## Status legend

| Status | Meaning |
|--------|---------|
| **Proposed** | Under discussion; not yet binding |
| **Accepted** | Current binding decision |
| **Deprecated** | No longer recommended; kept for history |
| **Superseded** | Replaced by a newer ADR (link it) |

## How to add an ADR

1. Copy the template below into `NNNN-short-kebab-title.md` (next free number, zero-padded to 4 digits).
2. One decision per file. Keep it roughly one screen.
3. Set **Status** to `Proposed` until the team accepts it; then `Accepted`.
4. Add a row to the index table in this README.
5. Mention the new ADR in the current month’s [changes](../changes/) entry when the decision is accepted or superseded.
6. Do **not** rewrite Accepted history — supersede with a new ADR instead.

Point at code and living docs (`API_CONTRACT.md`, restore CLI, etc.) instead of pasting full contracts into the ADR.

When a decision touches persistence, call out **MySQL/MariaDB compatibility** and **backup/restore** under Consequences.

## Template

```markdown
# ADR-NNNN: Title

- Status: Proposed | Accepted | Deprecated | Superseded by ADR-XXXX
- Date: YYYY-MM-DD
- Deciders: (optional)

## Context
What problem or constraint forced a choice?

## Decision
What we chose.

## Consequences
Positive, negative, and follow-on constraints.

## Alternatives considered
Brief list of options not chosen, and why.
```

## Index

| ADR | Title | Status |
|-----|-------|--------|
| [0001](0001-record-architecture-decisions.md) | Record architecture decisions | Accepted |
| [0002](0002-custom-php-mvc.md) | Custom PHP MVC (not a full framework) | Accepted |
| [0003](0003-mysql-mariadb-compatibility.md) | MySQL and MariaDB compatibility | Accepted |
| [0004](0004-session-web-auth-bearer-api-tokens.md) | Session web auth + Bearer API tokens | Accepted |
| [0005](0005-api-json-envelope.md) | API JSON success/error envelope | Accepted |
| [0006](0006-external-javascript-only.md) | External JavaScript only | Accepted |
| [0007](0007-role-capabilities.md) | Role capabilities for authorization | Accepted |
| [0008](0008-zip-backup-cli-restore.md) | ZIP backup and CLI-only restore | Accepted |
| [0009](0009-mobile-rest-additive.md) | Mobile REST additive to web domain | Accepted |
| [0010](0010-playwright-base-url-configurable.md) | Playwright `BASE_URL` configurable | Accepted |
