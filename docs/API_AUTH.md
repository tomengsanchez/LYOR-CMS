# REST API Authentication (Postman)

Token-based authentication for the PAPeR REST API. Use Postman to test.

---

## 1. Run migration

Before using the API, create the `api_tokens` table:

```bash
php cli/migrate.php
```

---

## 2. Endpoints

All `/api/*` responses go through `Core\Controller::json()`, which wraps payloads in the standard envelope (see `docs/API_CONTRACT.md`): `{ "success": true|false, "data": ..., "error": ... }`. Examples below show that shape.

**API client gate (optional):** When enabled under **System → API Clients → Clients**, requests to `/api/*` that are **not** using a first-party browser session must send:

```http
X-App-Id: paper-mobile
X-App-Secret: <secret shown once when the client is created/regenerated>
```

Create/disable/rotate clients under **System → API Clients → Clients**. Admins can review traffic on **Dashboard**, **Security logs**, and **Usage analytics** (filter by client, user `#id`, project `#id`). Failure returns **`UNAUTHORIZED_CLIENT`** (401). The web UI (session cookie) is exempt. `GET /api/meta/error-codes` stays public.

Base path (adjust host and optional subfolder / `base_url` from `config/app.php`):
- Example login URL when the app is at the web root: `http://localhost/api/auth/login`
- If the app lives in a subpath, prefix accordingly (e.g. `http://localhost/paper/public/api/auth/login`).

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST   | `/api/auth/login`        | No  | Login. With 2FA off: returns `data.token`. With 2FA on: returns `data.pending_2fa` + `data.challenge_id`. |
| POST   | `/api/auth/2fa/verify`   | No  | Step 2 of 2FA login. Body: `{ challenge_id, code }`. Returns `data.token`. |
| POST   | `/api/auth/2fa/resend`   | No  | Re-send the OTP for a still-active challenge. Body: `{ challenge_id }`. |
| GET    | `/api/auth/me`           | Yes | Current user info + capabilities |
| POST   | `/api/auth/logout`       | Yes | Revoke token (optional) |

**Token lifetime:** Absolute expiry defaults to **7 days** (`api_token_expiry_days`). Tokens unused longer than the idle window (default **72 hours**, `api_token_idle_minutes`) are revoked on next use. Call `POST /api/auth/logout` to revoke immediately.

---

## 3. Login

**Request**

- **Method:** `POST`
- **URL:** `http://localhost/api/auth/login`
- **Headers:** `Content-Type: application/json`
- **Body (raw JSON):**

```json
{
  "username": "your_username",
  "password": "your_password"
}
```

**Success (200)**

```json
{
  "success": true,
  "data": {
    "token": "64-char-hex-string",
    "expires_at": "2026-04-02 12:00:00",
    "user": {
      "id": 1,
      "username": "admin",
      "display_name": "Administrator",
      "email": "admin@example.com"
    }
  },
  "error": null
}
```

### 3.1 Email 2FA flow (when `enable_email_2fa` is on)

> Mobile developers: see **[MOBILE_2FA_GUIDE.md](MOBILE_2FA_GUIDE.md)** for a step-by-step guide with state machine, error matrix, and ready-to-use TypeScript / Dart / Kotlin / Swift snippets.

`POST /api/auth/login` with valid credentials returns **`200`** with a **pending challenge** instead of a token:

```json
{
  "success": true,
  "data": {
    "pending_2fa": true,
    "challenge_id": "f2c4...e9",
    "expires_at": "2026-05-19 09:10:00",
    "email_hint": "ad***@example.com"
  },
  "error": null
}
```

The OTP is **emailed** to the account's email. Submit it to **`POST /api/auth/2fa/verify`**:

```json
{
  "challenge_id": "f2c4...e9",
  "code": "123456"
}
```

Success returns the same shape as a no-2FA login (token, expires_at, user).

If the email did not arrive (or the code expired), call **`POST /api/auth/2fa/resend`** with `{ "challenge_id": "..." }` — the same challenge id is reused; attempts are reset and a new code is sent.

**Errors**

Responses use the same envelope with `success: false` and `error: { "code", "message", "details"? }` (see API_CONTRACT). Typical status codes:

- `400` – Missing username/password, or missing `challenge_id`/`code`
- `401` – Invalid credentials (`UNAUTHORIZED`) or wrong OTP (`TWO_FACTOR_INVALID_CODE`)
- `403` – `PASSWORD_EXPIRED`, `TWO_FACTOR_NO_EMAIL`, or legacy `TWO_FACTOR_REQUIRED` (account cannot start 2FA)
- `404` – `TWO_FACTOR_CHALLENGE_NOT_FOUND` (bad / already-consumed `challenge_id`)
- `410` – `TWO_FACTOR_CHALLENGE_EXPIRED`
- `429` – `RATE_LIMITED` (IP throttling) or `TWO_FACTOR_LOCKED` (too many wrong codes for the challenge)
- `502` – `TWO_FACTOR_SEND_FAILED` (SMTP failure when emailing the OTP)

Full list: `docs/API_ERROR_CODES.md` and `GET /api/meta/error-codes`.

---

## 4. Get current user (me)

**Request**

- **Method:** `GET`
- **URL:** `http://localhost/api/auth/me`
- **Headers:** `Authorization: Bearer <token>`

Replace `<token>` with the value from the login response.

**Success (200)**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "admin",
    "display_name": "Administrator",
    "email": "admin@example.com",
    "role_name": "Administrator"
  },
  "error": null
}
```

**Error (401)**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "API_ERROR",
    "message": "Authentication required"
  }
}
```

---

## 5. Logout (revoke token)

**Request**

- **Method:** `POST`
- **URL:** `http://localhost/api/auth/logout`
- **Headers:** `Authorization: Bearer <token>`

**Success (200)**

```json
{
  "success": true,
  "data": {
    "message": "Logged out"
  },
  "error": null
}
```

---

## 6. Postman setup

### 6.0 Import (recommended)

Tracked exports live under **`docs/postman/`**:

- **`docs/postman/PAPeR-API.postman_collection.json`** — Full **REST `/api/*`** surface from `public/index.php` (auth, dropdowns, respondents, notifications, history, dashboards, grievance CRUD, settings/system, profile, structure) plus a **Web (session)** catalog (dashboard, profile, structure, grievance + options, library, settings, system, users/roles, serve URLs). Regenerate after route changes: `node docs/postman/generate-collection.cjs` (source: **`docs/postman/generate-collection.cjs`**).
- **`docs/postman/PAPeR-Local.postman_environment.json`** — `baseUrl`, `api_token`, `session_cookie`, `csrf_token` (optional for web POST experiments).

See **`docs/postman/README.md`** for import steps and how to use session cookies for web routes.

### 6.1 Login request

1. Create a new request.
2. Method: **POST**
3. URL: `http://localhost/api/auth/login` (or your base URL)
4. **Headers:**
   - `Content-Type` = `application/json`
5. **Body:** raw, JSON:
   ```json
   {
     "username": "admin",
     "password": "your_password"
   }
   ```
6. Send and copy the `token` from the response (`data.token` in the JSON envelope).

### 6.2 Using the token for other requests

**Option A – Manual**

For each protected request (e.g. `/api/auth/me`):

1. Add header: `Authorization` = `Bearer <paste-token-here>`

**Option B – Environment variable**

1. Create an environment (or use Postman variables).
2. Add variable: `api_token` = (paste token from login).
3. For protected requests, add header: `Authorization` = `Bearer {{api_token}}`
4. After login, use **Tests** tab to store the token:
   ```javascript
   if (pm.response.code === 200) {
     var json = pm.response.json();
     var token = json.data && json.data.token ? json.data.token : json.token;
     if (token) {
       pm.environment.set("api_token", token);
     }
   }
   ```

### 6.3 Example: me request

1. Method: **GET**
2. URL: `http://localhost/api/auth/me`
3. Headers: `Authorization` = `Bearer {{api_token}}`
4. Send.

---

## 7. Using the token for other API endpoints

The same Bearer token works for existing API endpoints, for example:

- `GET /api/dashboard` – Dashboard data
- `GET /api/projects` – Projects list
- `GET /api/profiles` – Profiles list
- `GET /api/profile/list`, `GET /api/profile/{id}`, profile store/update — core PAP fields only (no legacy questionnaire keys); see **PAP profiles (REST)** in `docs/API_CONTRACT.md`
- `POST /api/profile/restore/{id}` (admin, delete capability)
- `POST /api/structure/restore/{id}` (admin, delete capability)
- etc.

Add the header: `Authorization: Bearer <your-token>`

---

## 8. Notes

- Tokens expire after 30 days. Re-login to get a new token.
- **Email 2FA is supported via API** (see §3.1): login returns a challenge, verify with the emailed code. Each challenge defaults to 5 attempts; lockouts return `TWO_FACTOR_LOCKED` — restart login to issue a new challenge.
- Tokens are hashed; the raw token is only returned at login. Store it securely on the client.
