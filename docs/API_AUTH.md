# REST API authentication

Token-based auth for Simple CMS `/api/*`. Collection: [postman/Simple-CMS-API.postman_collection.json](postman/Simple-CMS-API.postman_collection.json).

Responses use `{ "success", "data", "error" }` ([API_CONTRACT.md](API_CONTRACT.md)).

Run `php cli/migrate.php` so `api_tokens` exists.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/login` | No | Body `{ username, password }`. Returns `data.token`, or 2FA challenge. |
| POST | `/api/auth/2fa/verify` | No | `{ challenge_id, code }` → `data.token` |
| POST | `/api/auth/2fa/resend` | No | `{ challenge_id }` |
| GET | `/api/auth/me` | Yes | User + capabilities |
| POST | `/api/auth/logout` | Yes | Revoke token |

Send `Authorization: Bearer <token>` on later requests.

**Token lifetime:** `api_token_expiry_days` (default 7) and idle window `api_token_idle_minutes` (default 72 hours).

Example `baseUrl`: `http://cms.local` (configurable; do not hardcode in Playwright).
