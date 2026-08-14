# Mobile App — Email 2FA Login Guide

End-to-end onboarding for mobile developers integrating the PAPeR REST **email 2FA** flow shipped on **2026-05-19**.

> **TL;DR:** When the server has email 2FA enabled, `POST /api/auth/login` no longer returns a token on success. It returns a **pending challenge**. Submit the emailed 6-digit code to **`POST /api/auth/2fa/verify`** to receive the Bearer token. The token, once issued, behaves exactly like the no-2FA flow — store it and reuse on every `/api/*` request.

---

## Contents

1. [Why this flow exists](#1-why-this-flow-exists)
2. [Endpoint summary](#2-endpoint-summary)
3. [Full success sequence](#3-full-success-sequence)
4. [Endpoint contracts](#4-endpoint-contracts)
5. [Error matrix and UI handling](#5-error-matrix-and-ui-handling)
6. [Retry, resend, lockout rules](#6-retry-resend-lockout-rules)
7. [Storage and security checklist](#7-storage-and-security-checklist)
8. [Reference client implementations](#8-reference-client-implementations)
9. [Testing locally and in CI](#9-testing-locally-and-in-ci)
10. [Backwards compatibility for existing apps](#10-backwards-compatibility-for-existing-apps)
11. [Server config knobs](#11-server-config-knobs)
12. [FAQ](#12-faq)

---

## 1. Why this flow exists

The PAPeR backend supports an optional **email 2FA** policy (Security Settings → "Enable email 2FA"). Before 2026-05-19, the policy could not be satisfied from native apps — `POST /api/auth/login` returned `TWO_FACTOR_REQUIRED` and that was the end of the road.

The new endpoints expose a two-step flow that mirrors what the web app already does, but designed for stateless mobile clients (no cookies, no CSRF):

1. **Login (step 1)** — username/password is verified; if 2FA is enabled, the server emails a 6-digit code and returns a **`challenge_id`**.
2. **Verify (step 2)** — the app posts `{ challenge_id, code }` and receives the **Bearer token**.

The challenge is **scoped to the device** that started login (it carries IP and User-Agent for audit), is single-use, and expires after the configured TTL (default 15 minutes).

---

## 2. Endpoint summary

| Method | Path | Auth | Body | Purpose |
|--------|------|------|------|---------|
| `POST` | `/api/auth/login` | None | `{ username, password }` | Step 1. Returns either a token (2FA off) or `pending_2fa` + `challenge_id` (2FA on). |
| `POST` | `/api/auth/2fa/verify` | None | `{ challenge_id, code }` | Step 2. Returns token on correct code. |
| `POST` | `/api/auth/2fa/resend` | None | `{ challenge_id }` | Rotates the OTP on the same challenge (attempts reset, expiry extended). |
| `GET`  | `/api/auth/me` | Bearer | – | Sanity check that the issued token is good. |
| `POST` | `/api/auth/logout` | Bearer | – | Revoke token at sign-out. |

All responses use the standard envelope:

```json
{ "success": true|false, "data": ..., "error": null | { "code": "...", "message": "..." } }
```

> Always branch on **`error.code`**, never on `error.message` (messages may be translated/changed).

---

## 3. Full success sequence

```text
mobile app                                                       PAPeR API
    │                                                                  │
    │  POST /api/auth/login                                            │
    │  { "username": "u", "password": "p" }                            │
    │ ───────────────────────────────────────────────────────────────► │
    │                                                                  │
    │ 200 OK                                                           │
    │ { success: true,                                                 │
    │   data: { pending_2fa: true,                                     │
    │           challenge_id: "f2c4...e9",                             │
    │           expires_at: "2026-05-19 09:10:00",                     │
    │           email_hint: "ad***@example.com" } }                    │
    │ ◄─────────────────────────────────────────────────────────────── │
    │                                                                  │
    │ (user reads email, types 123456)                                 │
    │                                                                  │
    │  POST /api/auth/2fa/verify                                       │
    │  { "challenge_id": "f2c4...e9", "code": "123456" }               │
    │ ───────────────────────────────────────────────────────────────► │
    │                                                                  │
    │ 200 OK                                                           │
    │ { success: true,                                                 │
    │   data: { token: "<64-hex>",                                     │
    │           expires_at: "2026-06-18 ...",                          │
    │           user: { id, username, display_name, email } } }        │
    │ ◄─────────────────────────────────────────────────────────────── │
    │                                                                  │
    │  GET /api/auth/me   (Authorization: Bearer <token>)              │
    │  ... regular API calls from here ...                             │
```

When 2FA is **off**, step 1 already returns `data.token` and the verify step is skipped entirely. Your app should always check `data.pending_2fa` first.

---

## 4. Endpoint contracts

### 4.1 `POST /api/auth/login`

```http
POST {base}/api/auth/login
Content-Type: application/json

{ "username": "admin", "password": "secret" }
```

**Response — 2FA OFF (200)**

```json
{
  "success": true,
  "data": {
    "token": "<64-hex>",
    "expires_at": "2026-06-18 09:10:00",
    "user": { "id": 1, "username": "admin", "display_name": "Administrator", "email": "admin@example.com" }
  },
  "error": null
}
```

**Response — 2FA ON, credentials valid (200)**

```json
{
  "success": true,
  "data": {
    "pending_2fa": true,
    "challenge_id": "f2c4d11a...0e9",
    "expires_at": "2026-05-19 09:10:00",
    "email_hint": "ad***@example.com"
  },
  "error": null
}
```

- **`challenge_id`** is opaque hex (~48 chars). Store it in memory for the verify step. Do **not** persist it long-term — challenges are single-use and short-lived.
- **`expires_at`** is a UTC-ish server datetime string. Use it to drive a countdown timer in the OTP entry screen.
- **`email_hint`** masks the user's email (`ad***@example.com`) so the UI can show *"We sent a code to your registered email"*.

### 4.2 `POST /api/auth/2fa/verify`

```http
POST {base}/api/auth/2fa/verify
Content-Type: application/json

{ "challenge_id": "f2c4d11a...0e9", "code": "123456" }
```

**Success (200)** — identical shape to the no-2FA login:

```json
{
  "success": true,
  "data": {
    "token": "<64-hex>",
    "expires_at": "2026-06-18 09:10:00",
    "user": { "id": 1, "username": "admin", "display_name": "Administrator", "email": "admin@example.com" }
  },
  "error": null
}
```

After this point, **store the token and add `Authorization: Bearer <token>` to every protected call**. The verify endpoint is single-use; calling it again with the same challenge returns `TWO_FACTOR_CHALLENGE_NOT_FOUND`.

### 4.3 `POST /api/auth/2fa/resend`

```http
POST {base}/api/auth/2fa/resend
Content-Type: application/json

{ "challenge_id": "f2c4d11a...0e9" }
```

**Success (200)**

```json
{
  "success": true,
  "data": {
    "challenge_id": "f2c4d11a...0e9",
    "expires_at": "2026-05-19 09:25:00",
    "email_hint": "ad***@example.com",
    "resent": true
  },
  "error": null
}
```

- The **same `challenge_id`** is returned — keep using it.
- Server invalidates the old code and rotates to a new one (sent to the same email).
- `attempts` counter is reset; `expires_at` is extended to `now + 2fa_expiration_minutes`.
- Do **not** call resend on every failure — the next code is delivered by email, not in the JSON.

---

## 5. Error matrix and UI handling

All error responses follow the standard envelope. Always read **`error.code`**.

| `error.code` | HTTP | Likely cause | Suggested UI action |
|--------------|------|--------------|---------------------|
| `BAD_REQUEST` | 400 | Missing `username`/`password`, or missing `challenge_id`/`code` | Highlight blank fields. |
| `UNAUTHORIZED` | 401 | Wrong username/password | Show "Invalid credentials"; let the user retry login. |
| `TWO_FACTOR_INVALID_CODE` | 401 | Wrong 6-digit code | Keep the OTP screen open, increment a local "wrong code" counter, let the user retry. |
| `TWO_FACTOR_CHALLENGE_NOT_FOUND` | 404 | Bad or already-consumed `challenge_id` | Force restart of the login screen. |
| `TWO_FACTOR_CHALLENGE_EXPIRED` | 410 | The code expired before user entered it | Offer **Resend** or "Start over"; the old code is gone. |
| `TWO_FACTOR_NO_EMAIL` | 403 | 2FA is enabled but the account has no email on file | Show "Contact your administrator to add an email". |
| `TWO_FACTOR_SEND_FAILED` | 502 | Server failed to email the OTP (SMTP down) | Offer **Resend**; if it persists, prompt the user to contact admin. |
| `TWO_FACTOR_LOCKED` | 429 | Too many wrong codes on this challenge (5 by default) | Force restart of login. The challenge id is unusable. |
| `RATE_LIMITED` | 429 | IP-level brute-force throttle | Backoff (read message), retry later. |
| `PASSWORD_EXPIRED` | 403 | Server password policy expiry | Show "Contact administrator to reset". |
| `TWO_FACTOR_REQUIRED` | 403 | Legacy — server cannot start 2FA for this account | Same UX as `TWO_FACTOR_NO_EMAIL`. |
| `INTERNAL_ERROR` | 500 | Server fault | Generic error; optional retry. |

Full machine-readable list: **`GET /api/meta/error-codes`** (no auth) or `docs/api-error-codes.json`.

### State machine

```
        +---------+   wrong creds              +---------+
LOGIN──►| Credentials  ─────────────────────►  Show error
        +-----+---+
              │ valid creds, 2FA on
              ▼
        +---------+   wrong code, attempts < max
ENTER──►|  Verify ──────────────────────────►  Stay on screen, prompt again
   OTP  +---+---+--+
            │   │  │
            │   │  └► code valid ─► STORE TOKEN ─► Authenticated
            │   │
            │   └► attempts reached max ► TWO_FACTOR_LOCKED ─► restart login
            │
            └► time > expires_at        ► TWO_FACTOR_CHALLENGE_EXPIRED ─► offer Resend / restart
```

---

## 6. Retry, resend, lockout rules

- A challenge is **single-use**. Once `verify` succeeds, the same `challenge_id` is locked and any further verify/resend on it returns `TWO_FACTOR_CHALLENGE_NOT_FOUND`.
- Default **max_attempts = 5**. When exceeded, the server responds with `TWO_FACTOR_LOCKED` (HTTP 429). The challenge id is dead — restart login to get a new one.
- **Resend** rotates the OTP on the **same** `challenge_id`. It resets `attempts` to 0 and extends `expires_at`.
  - Use resend when the email did not arrive, or when the code expired but the user is still on the OTP screen.
  - Do **not** call resend on every wrong code — it does not help the user retry, it changes the valid code under their feet.
- IP-level throttle (`RATE_LIMITED`) is enforced across both login and verify. Wrong codes count toward the IP throttle, not just the challenge attempts.

---

## 7. Storage and security checklist

| Item | Recommendation |
|------|----------------|
| **`token`** | Store in secure storage: Keychain (iOS), EncryptedSharedPreferences/Keystore (Android), `flutter_secure_storage`, `react-native-keychain`. Never in plain UserDefaults / SharedPreferences. |
| **`expires_at`** | Use to schedule a silent re-login or to invalidate cached state. Token lifetime defaults to 30 days. |
| **`challenge_id`** | In-memory only (e.g. ViewModel/state object). It is single-use and expires in minutes — there is no value persisting it. |
| **`code`** | Never log or persist. Do not echo back in analytics events. |
| **`email_hint`** | Safe to display; already masked server-side. |
| **Transport** | Use **HTTPS** in any non-localhost environment. Pin certificates if your security policy requires it. |
| **Logout** | Call `POST /api/auth/logout` with the Bearer token on sign-out. Then delete the token from secure storage. |

---

## 8. Reference client implementations

### 8.1 TypeScript / fetch (React Native, Expo, web)

```ts
type Envelope<T> = { success: boolean; data: T | null; error: { code: string; message: string } | null };

async function apiPost<T>(base: string, path: string, body: unknown, token?: string): Promise<Envelope<T>> {
  const res = await fetch(`${base}${path}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify(body),
  });
  return res.json();
}

export async function login(base: string, username: string, password: string) {
  const r = await apiPost<
    | { token: string; expires_at: string; user: { id: number; username: string } }
    | { pending_2fa: true; challenge_id: string; expires_at: string; email_hint: string | null }
  >(base, "/api/auth/login", { username, password });

  if (!r.success || !r.data) throw r.error ?? new Error("login failed");
  if ("pending_2fa" in r.data && r.data.pending_2fa) {
    return { kind: "pending_2fa" as const, ...r.data };
  }
  return { kind: "token" as const, ...(r.data as { token: string; expires_at: string }) };
}

export async function verify2fa(base: string, challengeId: string, code: string) {
  const r = await apiPost<{ token: string; expires_at: string; user: { id: number } }>(
    base,
    "/api/auth/2fa/verify",
    { challenge_id: challengeId, code },
  );
  if (!r.success || !r.data) throw r.error ?? new Error("verify failed");
  return r.data;
}

export async function resend2fa(base: string, challengeId: string) {
  const r = await apiPost<{ challenge_id: string; expires_at: string; resent: boolean }>(
    base,
    "/api/auth/2fa/resend",
    { challenge_id: challengeId },
  );
  if (!r.success || !r.data) throw r.error ?? new Error("resend failed");
  return r.data;
}
```

### 8.2 Dart / Flutter (http package)

```dart
class PaperApi {
  PaperApi(this.base);
  final String base;

  Future<Map<String, dynamic>> _post(String path, Map<String, dynamic> body, {String? token}) async {
    final res = await http.post(
      Uri.parse('$base$path'),
      headers: {
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      },
      body: jsonEncode(body),
    );
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  Future<LoginStep> login(String username, String password) async {
    final env = await _post('/api/auth/login', {'username': username, 'password': password});
    if (env['success'] != true) throw ApiException.fromEnvelope(env);
    final data = env['data'] as Map<String, dynamic>;
    if (data['pending_2fa'] == true) {
      return LoginStep.pending2fa(
        challengeId: data['challenge_id'] as String,
        expiresAt: data['expires_at'] as String,
        emailHint: data['email_hint'] as String?,
      );
    }
    return LoginStep.token(
      token: data['token'] as String,
      expiresAt: data['expires_at'] as String,
    );
  }

  Future<AuthToken> verify2fa(String challengeId, String code) async {
    final env = await _post('/api/auth/2fa/verify', {'challenge_id': challengeId, 'code': code});
    if (env['success'] != true) throw ApiException.fromEnvelope(env);
    final data = env['data'] as Map<String, dynamic>;
    return AuthToken(data['token'] as String, data['expires_at'] as String);
  }

  Future<void> resend2fa(String challengeId) async {
    final env = await _post('/api/auth/2fa/resend', {'challenge_id': challengeId});
    if (env['success'] != true) throw ApiException.fromEnvelope(env);
  }
}
```

### 8.3 Kotlin / OkHttp

```kotlin
class PaperApi(private val base: String, private val http: OkHttpClient = OkHttpClient()) {
    private val json = "application/json".toMediaType()

    private fun post(path: String, body: String, token: String? = null): JSONObject {
        val req = Request.Builder()
            .url("$base$path")
            .post(body.toRequestBody(json))
            .apply { if (token != null) addHeader("Authorization", "Bearer $token") }
            .build()
        return http.newCall(req).execute().use { JSONObject(it.body!!.string()) }
    }

    fun login(username: String, password: String): LoginStep {
        val env = post("/api/auth/login", """{"username":"$username","password":"$password"}""")
        require(env.getBoolean("success")) { env.getJSONObject("error").toString() }
        val data = env.getJSONObject("data")
        return if (data.optBoolean("pending_2fa", false)) {
            LoginStep.Pending2fa(data.getString("challenge_id"), data.getString("expires_at"), data.optString("email_hint"))
        } else {
            LoginStep.Token(data.getString("token"), data.getString("expires_at"))
        }
    }

    fun verify2fa(challengeId: String, code: String): AuthToken {
        val env = post(
            "/api/auth/2fa/verify",
            """{"challenge_id":"$challengeId","code":"$code"}"""
        )
        require(env.getBoolean("success")) { env.getJSONObject("error").toString() }
        val data = env.getJSONObject("data")
        return AuthToken(data.getString("token"), data.getString("expires_at"))
    }
}
```

### 8.4 Swift / URLSession (iOS)

```swift
struct PaperApi {
    let base: URL

    func post<T: Decodable>(_ path: String, body: [String: Any], token: String? = nil) async throws -> T {
        var req = URLRequest(url: base.appendingPathComponent(path))
        req.httpMethod = "POST"
        req.addValue("application/json", forHTTPHeaderField: "Content-Type")
        if let t = token { req.addValue("Bearer \(t)", forHTTPHeaderField: "Authorization") }
        req.httpBody = try JSONSerialization.data(withJSONObject: body)
        let (data, _) = try await URLSession.shared.data(for: req)
        return try JSONDecoder().decode(T.self, from: data)
    }
}
```

---

## 9. Testing locally and in CI

### 9.1 Postman

Import the regenerated collection from `docs/postman/PAPeR-API.postman_collection.json` and environment `docs/postman/PAPeR-Local.postman_environment.json`. The **Auth (REST)** folder contains:

1. **Login** — captures `data.challenge_id` into the environment variable `twofa_challenge_id` (and `api_token` if 2FA is off).
2. **2FA verify** — uses `{{twofa_challenge_id}}` and stores `data.token` into `api_token` on success.
3. **2FA resend** — uses `{{twofa_challenge_id}}`.

### 9.2 Playwright (project-internal, optional)

The repository ships a headless suite that proves the round trip:

```bash
npm run test:e2e:api-2fa:headless
```

It uses `cli/e2e_api_2fa.php` to (a) toggle `enable_email_2fa`, (b) switch `Mailer` to the **log** provider so no SMTP is needed, (c) overwrite the OTP hash to a known value, then runs the API calls.

### 9.3 Manual smoke test (curl)

```bash
# Step 1 — start login
curl -s -X POST $BASE/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"admin123"}'

# Step 2 — verify with the code from email
curl -s -X POST $BASE/api/auth/2fa/verify \
  -H 'Content-Type: application/json' \
  -d '{"challenge_id":"<paste>","code":"123456"}'

# Step 3 — use the token
curl -s $BASE/api/auth/me \
  -H 'Authorization: Bearer <paste-token>'
```

---

## 10. Backwards compatibility for existing apps

| App behavior before 2026-05-19 | Behavior now |
|----------------------------------|--------------|
| Always expects `data.token` on `login` | Will see `data.pending_2fa` when 2FA is enabled. **Update your code to branch on it.** |
| Treats HTTP 403 + `TWO_FACTOR_REQUIRED` as "cannot log in" | Still possible only when 2FA is enabled but the account has no email or the server cannot start a challenge. New code: `TWO_FACTOR_NO_EMAIL` covers the no-email case more clearly. |
| Bearer-token usage on `/api/*` | **Unchanged.** Tokens issued from verify behave identically to tokens issued from password-only login. |
| `GET /api/auth/me` shape | **Unchanged.** |
| `POST /api/auth/logout` | **Unchanged.** |

Add this branching to your login handler:

```ts
if ("pending_2fa" in data && data.pending_2fa) {
  navigateToOtpScreen(data.challenge_id, data.expires_at, data.email_hint);
} else {
  storeToken(data.token);
  navigateToHome();
}
```

---

## 11. Server config knobs

These live in PAPeR Security Settings (`/security-settings`, admin only) and back-end `app_settings`:

| Setting | Default | Meaning |
|---------|---------|---------|
| `enable_email_2fa` | `0` (off) | When `1`, both the web and API logins enforce 2FA. |
| `2fa_expiration_minutes` | `15` | TTL for each issued challenge (capped 1–1440 minutes). |
| `login_throttle_enabled` | `1` | Adds IP-based throttling (`RATE_LIMITED`). |
| `login_throttle_max_attempts` | `5` | Max failures per IP within `login_throttle_lockout_minutes` before throttling. |
| (challenge `max_attempts`) | `5` | Per-challenge wrong-code limit before `TWO_FACTOR_LOCKED`. Not configurable from the UI yet; defaults baked into `App\ApiTwoFactorChallenge`. |

Admin must also ensure each user has a working email on file; otherwise login returns `TWO_FACTOR_NO_EMAIL`.

---

## 12. FAQ

**Q. Will my existing logged-in app session break when admin turns 2FA on?**
No. Already-issued Bearer tokens stay valid until they expire (default **7 days**, idle timeout **72 hours** unless renewed by use) or are revoked via `/api/auth/logout`. The 2FA gate applies only to *new* logins.

**Q. Can I auto-fill the OTP on iOS?**
Yes. The email body sends a plain "Your verification code is: 123456" line. iOS pulls the code into the keyboard suggestion bar if your text field uses `textContentType = .oneTimeCode`.

**Q. Should I cache the `challenge_id` if the user backgrounds the app?**
Only in memory. The challenge expires in minutes; if the user comes back later, restart the login screen. Forcing a fresh challenge is safer than trying to revive an expired one.

**Q. What happens if I keep posting wrong codes?**
After 5 wrong attempts the challenge enters `TWO_FACTOR_LOCKED` (HTTP 429). The challenge id is unusable; you must restart login.

**Q. Does this work in a subfolder install?**
Yes. Treat the base URL as `https://host[/subpath]` and prefix all paths the same way you already do for `/api/auth/me`.

**Q. Where do I see all error codes?**
`GET /api/meta/error-codes` (no auth) or `docs/api-error-codes.json`.

---

## Change log

- **2026-05-19:** Initial mobile-focused 2FA guide. Documents the new `/api/auth/2fa/verify` and `/api/auth/2fa/resend` endpoints, the updated `/api/auth/login` shape (`pending_2fa`, `challenge_id`), and the new error codes (`TWO_FACTOR_INVALID_CODE`, `TWO_FACTOR_CHALLENGE_EXPIRED`, `TWO_FACTOR_CHALLENGE_NOT_FOUND`, `TWO_FACTOR_LOCKED`, `TWO_FACTOR_NO_EMAIL`, `TWO_FACTOR_SEND_FAILED`). See `docs/CHANGES.md` and `docs/DevelopmentHistory/5.19.2026.json`.
