# ADR-0005: API JSON success/error envelope

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization of `Core\Controller` API responses

## Context

Integrators (mobile apps, Postman, first-party `fetch` dashboards) need a predictable JSON shape so success payloads and errors can be handled uniformly across `/api/*`.

## Decision

All `/api/*` JSON responses use the envelope:

```json
{ "success": true|false, "data": ..., "error": null|{ "code", "message", "details?" } }
```

- Success/error helpers: `apiSuccess()` / `apiError()` (and related) in API controllers
- Stable `error.code` values from `App\ApiErrorCode` (see `docs/API_ERROR_CODES.md`, `GET /api/meta/error-codes`)
- Clients must read domain fields from **`data`**, never assuming top-level business keys

Canonical contract: `docs/API_CONTRACT.md`.

## Consequences

- Positive: One parsing pattern for web JS and mobile; clear HTTP status + machine codes.
- Negative: Nested payloads (`data.items`, etc.); legacy or ad-hoc top-level keys are invalid.
- Follow-on: Keep envelope normalization in `Core\Controller`; update Postman + mobile docs when adding endpoints. Backup/restore unaffected (response shape only).

## Alternatives considered

- **Bare resource JSON** (no envelope) — simpler for some clients; inconsistent error shape and harder shared helpers.
- **JSON:API / HAL** — richer hypermedia; more verbose than needed for this API surface.
