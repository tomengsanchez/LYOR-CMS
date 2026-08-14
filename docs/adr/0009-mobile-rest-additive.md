# ADR-0009: Mobile REST additive to web domain

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization of mobile integration approach

## Context

A mobile/third-party client needs the same profiles, structures, and grievances as the web app, without a parallel schema or divergent business rules.

## Decision

Expose **additive REST** under `/api/*` that reuses web domain models, capabilities, project scoping, multipart field names, and serve routes where possible.

- Contract and onboarding: `docs/API_CONTRACT.md`, `docs/MOBILE_APP_INTEGRATION.md`, `docs/MOBILE_EXCHANGES.md`
- Prefer new list/detail/write endpoints that mirror web behavior (e.g. structure list, attachment cards) rather than mobile-only tables
- Keep response envelope and auth per ADR-0004 and ADR-0005

## Consequences

- Positive: One source of truth; mobile gains features as web rules evolve; backup/restore unchanged when APIs are read/write against existing tables/files.
- Negative: API must carefully preserve partial-update semantics (omit vs clear); web-only UX (some 2FA paths) may block API login.
- Follow-on: Breaking mobile contracts requires versioning or exchange-log agreement; update Postman when routes change.

## Alternatives considered

- **Separate mobile BFF + DTOs** — isolates clients; duplicates validation and drifts from web.
- **GraphQL** — flexible queries; new stack and auth story for limited client count.
- **Fully independent mobile schema** — sync hell; rejected.
