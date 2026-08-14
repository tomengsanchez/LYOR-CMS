# Mobile ↔ PHP exchanges (reference log)

Dated notes of agreements and change requests between the **mobile app team** (`paps-mobile`) and this **PHP backend** (`eco`). Use this file so future work does not re-litigate settled contracts.

**After each API change:** update this log + `MOBILE_APP_INTEGRATION.md` / `API_CONTRACT.md` / monthly `changes/` / Postman. Ask mobile to re-copy PHP docs into `paps-mobile/guide-docs/php-docs/`.

---

## How to add an entry

1. Newest first under the current month heading.
2. Record: date, source (mobile / PHP), ask or decision, breaking?, PHP status.
3. Link related CHANGES / DevelopmentHistory when implemented.

---

## 2026-08

### 2026-08-11 — API client gate (X-App-Id / X-App-Secret)

**Source:** PHP / product (limit API to named mobile/frontend apps).

#### Ask / decision

Optional gate on `/api/*`: named clients under **System → API Clients** (`app_settings`). Headers `X-App-Id` + `X-App-Secret`. Per-client disable. Web session exempt. Error `UNAUTHORIZED_CLIENT`. Default **off** until admins enable.

UI: **Dashboard** (`/system/api-clients`), **Clients**, **Security logs**, **Usage analytics** — filter by client / user `#id` / project `#id`, with Prev/Next pagination. Events in `api_client_events` (migration **095**). Listings exclude local/web-session/loopback traffic by default (`include_local=1` to show). Logs record **device model** + **OS version** (migration **096**; headers `X-Device-Model` / `X-Device-OS` or User-Agent). Mobile should pass `project_id` on scoped calls so project analytics populate.

#### Breaking?

Only when the gate is turned **on** (then mobile/Postman must send headers). Off by default.

#### PHP status

**Done** 2026-08-11 — see `changes/2026-08/CHANGES.md`.

**Docs to re-copy:** `MOBILE_APP_INTEGRATION.md`, `API_CONTRACT.md`, `API_AUTH.md`, `API_ERROR_CODES.md`, this file, Postman.

---

### 2026-08-05 — Profile + grievance attachment cards on API

**Source:** PHP / product follow-up (mobile asked what was still missing after status/structure uploads).

#### Ask / decision

Expose **registration attachment cards** (title/description/file) on REST the same way the web forms do — not only status-history files and structure images.

#### Breaking?

No (additive). New `attachments[]` on GET profile/grievance detail. Multipart card fields on create/update. Partial updates that omit card fields do **not** clear cards.

#### PHP status

**Done** 2026-08-05 — see `changes/2026-08/CHANGES.md`.

| Surface | Notes |
|---------|--------|
| `GET /api/profile/{id}` | `attachments[]` with serve `url` |
| `POST /api/profile/store` + `update` | multipart `attachment_*` (+ `attachment_section` on replace) |
| `GET /api/grievance/{id}` | `attachments[]` → `/serve/grievance-card-attachment?id=` |
| `POST /api/grievance/store` + `update` | same multipart card fields as web |

**Docs to re-copy:** `MOBILE_APP_INTEGRATION.md`, `API_CONTRACT.md`, this file, Postman.

---

### 2026-08-05 — Mobile integration brief + Structure list

**Source:** Mobile developer change request / working agreement.

#### Keep working as today (no change)

| Topic | Status |
|-------|--------|
| Response envelope `{ success, data, error }` | Keep |
| Bearer auth + optional email 2FA challenge | Keep |
| Capabilities on `GET /api/auth/me` | Keep |
| Grievance list/detail/store/update/status-update/status-log/options/history | Keep |
| `Idempotency-Key` on create (preferred on update retries) | Keep |
| `expected_updated_at` + `CONFLICT` on concurrent edits | Keep |
| Preserve `phase_id` when omitted on partial update; accept explicit `null` to clear | Keep (already fixed 2026-08-04) |

#### Process when APIs change

1. Update `eco/docs/` (especially `MOBILE_APP_INTEGRATION.md`, `API_CONTRACT.md`, monthly CHANGES, Postman).
2. Tell mobile to re-copy into `paps-mobile/guide-docs/php-docs/`.
3. Call out breaking changes: renamed fields, newly required fields, changed error codes.

#### Helpful (optional, not blockers)

- Keep `GET /api/meta/error-codes` current.
- Prefer project-scoped options via `?project_id=` consistency.
- Avoid clearing fields on partial JSON updates unless the field is present.

#### Out of mobile scope for now

- Library admin create/edit (projects/muni/brgy)
- Web-only responsive CSS / drawer
- Backup/restore CLI (mobile only syncs queue before maintenance)

#### Requested (implemented same day)

| Item | Detail | Breaking? | PHP status |
|------|--------|-----------|------------|
| `GET /api/structure/list` | `?page=&per_page=&q=&project_id=` (+ optional web filters) | No (additive) | **Done** 2026-08-05 |
| `GET /api/structure/options` | Tagging status + actual usage suggestions + classification pickers | No (additive) | **Done** 2026-08-10 |

**Why:** Mobile wants a Structures tab. Previously only `GET /api/structure/{id}` + tag/primary search existed, so structures were opened from Profiles.

**Implementation notes for mobile**

- Auth: Bearer; capability `view_structure`.
- Pagination shape matches `GET /api/profile/list` / `GET /api/grievance/list`.
- Prefer `project_id` for scoped lists (user project ACL still applies).
- Detail/images: use `GET /api/structure/{id}` after picking a row.
- Optional `primary_only=1` nests Secondaries under Primary (`data.nested`, `items[].secondaries`); default list is flat.

**Docs to re-copy:** `MOBILE_APP_INTEGRATION.md` §7.0, `API_CONTRACT.md` Structure list, Postman “List structures”, this file.

---

## Template (copy for next exchange)

```markdown
### YYYY-MM-DD — Short title

**Source:** …

#### Ask / decision
- …

#### Breaking?
- Yes/No — …

#### PHP status
- Pending / Done — link CHANGES entry
```
