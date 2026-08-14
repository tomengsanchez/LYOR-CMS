# ADR-0004: Session web auth + Bearer API tokens

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization of the existing auth model

## Context

Browsers need cookie/session login (CSRF-protected form posts, optional idle timeout, optional email 2FA, session inventory). Mobile and third-party clients need token auth without a browser session.

## Decision

Use **two complementary auth modes**:

1. **Web:** PHP session via `Core\Auth` (login regenerates session id; capabilities; optional 2FA and idle logout).
2. **API:** When no session exists on `/api/*`, accept `Authorization: Bearer <token>` validated by `App\ApiToken` (hashed rows in `api_tokens`; absolute + idle expiry). Controllers use `requireAuthApi()` for 401 JSON.

CSRF applies to web POSTs, not Bearer API calls. Details: `docs/API_AUTH.md`, `docs/API_CONTRACT.md`.

## Consequences

- Positive: Same user/capability model for web and API; mobile can authenticate without cookies.
- Negative: Two idle/expiry paths to reason about; when email 2FA is on, API login is a two-step challenge (`pending_2fa` → `/api/auth/2fa/verify`) instead of issuing a token immediately (see `docs/API_AUTH.md`).
- Follow-on: Never put long-lived secrets in client JS; revoke tokens on logout/compromise; keep token hashes only in DB (backup/restore includes `api_tokens`).

## Alternatives considered

- **Session-only API** — awkward for native mobile and non-browser integrators.
- **JWT without server storage** — harder immediate revoke/idle tracking; project already stores hashed tokens.
- **OAuth2/OIDC provider** — disproportionate for current first-party mobile + Postman use cases.
