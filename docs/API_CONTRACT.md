# API Contract (Third-Party Frontend)

This document defines the response envelope and practical integration rules for `/api/*` endpoints.

**Mobile / third-party apps:** See **[MOBILE_APP_INTEGRATION.md](MOBILE_APP_INTEGRATION.md)** for auth flow, capability matrix, location picker sequence, and multipart examples. **Error codes:** **[API_ERROR_CODES.md](API_ERROR_CODES.md)** and **`GET /api/meta/error-codes`** (no auth). **Exchange log:** **[MOBILE_EXCHANGES.md](MOBILE_EXCHANGES.md)**.

## Response Envelope

All API endpoints return JSON in this shape:

```json
{
  "success": true,
  "data": {},
  "error": null
}
```

Error responses:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "API_ERROR",
    "message": "Human-readable message",
    "details": {}
  }
}
```

Notes:
- `details` is optional.
- `data` may be object, array, scalar, or null.
- **`error.code`** uses stable values from `App\ApiErrorCode` (e.g. `UNAUTHORIZED`, `UNAUTHORIZED_CLIENT`, `FORBIDDEN`, `FORBIDDEN_PROJECT`, `VALIDATION_ERROR`, `NOT_FOUND`, `CONFLICT`, `TWO_FACTOR_REQUIRED`, `PASSWORD_EXPIRED`, `RATE_LIMITED`, `INTERNAL_ERROR`). See **`docs/API_ERROR_CODES.md`** and **`GET /api/meta/error-codes`**.
- All `App/Controllers/Api/*` handlers return success via **`apiSuccess()`** and errors via **`apiError()`** / related helpers. Integrators should always read `token` from `data` on `POST /api/auth/login` success, not from the top level.
- **First-party web pages** that `fetch` `/api/*` from JavaScript must use the **`data`** property for domain payloads—for example **`GET /api/dashboard`** returns widgets under **`data.profile`**, **`data.structure`**, **`data.grievance`**, **`data.users`**, not at the top level. The main dashboard script is **`public/assets/js/dashboard/index.js`** (unwrap helper); **`public/assets/js/grievance/dashboard.js`** uses the same pattern for **`GET /api/grievance/dashboard`**.

## Authentication

- Session auth is supported (browser-based).
- Bearer auth is supported for API:
  - `Authorization: Bearer <token>`
  - Token is obtained from `POST /api/auth/login` (in the success envelope, `data.token`)
- **API client gate (optional, System → API Clients):** When enabled, non-session `/api/*` callers must also send **`X-App-Id`** and **`X-App-Secret`** for a named client stored in `app_settings` (`api_clients_json`). Per-client enable/disable/rotate under **Clients**. Web UI session is exempt. Public: `GET /api/meta/error-codes`. Failure: **`UNAUTHORIZED_CLIENT`** (401). Usage/security events are stored in `api_client_events` and reviewed under **Dashboard** / **Security logs** / **Usage analytics** (filters: client, user `#id`, project `#id`; paginated; local/web-session/loopback excluded by default via `include_local`). Each event stores **device_model** + **os_version** (optional headers `X-Device-Model` / `X-Device-OS`, else User-Agent). Prefer `project_id` on query/form for project attribution.
- Unauthenticated API responses return `401`.
- Forbidden/capability failures return `403`.
- Login throttling `429` is enforced by `Core/LoginThrottle`; when Realtime Security auto-block is enabled, block threshold uses `realtime_security_failed_login_threshold`.
- **Email 2FA (API):** When `enable_email_2fa` is on, successful credential check on `POST /api/auth/login` returns **`200`** with `data.pending_2fa`, `data.challenge_id`, `data.expires_at`, `data.email_hint` (no token yet). Complete with **`POST /api/auth/2fa/verify`** `{ challenge_id, code }` → `data.token`. Resend via **`POST /api/auth/2fa/resend`**. Full flow: **[API_AUTH.md](API_AUTH.md)** §3.1.
- Auth-specific failures from login / 2FA (not the happy path):
  - `PASSWORD_EXPIRED` (`403`) when password expiry policy blocks login
  - `TWO_FACTOR_NO_EMAIL` / `TWO_FACTOR_SEND_FAILED` when a challenge cannot be started or emailed
  - Legacy `TWO_FACTOR_REQUIRED` only when the account cannot start 2FA (not the normal “2FA is enabled” response)

## Status Codes (common)

- `200` success
- `201` created
- `400` bad request / validation
- `401` unauthenticated
- `403` forbidden
- `404` not found
- `409` conflict (optimistic lock — record changed since client loaded it; reload and retry)
- `429` login throttling
- `500` internal error

## Optimistic locking (optional)

When updating records that may be edited by multiple users, send the **`updated_at`** value from your last read:

| Context | Field name |
|---------|------------|
| Web forms (hidden input) | `record_updated_at` |
| JSON API body | `expected_updated_at` (alias: `record_updated_at`, `updated_at`) |

If another save changed the row first, the API responds with **`409`** and **`error.code`: `CONFLICT`**. Reload the record and retry. When the field is omitted, updates behave as before (last write wins).

Profile **`GET`** / update responses include **`updated_at`** when available. Structure **`GET /api/structure/{id}`** includes **`updated_at`**.

## API idempotency (optional)

For **`POST /api/grievance/store`**, **`POST /api/grievance/update/{id}`**, **`POST /api/profile/store`**, **`POST /api/profile/update/{id}`**, **`POST /api/structure/store`**, and **`POST /api/structure/update/{id}`**, clients may send:

- Header: **`Idempotency-Key`** or **`X-Idempotency-Key`** (max 128 chars)

The first successful response for that key (per authenticated user and endpoint scope) is stored for **24 hours** and replayed on duplicate requests with the same key. Omit the header for normal one-off requests.

## Domain Rules Relevant to Frontend

### Grievance escalation

- Escalation timer does **not** reset on note-only updates if status/level did not change.
- Timer resets only on real status/level transitions.
- **Per project** (`projects.escalation_count_start`, editable under Library → Edit Project): **`next_day`** (default) counts from the day after the level effective date; **`effective_date`** counts from the effective date itself (day 1). Weekend/holiday exclusions remain global in Grievance Settings.
- For project-specific progress levels:
  - If project has custom levels, those are used.
  - If not, defaults (`Level 1/2/3`) are used.

### Progress-level labels

- API is project-aware for progress-level names.
- Mixed-project dashboard responses may include display labels prefixed by project name for clarity.

### Contacts directory (REST, read-only)

- **List:** `GET /api/contact/list` — paginated contact rows for PAPS (**`entity_type=profile`**) and users (**`entity_type=user`**). Query: `q`, `entity_type`, `project_id` (PAPS project filter), `page`, `per_page`. Requires **`view_contacts`**. PAPS rows respect project scope; soft-deleted profiles are excluded. Each item includes **`person_label`**, **`number`**, **`owner_label`**, **`owner_edit_path`** (web path to edit the owner). No create/update/delete on this API — use Profile/User store/update with **`contacts_person`** / **`contacts_number`** or **`contact_numbers`**.

### PAP profiles (REST)

- **List:** `GET /api/profile/list` returns paginated rows with core list columns plus derived fields **`address`** (house/building/lot/street/village/barangay/city), **`representative_info`** (representative name + contact numbers), and **`related_structure_pn_numbers`** (distinct PN numbers from linked structures: owner, tagged-by, or matching structure tags in the same project; comma-separated when multiple). Former questionnaire fields (residing-in-structure, structure-owner flags, property/housing questions, HH income, etc.) are not returned.
- **Admin deleted filter:** `GET /api/profile/list` accepts `show_deleted=with|only` for administrators; non-admin clients should assume active-only results.
- **Read / write:** `GET /api/profile/{id}`, `POST /api/profile/store`, `POST /api/profile/update/{id}` use a payload aligned with the web profile: **`entity_type`** (`person`|`business`, editable; default `person`), identity for persons (**`first_name`**, **`middle_name`**, **`suffix`**, **`last_name`**, **`full_name`**, age/birthday) or for businesses (**`registered_business_name`** — also synced to **`full_name`**), plus read-only convenience **`display_name`**, representative name (**`representative_last_name`**, **`representative_first_name`**, **`representative_middle_name`**, **`representative_suffix`**, **`representative_contact_number_1`** … **`_3`**), contacts, location (**`custom_municipality_id`** preferred; **`custom_municipality`** name still accepted for legacy clients and resolved to an existing row in **`municipalities`**), **`custom_barangay_id`** (FK → **`barangays`**, preferred) with optional legacy **`custom_barangay`** name resolved against the selected **`project_id`** + municipality, dates (**`date_of_invitation`** and three field-visit pairs **`visit_1_date`**, **`visit_1_remarks`** … **`visit_3_date`**, **`visit_3_remarks`**), **invitation card** fields (**`invitation_rsvp`**, **`invitation_reason_not_accepting`**, **`invitation_reason_not_attending`**, **`invitation_specific_needs_specify`**, first/second visit personnel names and positions, **`invitation_first_visit_date_of_invitation`**, **`invitation_second_visit_date_of_invitation`**, **`invitation_distribution_status`**, **`invitation_distribution_status_other`**, **`invitation_first_visit_status`**, **`invitation_second_visit_status`**, **`invitation_received_by`**), project, ownership fields (**`structure_ownership_type`**, **`civil_status`**, **`spouse_name`**), added household/address fields (**`total_household`**, **`household_number`**, **`structure_number`**, **`house_number`**, **`building_number`**, **`lot_block_number`**, **`street_name`**, **`village_subd`**, **`barangay_text`**, **`city_municipality`**), **`field_personnel_id`**, and **`field_personnel_name`** (on read). On read, JSON includes **`custom_municipality_id`**, **`custom_municipality`** (display name), **`custom_barangay_id`**, and **`custom_barangay`** (display name from the join), plus **`invitation_rsvp_label`** and **`invitation_distribution_status_label`** when the stored value matches a known option. **`invitation_rsvp`** and **`invitation_distribution_status`** must be one of the allowed stored values (same strings as the web select options). Questionnaire fields for removed sections are **not** part of the contract. **Attachment cards** (`profile_attachments`): read returns **`attachments[]`** (`id`, `title`, `description`, `file_path`, `url` via `/serve/profile`, `sort_order`). Multipart **`store`/`update`** accept `attachment_title[]`, `attachment_description[]`, `attachment_file[]`, optional `attachment_id[]`; send **`attachment_section`** when replacing the full set (omitted cards deleted). Omitting card fields on update leaves attachments unchanged. Non-empty **`control_number`** must be unique among active profiles.
- **Duplicate check:** `GET /api/profile/check-control-number?control_number=&exclude_id=` — returns `{ available: bool, message: string|null }` for form warnings.
- **`project_id` is required** on create/update; municipality and barangay must be valid for that project (same as web).
- **Structure tags (optional):** send **`profile_structure_tags`** as a JSON array of strings; each tag must already exist on a structure in the profile's **project, municipality, and barangay**. Legacy single **`profile_structure_tag`** is still accepted on write. Read responses include **`profile_structure_tags`** and **`profile_structure_tag`** (first tag, or null).
- **Ownership-dependent validation:** `civil_status` applies when `structure_ownership_type` is `owner` or `co_owner`; `spouse_name` is persisted when ownership is owner-like and `civil_status` is `married` or `live_in_common_law`. Ownership options include `co_owner` (Co-Owner). Civil status options include `live_in_common_law` (Live-In/Common Law Partner).
- **Web edit (Main tab):** The profile **edit** screen may save the primary/Main fields via **`POST /api/profile/update/{id}`** (JSON or form-encoded body as implemented) and expect a JSON envelope including **`success`** and updated **`profile`** (or equivalent) on success—same validation rules as other update paths.
- **Legacy field visits:** The web invitation card no longer includes **`visit_1_*`** … **`visit_3_*`** inputs. **`POST /api/profile/update/{id}`** still accepts those keys when sent explicitly (set or clear). When **none** of the six `visit_*` keys appear in the request body, the server clears all legacy visit dates/remarks and nulls **`invitation_first_visit_status`** / **`invitation_second_visit_status`** (same as web **`ProfileController::gatherProfileData()`** on full form save).
- **Structures for a profile:** `GET /api/profile/{id}/structures` returns a JSON array of non-deleted structure rows linked to that profile by any of:
  - `owner_id = {id}`
  - `tagged_by_profile_id = {id}`
  - matching `structure_tag` + project (`profile_structure_tags` junction and `project_id`)
  Each item includes `structure_classification` and `classification_label` (Primary / Secondary / —).
  Project scoping still applies for non-admin users. Used by the in-page **Structure** tab on profile view/edit.
- **Socio Economic (SES):** `GET /api/profile/{id}/socio-economic?version_id=` (optional) returns version list, selected snapshot sections (CSV filename collapsibles, multi-row blocks), effect summary, and changed-field paths vs the previous version. Changed fields include **`previous_value`**. **`sections`** (and rows within each section) are ordered by **`EntryID`** ascending (aliases `EntryID` / `ENTRYID` / `Entry ID`); missing EntryID sorts last, then `section_key`. Each section/row may include **`entry_id`**. Requires **`view_socio_economic`**. Survey data is imported via web **System → Socio Economic** ZIP upload (not via Main profile update). `POST /api/profile/{id}/section/socio-economic` remains a no-op placeholder for legacy clients. **`POST /api/profile/{id}/section/validation`** is likewise a no-op placeholder (nothing persisted). Storage/diff order is unchanged (still keyed by section + original row index).
- **SES → RAP Mapping:** `GET /api/system/rap-mapping` (fields + maps + `value_modes` / `source_entities`), `GET /api/system/rap-mapping/columns?project_id=` (optional; returns `{ ses: {sections,columns_flat}, structure: [...], grievance: [...] }`), `GET /api/library/{id}/rap-summary` (project RAP preview from current SES / Structure / Grievance maps). Field ops include `first`/`list`/`sum`/`average`/`count`/`count_in_range`/`sum_in_range` (`mode_params.min`/`max`). Requires **`view_rap_mapping`** or **`manage_rap_mapping`** (library summary also needs **`view_projects`**, and accepts **`view_socio_economic`** as an alternate for summary). Read-only; does not write Main or source rows. Web: **System → SES → RAP Mapping** (field CRUD + maps), **Library → project → RAP summary**. Migration **090**.
- **Restore:** `POST /api/profile/restore/{id}` restores a soft-deleted profile (admin + delete capability required).
- Integrators should not depend on keys that existed before migration **037** (`database/migration_037_remove_profile_questionnaire_fields.php`).
- For upgraded environments, migration 037 drops only columns that currently exist; clients should still treat those legacy keys as permanently unavailable after rollout.

### Structure (REST)

Used by the standalone Structure module and by the **profile Structure tab** (multipart create/update for images).

- **List:** `GET /api/structure/list` — same pagination shape as profile/grievance list APIs (`items`, `total`, `page`, `per_page`, `total_pages`, `first_id`, `last_id`, plus `nested` bool). Supports `q`, `columns`, `sort`, `order`, `page`, `per_page`, `after_id`, `before_id`, and filters: `project_id`, `municipality_id`, `barangay_id`, `phase_id`, `structure_tag`, `paps_q`, `date_first_visit_from`, `date_first_visit_to`, `tagging_status`, `structure_classification` (`primary` \| `secondary`), `primary_only=1` (nests Secondaries under Primary rows; items may include `secondaries[]`). Admins may pass `show_deleted=with|only`. Project scoping applies via `user_projects`. Each item includes normalized `structure_classification` and `classification_label`. Web list **Select Columns** also offers `gps_latitude` / `gps_longitude` (clickable map dialog). Requires **`view_structure`**.
- **Options (lookups):** `GET /api/structure/options` — Bearer; requires `view_structure`, `add_structure`, or `edit_structure`. Returns `tagging_statuses` (`id`, `code`, `name`, `description`, `sort_order`, `requires_other_text`, `requires_refusal_reason`), `actual_usages` (`id`, `name`, `description`, `sort_order` — **suggestion store** for free-text `actual_usage`, not a closed enum), and `classifications` (`code`, `name`). Prefer `code` for `tagging_status`; send any string for `actual_usage` (new values are remembered for later suggestions). Empty tagging library falls back to the former built-in status list. Managed in web under Structure → Options Library (`manage_structure_options`; migration **092**).
- **Read one:** `GET /api/structure/{id}` — structure fields including `tagging_images` / `structure_images` as JSON array strings (web paths under `/uploads/structure/...`), plus tagging extension fields when migration **047** is applied: `location_of_structure`, `pn_number` (migration **093**, optional text), `date_first_visit`, `remarks_first_visit`, `date_second_visit`, `remarks_second_visit`, `date_third_visit`, `remarks_third_visit`, plus visit witness fields from migration **097** (`first_visit_witness_1_name` / `_position_org` / `_date`, `first_visit_witness_2_*`, and the same pattern for `second_visit_*` and `third_visit_*`). On write, each legacy `date_*_visit` is **synced from that visit’s witness 1 date** when the witness date key is present (kept for list filters / older clients). `structure_classification` (`primary` \| `secondary`; legacy DB value `associated` is normalized to `secondary` on read/write), `associated_primary_structure_id` (optional link to a **Primary** structure when classification is **Secondary**), `associated_primary_structure_tag` (read-only label from linked primary), `actual_usage`, `gps_latitude`, `gps_longitude` (parsed decimal degrees for maps), `gps_latitude_text`, `gps_longitude_text` (original coordinate strings as entered, migration **065**), `tagging_status`, `tagging_status_other`, `refusal_reason`. On create/update, send coordinates in `gps_latitude` / `gps_longitude`; the API stores the raw strings in `*_text` and parsed decimals in the decimal columns. Nullable **`project_id`**, **`municipality_id`**, **`barangay_id`** (FKs; migration **055**) mirror the Library location saved from **`/structure/create`** / **`/structure/edit/{id}`**. `tagging_status` values come from the Options Library (defaults include `tagged`, `owner_refused`, `owner_not_around`, `vacant`, `abandoned`, `under_construction`, `temporary`, `exceeded_number_of_visits`, `other`). The first-party **web** page `GET /structure/view/{id}` presents the same fields in tabs—**Structure tagging information** (STRID, tag #, extended tagging fields including PN number, description, **other_details**, image galleries), **Visitation Data** (two witnesses + dates per visit, plus remarks; Secondary forms may **Copy visitation from primary**), and **Paps information** (linked profiles by ownership role).
- **Create:** `POST /api/structure/store` — **`multipart/form-data`**: optional **`owner_id`** (profile id; used by embedded/profile flows; omit for null owner), optional **`project_id`**, **`municipality_id`**, **`barangay_id`** (validated like profile location against Library project areas / **`municipality_projects`** + master **`barangays`**), optional text fields (`structure_tag`, `description`, `other_details`, `pn_number`, visit witness fields, and the same tagging field names as on the web form), file fields **`tagging_images[]`** and **`structure_images[]`**. Success: **`{ "success": true, "data": { "id": <integer> } }`**. A structure **`created`** row may be written to **`audit_log`**. Requires **`add_structure`**.
- **Update:** `POST /api/structure/update/{id}` — multipart; may include **`strid`**, **`owner_id`**, text fields (including tagging extension fields), new image file arrays, and removal arrays **`tagging_images_remove[]`** / **`structure_images_remove[]`** (paths to drop from stored JSON). If any of **`project_id`**, **`municipality_id`**, **`barangay_id`** are present in the body, the stored location is replaced after validation; omitting **all three** keys leaves location unchanged. Requires **`edit_structure`**. The first-party **web** form `POST /structure/update/{id}` does **not** expose `owner_id`; the controller keeps the existing **`structures.owner_id`** (PAPS linkage is driven by profiles sharing the structure tag).
- **Delete / restore:** `POST /api/structure/delete/{id}` (soft delete), **`POST /api/structure/restore/{id}`** (admin + delete capability for restore).
- **Primary search (Secondary classification):** `GET /api/structure/primary-search?q=<text>&exclude_id=<optional>` — returns **`{ "success": true, "data": [ { "id": <int>, "text": "<tag · strid>" } ] }`** for structures classified **primary**, project-scoped like other structure APIs. Used by the structure form Select2 control when **Classification = Secondary**.
- **Next STRID:** `GET /api/structure/next-strid` — returns a generated id string for forms that pre-fill STRID.
- **Find by Structure Tag + project:** `GET /api/structure/find-by-tag?tag=<text>&project_id=<id>` — returns `{ structure: null|{ id, strid, owner_name, owners[], linked_groups{ owners[{id,name,papsid,control_number}], co_owners[{...}], renters[{...}], sharers_occupants[{...}], caretakers[{...}] }, structure_tag, description, other_details, pn_number, tagging_images[], structure_images[] } }`, scoped to non-deleted structures in the given project.
- **Tag search (profile multi-select):** `GET /api/structure/tag-search?q=<text>&project_id=<id>&municipality_id=<id>&barangay_id=<id>` — returns `{ "success": true, "data": [ { "id": "<tag>", "structure_tag": "<tag>", "strid": "...", "structure_classification": "primary|secondary|null", "classification_label": "Primary|Secondary|—", "text": "<tag · strid>" } ] }` for distinct existing tags scoped to the structure rows in that project **and** municipality/barangay (structure `municipality_id` / `barangay_id`). Used by profile create/edit Select2 (existing tags only; does not create structures).
- **Structure save audit:** Web and API structure update record `status_changed` in `audit_log` when `tagging_status` (or related other/refusal fields) change; structure view **Status History** reads these entries via `Structure::taggingStatusHistory()`.

### Grievances (REST)

- **List:** `GET /api/grievance/list` — same pagination shape as other list APIs (`items`, `total`, `page`, `per_page`, …). Supports `q`, `columns`, `sort`, `order`, `page`, `per_page`, `after_id`, `before_id`, and filters: `status`, `project_id`, `progress_level`, `respondent_id`, `needs_escalation`, `date_from`, `date_to`. Admins may pass `show_deleted=with|only`.
  - `needs_escalation=1` applies only to `in_progress` rows whose current stage exceeded configured `days_to_address`, comparing DevClock "today" against denormalized `grievances.current_level_started_at` and the grievance's project `escalation_count_start`. When **Grievance Settings** exclude weekends and/or holidays, elapsed days use business-day counting (see `App\GrievanceEscalation`).
- **Read:** `GET /api/grievance/{id}` — JSON grievance with decoded `*_ids` arrays (same field set as web form / `Grievance::create` payload, plus `project_name`, `profile_name`, `attendant_id`, `attendant_text`, `attendant_name` (user display name or free text), `current_level_started_at`, `is_deleted`, and **`attachments[]`** registration cards: `id`, `title`, `description`, `file_path`, `url` → `/serve/grievance-card-attachment?id=`, `sort_order`). Administrators can load soft-deleted rows (same as web view).
- **Create:** `POST /api/grievance/store` — JSON or multipart form body. **`date_recorded` is required** and must be a valid date/time. Required option fields match the web module when the **project’s** resolved lookup set is non-empty (GRM channel, preferred languages — project-scoped or defaults; grievance types/categories remain global). Selected GRM channel and preferred language IDs must belong to `GET /api/grievance/options?project_id=...`. Optional **`municipality_id`** and **`barangay_id`** (both together or neither; validated like profile/structure location against project configuration when set; PAPS uses profile location when present). Optional attendant: **`attendant_id`** (user linked via **`user_projects`**; when **`project_id`** is set, must be linked to that project) **or** free-text **`attendant_text`** (mutually exclusive; `attendant_id` wins if both sent). Optional web `attendant_mode` = `free` \| `user`. Optional `grm_channel_id` or `grm_channel_ids`; `respondent_*` and PAPS/profile fields as on web. Initial status fields are `status` (`open` default), `progress_level` (required and project-scoped for `in_progress`), `status_note`/`note`, `status_effective_at`, and multipart `status_attachments[]`. Non-default initial status/history fields require `edit_grievance` or `change_grievance_status` in addition to `add_grievance`. Create writes one matching status-history row and initializes escalation/closure fields from its effective date; when omitted, effective date defaults to `date_recorded`. It must not be earlier than Date Recorded or in the future. Multipart registration **attachment cards** use the same fields as web (`attachment_title[]`, `attachment_description[]`, `attachment_file[]`, optional `attachment_id[]`; send `attachment_section` when replacing the full set). Status-history files remain `status_attachments[]`. Non-empty **`grievance_case_number`** must be unique among active grievances; duplicates return validation error.
- **Duplicate check:** `GET /api/grievance/check-case-number?grievance_case_number=&exclude_id=` — returns `{ available: bool, message: string|null }` for form warnings.
- **Update:** `POST /api/grievance/update/{id}` — partial updates merge with the existing row, but the resulting grievance must have a valid, non-empty **`date_recorded`**. Date Recorded cannot be later than any existing status log effective date. If `status` / `progress_level` change, or **`status_note`** / status attachments are sent, a `grievance_status_log` row is created and status notifications may fire (same as web behavior). **`progress_level`** must belong to the grievance project scope (`forProjectOrDefault`) when `status` is `in_progress`. Optional `status_effective_at` (datetime) sets when the transition actually occurred for escalation (`current_level_started_at`); defaults to now. Must be ≥ `date_recorded` and not in the future. On the web UI, every status update (including note-only on the same stage) accepts **Effective date** for history; only segment changes affect the SLA clock. When registration fields change, Activity History records the same field-level **from → to** diffs as the web edit form, plus `source: api`. Optional **`phase_id`** is preserved on partial update when omitted (same merge pattern as other fields). Multipart **attachment cards** (`attachment_title[]` / `attachment_description[]` / `attachment_file[]` / `attachment_id[]`) follow the same rules as web: omit all card fields to leave cards unchanged; send `attachment_section` (or any card field) with the desired set to sync (missing cards soft-deleted).
- **Status update (recommended for mobile):** `POST /api/grievance/status-update/{id}` — JSON or multipart. Body: `status`, `progress_level` (when `in_progress`), `status_note` (or `note`), optional `status_effective_at`, optional multipart `status_attachments[]`. Always appends status history (including note-only). Requires `edit_grievance` or `change_grievance_status`. Response includes updated `grievance` and new `status_log` entry.
- **Status history read:** `GET /api/grievance/status-log/{id}` — `{ grievance_id, items[] }` with `status_label`, `note`, `effective_at` (business datetime, no TZ conversion), `created_at` (system timestamp in org timezone), `attachments[]`, etc.
- **Status log effective date (admin):** `POST /api/grievance/status-log-effective-at/{grievanceId}/{logId}` — body `{ "effective_at": "..." }`. Recomputes `current_level_started_at` from status log history.
- **Delete / restore:** `POST /api/grievance/delete/{id}` (soft delete), `POST /api/grievance/restore/{id}` (admin only, requires delete capability).

### Respondent autocomplete (grievance form)

Used by the web grievance form for datalists, prior grievance history, and “latest details” autofill. Requires grievance view/add/edit capability (same as implemented in `ApiController`). Name matching includes linked-profile fallbacks (`profiles` via `profile_id`) so records linked to a profile are discoverable in first/middle/last lookups and history/latest checks.

- **`GET /api/respondents/first-names`** — query param `q` (prefix). Returns `[]` if trimmed `q` is shorter than **3** characters (`mb_strlen`).
- **`GET /api/respondents/middle-names`** — requires `first_name`; returns `[]` if trimmed `first_name` is shorter than **3** characters.
- **`GET /api/respondents/last-names`** — requires `first_name`; same **3**-character minimum on `first_name`.
- **`GET /api/respondents/history`** — requires `first_name` (and other name params as implemented); returns `[]` if `first_name` is under **3** characters.
- **`GET /api/respondents/latest-details`** — requires `first_name` and `last_name`; returns `{ "matched": false }` if `first_name` is under **3** characters. When matched, includes `municipality_id`, `municipality_name`, `barangay_id`, `barangay_name` (from the latest grievance row) in addition to contact/respondent fields.

Integrators and custom clients should respect the same minimum to avoid heavy scans on large tables. The bundled **`public/assets/js/grievance/form.js`** matches this behavior.

### Grievance respondent profiles (server-rendered web)

HTML pages (session auth), not JSON:

- **`GET /grievance/respondents`** — `q` (optional; matches respondent names, mobile, email, and linked **PAPS** profile names), `page` (default 1), `per_page` (10–100, default 15), `is_paps` (optional `yes` \| `no` \| `mixed`), `linked` (optional `1` = has `profile_id`, `0` = no linked profile), `gender` (optional exact match; common values match the grievance form: Male, Female, Others, Prefer not to say), `sort` (optional; default `latest_desc` — also `latest_asc`, `name_asc`, `name_desc`, `grievances_desc`, `grievances_asc`).
- **`GET /grievance/respondents/pdf`** — same query parameters as the list for **PDF** output (server caps row count).
- **`GET /grievance/respondent`** — **302** redirect to **`/grievance/respondents`**, preserving the query string.

Postman: **`docs/postman/PAPeR-API.postman_collection.json`** covers **all `/api/*` routes** and a **web route catalog** (regenerate with **`node docs/postman/generate-collection.cjs`**). See **`docs/postman/README.md`**.

### Realtime Security (system telemetry)

- **`GET /api/system/realtime-security`** (admin-only) returns:
  - `data.config`: persisted `realtime_security_*` controls (`enabled`, `block_suspicious_ips`, `alert_on_lockout`, `failed_login_threshold`, `window_minutes`)
  - `data.metrics`: last-24h auth telemetry derived from `logs/auth.log` (`risk_level`, `login_attempts`, `failed_logins`, `successful_logins`, `blocked_attempts`, and `latest_events[]`)
  - `data.last_malware_scan`: last stored malware-scan report (or `null`) from the Realtime Security module
  - First-party page `System > Realtime Security` uses this endpoint via AJAX polling (10s default) to refresh metric cards and latest auth events without full-page reload.
- **`POST /api/system/realtime-security/malware-check`** (admin-only) triggers the malware scanner and returns:
  - `data.report`: `{ started_at, finished_at, files_scanned, findings_count, severity, findings[] }`
  - Scanner checks `App/`, `Core/`, `public/`, `cli/` PHP files against suspicious code patterns (`eval(`, `base64_decode(`, `gzinflate(`, `shell_exec(`, `system(`, `passthru(`, `assert(`)
- Uses the standard API envelope (`success`, `data`, `error`).

### Realtime Dashboard (presence + ops KPIs)

- **`POST /api/presence/heartbeat`** (any signed-in user, session cookie): body `page_key`, `path` (optional `page_label`). Updates **own** `user_sessions` presence columns only. Best-effort; CSRF is not validated on this quiet poll (regenerating CSRF would break open forms). Layout JS: `public/assets/js/layout/presence-heartbeat.js`.
- **`GET /api/system/realtime-dashboard`** (admin-only): snapshot with `summary`, `active_users[]` (web), `api_active_users[]` (Bearer tokens), `users_by_module[]`, `recent_audit[]`, `auth_events[]`. Online window defaults to 10 minutes. Web uses `COALESCE(presence_updated_at, last_activity_at)`; API uses non-expired `api_tokens.last_used_at`. Summary includes `api_active_users` / `api_active_tokens`. API rows may include `client_id`, `device` / `device_model` / `os_version`, `path`, `ip` from the latest `api_client_events` request when logging is available.
- **`POST /api/system/realtime-dashboard/sessions/{id}/revoke`** (admin-only + CSRF): force-ends that browser session; returns refreshed `csrf_token` and `snapshot`. Audit: `user_session` / `revoked_by_admin`.
- **`POST /api/system/realtime-dashboard/tokens/{id}/revoke`** (admin-only + CSRF): deletes that Bearer token; returns refreshed `csrf_token` and `snapshot`. Audit: `api_token` / `revoked_by_admin`.
- First-party page: `System > Realtime Dashboard` (`/system/realtime-dashboard`). Separate from Realtime Security.

### Live Traffic (HTTP request monitor)

- **Capabilities:** `view_live_traffic` (read), `manage_live_traffic` (block/unblock + settings). Administrators bypass.
- **`GET /api/system/live-traffic`** (`view_live_traffic`): query `filter` (`all` \| `humans` \| `bots` \| `warnings` \| `blocked` \| `logins`), `ip`, `q`, `since_id`, `limit`. Returns `events[]`, `config`, `can_manage`, `hits_24h`, `csrf_token`.
- **`GET /api/system/live-traffic/by-ip?ip=`** — recent events for one IP (+ `is_blocked`).
- **`GET /api/system/live-traffic/whois?ip=`** — RDAP Whois summary (`whois.ok`, `whois.summary`, optional `whois.raw`).
- **`GET /api/system/live-traffic/blocks`** — active blocklist rows.
- **`POST /api/system/live-traffic/block`** (`manage_live_traffic` + CSRF): JSON `{ ip, reason?, csrf_token }`. `ip` may be an exact address, IPv4 wildcard (`124.123.4.*`), or IPv4 CIDR (`124.123.4.0/24`).
- **`POST /api/system/live-traffic/unblock`** (`manage_live_traffic` + CSRF): JSON `{ ip, csrf_token }` (exact stored pattern to remove).
- **`GET /api/system/live-traffic/blocks`**: active blocklist (visible with `view_live_traffic`); includes `pattern_label`.
- First-party page: `System > Live Traffic` (`/system/live-traffic`). Logging hook in `Core\Router` + site-wide block check in `bootstrap.php`. Live Traffic API polls, `/api/presence/heartbeat`, and `/api/help/chat*` are excluded from `traffic_events`.
- Blocklist UI: `System > Blocked IPs` (`/system/blocked-ips`) for full manage; Live Traffic shows a limited preview + link.
- **CSV export:** `GET /system/live-traffic/export` (`view_live_traffic`) — downloads all `traffic_events` as UTF-8 CSV (chunked stream).
- Retention: `live_traffic_retention_days` (default 30); CLI `php cli/prune_live_traffic.php`.

### Ask Help (in-app help chat)

- **`GET /api/help/chat/status`** (any signed-in user): `{ enabled, mode, model? }` — `mode` is `llm` when an API key is configured, otherwise `local`.
- **`POST /api/help/chat`** (session cookie + CSRF): JSON `{ message, page_key, csrf_token }`.
  - Answers are grounded in in-app Help content for `page_key` (same keys as `/help?from=`), in plain user language.
  - With `help_chat_api_key` set (System → General → Ask Help), calls an OpenAI-compatible `/chat/completions` endpoint; on failure falls back to local help-text matching.
  - Without a key, uses local matching only.
  - Rate-limited per user (`help_chat_max_per_hour`, default 30). Success returns `{ reply, mode, help_url, page_title, csrf_token }`.
- Widget: floating **Ask Help** on authenticated layout (`public/assets/js/layout/help-chat.js`). Toggle/settings: admin **System → General**.

### Settings email config (`GET /api/settings/email`)

- Returns `data.config` with notification toggle and sender transport settings.
- `email_provider`: `"smtp"` (default) or `"mailersend"`.
- SMTP keys: `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_encryption`.
- MailerSend keys: `mailersend_api_token`, `mailersend_from_email`, `mailersend_from_name`.
- Shared sender fallbacks: `from_email`, `from_name` (used by MailerSend if provider-specific sender fields are blank).

## Operational Helpers

### When to Remap (project-scoped options)

**GRM Channels**, **Preferred Languages**, and **In Progress Stages** share the same model:

| Situation | Project uses | Remap needed? |
|-----------|--------------|---------------|
| Project not Initialized (no project-specific rows) | Global defaults (`project_id` NULL) | **No** |
| After **Initialize this project** (copies with new IDs) and the project already has grievances | Project-specific IDs on forms; old rows may still store default IDs | **Yes** (UI Remap or CLI below) |
| Fresh install / no grievances yet | Defaults after migrate + `seed_grievance_options.php` | **No** |

Fresh install includes the feature via `php cli/migrate.php` (migration **085** for GRM/language `project_id`; progress levels via **026**). Seed defaults with `php database/seeders/seed_grievance_options.php`. Per-project lists remain optional.

**Web guide:** `GET /system/remap-audit` (capability `view_remap_audit`) shows per-project On defaults / Needs Remap / OK for GRM, languages, and stages. `POST /system/remap-audit/remap` (capability `run_remap_audit`, CSRF) runs the same remappers as Options Library. Migration **086** seeds both capabilities for Administrator.

### CLI remappers

- Re-map existing records to project-specific level IDs:
  - `php cli/remap_progress_levels.php`
  - `php cli/remap_progress_levels.php --project=<id>`
  - `php cli/remap_grm_language_options.php`
  - `php cli/remap_grm_language_options.php --project=<id>`
  - `php cli/remap_grm_language_options.php --only=grm|language`
  - `php cli/assess_grm_language_scope.php` (read-only impact check)

## Seeder paths (current tree)

- **Grievance lookups:** `php database/seeders/seed_grievance_options.php` (safe to re-run).
- **Bulk demo profiles:** `php database/seeders/seed_profiles.php` (default 500; `--count`, `--profiles`, `-n/-c`, or trailing number). Requires at least one active project.
- **Demo projects / Field Team / optional demo profiles:** `php database/seeders/seed_projects_users_field_team.php` with optional `--profiles=N` (see script header).
- **Bulk demo grievances (performance testing):** `php database/seeders/seed_grievances.php` (default 1000 rows; optional count flags in script header).

Progress-level sample data is configured through the **Grievance > In Progress Stages** UI and **`php cli/remap_progress_levels.php`**; older `seed_profiles_structures*.php` scripts are not shipped in this repository.

### E2E execution notes

- Prefer scripts that exist in root **`package.json`** and whose `tests/e2e/**/*.spec.ts` files are present. See **[AUTOMATED_FUNCTIONAL_TEST_DESIGN.md](AUTOMATED_FUNCTIONAL_TEST_DESIGN.md)** (Quick Start + known gaps).
- Common runners: `npm run test:e2e:full`, `npm run test:e2e:api`, `npm run test:e2e:all`, `npm run test:e2e:seed` (sets `E2E_DB_SEED=1`).
- Login helper: `tests/e2e/support/auth.ts` waits for navigation off `/login` and for authenticated layout shell before proceeding.
- `BASE_URL` is configurable (`.env.playwright` / ADR-0010); default often `http://eco.local`.

## HTTP route index (`/api/*`)

Authoritative registration: `public/index.php`. Methods below are as registered; each action enforces auth/capabilities in code (`requireAuthApi`, etc.).

### Project areas (municipality + barangay)

- **`GET /api/projects`** — Query **`q`** (name prefix). JSON array of up to 20 **`{ id, name }`** rows for non-deleted projects the user may access (Administrator: all). Session or Bearer auth; requires **`view_projects`**, **`add_projects`**, **`edit_projects`**, profile/grievance/users capabilities as implemented, or **`view_structure` / `add_structure` / `edit_structure`** (structure form Select2).
- **`GET /api/municipalities`** — Optional query **`q`**. JSON array (envelope **`data`**) of up to 20 rows **`{ id, name, code }`** from the master **`municipalities`** table (Library barangay form Select2; empty **`q`** returns first 20 by name). Session or Bearer auth; requires project-related capabilities (`view_projects` / `add_projects` / `edit_projects`).
- **`GET /api/projects/{id}/municipalities`** — JSON `{ municipalities: [{ id, name }], has_list: bool }` (distinct municipalities from normalized Library areas; **`municipalities`** table). Also allowed for **`view_structure` / `add_structure` / `edit_structure`** (same as profile flows).
- **`GET /api/projects/{id}/barangays`** — With query **`municipality_id=<int>`** (preferred) or **`municipality=<name>`** (legacy) or numeric id as string, JSON `{ municipality_id?, municipality?, barangays: [{ id, name }], has_list: bool }` for that municipality only. **Without** a municipality filter, returns a flat **`barangays`** list in the same **`{ id, name }`** shape (legacy TEXT-only projects may use **`id: 0`** with **`name`** only). Also allowed for **`view_structure` / `add_structure` / `edit_structure`**.
- **`GET /api/projects/{id}/phases`** — JSON `{ phases: [{ id, name, description }], has_list: bool }` for active project-scoped phases (ensures default **Unassigned** exists). Used by Structure, Profile, and Grievance forms.
- Profiles persist **`custom_municipality_id`** (FK → **`municipalities`**) and **`custom_barangay_id`** (FK → **`barangays`**); **`GET /api/profile/{id}`**, **`POST /api/profile/store`**, **`POST /api/profile/update/{id}`** accept **`custom_municipality_id`** and/or legacy **`custom_municipality`** name, and **`custom_barangay_id`** and/or legacy **`custom_barangay`** name. The (**`municipality_id`**, **`barangay_id`**) pair must either match a row in **`project_affected_barangays`** for the selected **`project_id`**, or—when that project has **no** such rows—the municipality must appear in **`municipality_projects`** for the project and the barangay must belong to that municipality (current Library → Municipality / Brgy workflow). Profile and Structure also accept **`phase_id`** (FK → **`project_phases`**; required when project is set). Grievance **`phase_id`** is optional.
- CSV profile import: optional **`custom_barangay_id`**; else **`custom_barangay`** name with **`custom_municipality_id`** / **`custom_municipality`**; optional columns **`custom_municipality_id`** and/or **`custom_municipality`**; if municipality omitted while **`custom_barangay`** and **`project_id`** are set, **`Unassigned`** is assumed for legacy rows where applicable.
- **`GET /api/users/linked-projects`** — Query **`q`** (username/display name prefix), optional **`project_id`**. JSON array (envelope **`data`**) of up to 20 users who have at least one **`user_projects`** row on a non-deleted project; when **`project_id`** is set, only users linked to that project. Used by grievance GRM **Attendant** Select2. Requires grievance/profile/users view capabilities as implemented in **`ApiController`**.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/api/meta/error-codes` | No auth |
| POST | `/api/auth/login` | Token or `pending_2fa` challenge — see Authentication |
| POST | `/api/auth/2fa/verify` | Body: `challenge_id`, `code` → `data.token` |
| POST | `/api/auth/2fa/resend` | Body: `challenge_id` |
| GET | `/api/auth/me` | |
| POST | `/api/auth/logout` | |
| GET | `/api/projects` | |
| GET | `/api/municipalities` | |
| GET | `/api/projects/{id}/municipalities` | |
| GET | `/api/projects/{id}/barangays` | |
| GET | `/api/projects/{id}/phases` | |
| GET | `/api/projects/{id}/users` | |
| GET | `/api/users/field-personnel` | |
| GET | `/api/users/linked-projects` | |
| GET | `/api/users/{id}/projects` | |
| GET | `/api/users/{id}/access/activity` | Access dashboard module activity (self or `view_users`); query `module`, `page`, `per_page` |
| GET | `/api/profiles` | |
| GET | `/api/respondents/first-names` | |
| GET | `/api/respondents/middle-names` | |
| GET | `/api/respondents/last-names` | |
| GET | `/api/respondents/history` | |
| GET | `/api/respondents/latest-details` | |
| GET | `/api/profile/{id}/structures` | |
| GET | `/api/notifications` | |
| GET | `/api/history` | Paginated activity log for `entity_type` = `profile`, `structure`, `grievance`, or `project`; query `entity_id`, `page`, `per_page`. Powers Activity History sidebars. `created_at` in org timezone (also `timezone`). |
| GET | `/api/dashboard` | |
| GET | `/api/contact/list` | Read-only directory; `view_contacts` |
| GET | `/api/grievance/check-case-number` | Duplicate check query |
| GET | `/api/grievance/dashboard` | |
| GET | `/api/grievance/calendar` | Month grid: `summary.due_on_deadline` / `due_overdue`; per-day `due.on_deadline` / `due.overdue`. |
| GET | `/api/grievance/calendar/day` | Single day: `items`, `due_items`. |
| GET | `/api/grievance/options` | Lookups + `escalation_settings`; project-scoped pickers with `?project_id=`. |
| GET | `/api/grievance/status-log/{id}` | Status history; `view_grievance`. |
| POST | `/api/grievance/status-update/{id}` | Status or note-only; `edit_grievance` or `change_grievance_status`. |
| POST | `/api/grievance/status-log-effective-at/{grievanceId}/{logId}` | Adjust status-log effective time |
| GET | `/api/grievance/list` | |
| GET | `/api/grievance/{id}` | |
| POST | `/api/grievance/store` | |
| POST | `/api/grievance/update/{id}` | |
| POST | `/api/grievance/delete/{id}` | |
| POST | `/api/grievance/restore/{id}` | |
| GET | `/api/settings/ui` | |
| GET | `/api/settings/email` | |
| GET | `/api/settings/security` | |
| GET | `/api/system/general` | Admin. `{ settings, branding, site_seo, public_theme, regions, timezones }` |
| GET | `/api/system/development` | |
| GET | `/api/system/operational` | |
| GET | `/api/system/realtime-security` | |
| POST | `/api/system/realtime-security/malware-check` | |
| GET | `/api/system/realtime-dashboard` | |
| POST | `/api/system/realtime-dashboard/sessions/{id}/revoke` | |
| POST | `/api/system/realtime-dashboard/tokens/{id}/revoke` | |
| GET | `/api/system/live-traffic` | |
| GET | `/api/system/live-traffic/by-ip` | |
| GET | `/api/system/live-traffic/whois` | |
| GET | `/api/system/live-traffic/blocks` | |
| POST | `/api/system/live-traffic/block` | |
| POST | `/api/system/live-traffic/unblock` | |
| GET | `/api/system/rap-mapping` | SES→RAP fields + maps |
| GET | `/api/system/rap-mapping/columns` | Discover source columns |
| GET | `/api/library/{id}/rap-summary` | Project RAP preview |
| POST | `/api/presence/heartbeat` | |
| GET | `/api/help/chat/status` | |
| POST | `/api/help/chat` | |
| GET | `/api/system/log` | Debug log |
| GET | `/api/profile/check-control-number` | Duplicate check |
| GET | `/api/profile/list` | |
| GET | `/api/profile/{id}` | |
| GET | `/api/profile/{id}/socio-economic` | SES tab payload; `view_socio_economic` |
| POST | `/api/profile/store` | |
| POST | `/api/profile/update/{id}` | |
| POST | `/api/profile/{id}/section/socio-economic` | No-op placeholder |
| POST | `/api/profile/{id}/section/validation` | No-op placeholder |
| POST | `/api/profile/delete/{id}` | |
| POST | `/api/profile/restore/{id}` | |
| GET | `/api/structure/list` | |
| GET | `/api/structure/options` | Tagging status / actual usage / classification lookups |
| GET | `/api/structure/next-strid` | |
| GET | `/api/structure/find-by-tag` | |
| GET | `/api/structure/primary-search` | |
| GET | `/api/structure/tag-search` | |
| GET | `/api/structure/{id}` | |
| POST | `/api/structure/store` | |
| POST | `/api/structure/update/{id}` | |
| POST | `/api/structure/delete/{id}` | |
| POST | `/api/structure/restore/{id}` | |

### `GET /api/grievance/dashboard` (escalation-focused payload notes)

- Returns standard API envelope: `{ success, data, error }`.
- Implementation: **`App/GrievanceDashboardStats`** + **`App/GrievanceDashboardFilter`** (shared project/date filters for all aggregations; status total + breakdown from one grouped query).
- Under `data`, escalation-relevant keys include:
  - `inProgressLevels`: grouped counts by project + progress stage.
  - `closedByStage`: grouped counts of closed grievances by last stage before closure (uses **closure date** for `date_from` / `date_to`, not date recorded).
  - `closedInRangeTotal`: total closed grievances in the closure-date filter.
  - `needsEscalationByLevel`: map of `progress_level_id -> count`.
  - `needsEscalationRows`: row set with `project_id`, `progress_level`, `count`, and computed `action` (`escalate` for non-last stage, `close` for last stage in that project scope).
- Grievance dashboard UI widget **`needs_escalation`** renders `needsEscalationRows` (escalate vs close badges); enable via dashboard Customize or default widget list in `App/DashboardConfig.php`.
- Query support includes `project_id`, `date_from`, `date_to` for scoping dashboard metrics and escalation rows.
- Category/type breakdowns use **`GrievanceJsonSql::memberOf()`** on `grievance_category_ids` / `grievance_type_ids` (`MEMBER OF` on MySQL 8.0.17+ with migration **068** indexes; **`JSON_CONTAINS`** fallback on MariaDB / older MySQL).
- Automated coverage intent: multi-project escalate vs close on `needsEscalationRows[].action`. Former specs `grievance-escalation-multi-project.spec.ts` / `grievance-full-self-contained.spec.ts` are not in tree (see AUTOMATED_FUNCTIONAL_TEST_DESIGN known gaps).
- **Profile `created` / `updated` counts** on this dashboard come from `audit_log`. Bulk profile seeders (`database/seeders/seed_profiles.php`, demo profiles in `database/seeders/seed_projects_users_field_team.php`) insert matching `audit_log` rows via `AuditLog::recordWithCreatedBy` so seeded data appears in the widget the same way as UI-created profiles.

### `GET /api/dashboard` (main dashboard payload notes)

- Returns standard API envelope: `{ success, data, error }`.
- Optional query: `date_from`, `date_to` (YYYY-MM-DD) filter activity counts; omit for all-time totals.
- Under `data`, main dashboard keys are:
  - `date_from`, `date_to` — echoed active filter (nullable)
  - `profile`: `{ created, updated, added_structures }` — entity counts from `profiles` / `structures` (not lifetime audit-event totals)
  - `structure`: `{ created, updated, added_images }` — entity counts; `added_images` from `audit_log` JOIN (indexed)
  - `grievance`: `{ created, updated, status_changed, unread_new, escalations, by_status[] }` — `unread_new` is unread notifications; `status_changed` from indexed audit JOIN
  - `users`: array of `{ role, count }`
- First-party page `/` reads these values through `public/assets/js/dashboard/index.js` (date filter UI included).

