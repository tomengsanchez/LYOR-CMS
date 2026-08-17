# API error codes

Machine-readable `error.code` on `/api/*` failures. Registry also: `GET /api/meta/error-codes`.

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "title is required.",
    "details": { "field": "title" }
  }
}
```

| Code | HTTP | When |
|------|------|------|
| `UNAUTHORIZED` | 401 | Missing or invalid Bearer token |
| `FORBIDDEN` | 403 | Missing role capability |
| `BAD_REQUEST` | 400 | Invalid parameters or body |
| `VALIDATION_ERROR` | 400 | Business rule (slug, required fields) |
| `NOT_FOUND` | 404 | Page, post, or media missing |
| `CONFLICT` | 409 | Duplicate slug or conflicting state |
| `TWO_FACTOR_REQUIRED` | 403 | Account cannot start 2FA |
| `TWO_FACTOR_NO_EMAIL` | 403 | 2FA on, no email |
| `TWO_FACTOR_SEND_FAILED` | 502 | SMTP failed sending OTP |
| `TWO_FACTOR_INVALID_CODE` | 401 | Wrong OTP |
| `TWO_FACTOR_CHALLENGE_EXPIRED` | 410 | Challenge expired |
| `TWO_FACTOR_CHALLENGE_NOT_FOUND` | 404 | Bad or consumed `challenge_id` |
| `TWO_FACTOR_LOCKED` | 429 | Too many wrong codes |
| `RATE_LIMITED` | 429 | Login or API throttle |
| `SERVER_ERROR` | 500 | Unexpected failure |
