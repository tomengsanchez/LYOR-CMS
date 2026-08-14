# API Error Codes

Stable machine-readable codes for all **`/api/*`** error responses. Mobile and third-party clients should branch on **`error.code`**, not on English **`error.message`** text.

## Envelope

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "At least one contact number is required.",
    "details": { "field": "contacts_number" }
  }
}
```

- **`details`** is optional and may include **`field`**, **`rule`**, or other context.
- Success responses use **`success: true`** with payload under **`data`**.

## Registry

| Code | HTTP | When | Mobile action |
|------|------|------|----------------|
| `UNAUTHORIZED` | 401 | Missing or invalid Bearer token | Clear token → login |
| `UNAUTHORIZED_CLIENT` | 401 | Missing/invalid `X-App-Id` / `X-App-Secret` when API client gate is on | Fix app credentials (not user password); contact admin if rotated |
| `FORBIDDEN` | 403 | Missing role capability | Hide action / show permission message |
| `FORBIDDEN_PROJECT` | 403 | `project_id` not in user's allowed projects | Refresh `GET /api/projects` |
| `BAD_REQUEST` | 400 | Invalid parameters or body | Fix input |
| `VALIDATION_ERROR` | 400 | Business rule failed (location, age, GRM options, etc.) | Show message; highlight `details.field` |
| `NOT_FOUND` | 404 | Entity or project not found | Back / refresh list |
| `CONFLICT` | 409 | Duplicate or conflicting state | Let user change inputs |
| `TWO_FACTOR_REQUIRED` | 403 | Server cannot start 2FA for this account (legacy) | Contact administrator |
| `TWO_FACTOR_NO_EMAIL` | 403 | 2FA enabled but account has no email | Contact administrator |
| `TWO_FACTOR_SEND_FAILED` | 502 | SMTP failure when emailing the OTP | Retry login or resend |
| `TWO_FACTOR_INVALID_CODE` | 401 | Wrong OTP submitted to `/api/auth/2fa/verify` | Re-enter code |
| `TWO_FACTOR_CHALLENGE_EXPIRED` | 410 | Challenge expired (default 15 min) | Restart login |
| `TWO_FACTOR_CHALLENGE_NOT_FOUND` | 404 | Bad or consumed `challenge_id` | Restart login |
| `TWO_FACTOR_LOCKED` | 429 | Too many wrong codes for the challenge | Restart login |
| `PASSWORD_EXPIRED` | 403 | Password policy expiry | Contact administrator |
| `RATE_LIMITED` | 429 | Login throttling | Retry later |
| `INTERNAL_ERROR` | 500 | Unexpected server failure | Generic error; optional retry |

## Machine-readable list

- **JSON:** `docs/api-error-codes.json`
- **Live (no auth):** `GET /api/meta/error-codes` → `{ success, data: { version, codes: [...] } }`

## Implementation

- Constants: `App\ApiErrorCode`
- Metadata: `App\ApiErrorRegistry`
- Helpers on `Core\Controller`: `apiError()`, `apiSuccess()`, `apiForbidden()`, `apiValidationError()`, etc.

## Related docs

- [API_CONTRACT.md](API_CONTRACT.md) — full API surface
- [MOBILE_APP_INTEGRATION.md](MOBILE_APP_INTEGRATION.md) — mobile onboarding
- [API_AUTH.md](API_AUTH.md) — login and token usage
