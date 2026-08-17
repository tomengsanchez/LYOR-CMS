# ADR 0011: Simple CMS reset

## Status

Accepted — 2026-08-14

## Context

PAPeR was a domain-specific system (profiles, structures, grievances, SES, RAP). The product direction changed to a **Simple CMS** with standard content management (pages, posts, media) while keeping operational admin features.

## Decision

1. Archive all 100 domain migrations to `database/migrations_legacy/` (reference only).
2. Introduce a fresh 8-migration schema (`migration_000` through `migration_007`).
3. Remove domain modules, APIs, and admin tools tied to resettlement workflows.
4. Implement CMS modules: Pages, Posts, Categories, Media.
5. Retain: custom MVC framework, auth/2FA, users/roles, settings, backup/restore, audit trail, trimmed REST API.

## Consequences

- **Breaking:** Existing PAPeR databases are not migrated automatically; deploy requires a new database and `php cli/migrate.php`.
- **Positive:** Smaller codebase, clearer module boundaries, standard CMS UX.
- **Negative:** Historical PAPeR data remains in git history and `database/migrations_legacy/`; it is not active. Domain docs were removed from `docs/`.

## Alternatives considered

- Incremental deprecation with feature flags — rejected due to complexity and dual maintenance.
- Keep old schema and hide UI — rejected as messy long-term.
