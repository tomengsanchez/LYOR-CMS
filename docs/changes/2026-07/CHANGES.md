# PAPeR – Changes (2026-07)

Part of the [changes index](../CHANGES.md). Newest entries first within this month.

---

## Grievance attendant Free text / User + optional location (2026-07-31)

- Attendant on create/edit supports **Free text** or **User** (Select2). Migration **091** adds `grievances.attendant_text`. Municipality and Barangay are no longer required (both together or neither). CSV import, API, help, and docs updated.
- Playwright: `tests/e2e/grievance/grievance-attendant-optional-location.spec.ts` (`npm run test:e2e:grievance-attendant-optional-location:fast`, `BASE_URL` configurable). Submit-guard updated for optional location.

---

## Profile Socio tab — order by EntryID (2026-07-31)

- `GET /api/profile/{id}/socio-economic` (profile Socio Economic accordion) sorts **sections** and **rows** by CSV **EntryID** ascending (aliases `EntryID` / `ENTRYID` / `Entry ID`); missing EntryID last, then `section_key`. Response includes `entry_id` on sections/rows. Change highlighting still uses storage row indices. RAP mapping / import storage order unchanged. Help + API + Postman + DEVELOPMENTGUIDE updated.

---

## RAP Mapping — Playwright E2E (2026-07-31)

- `tests/e2e/rap-mapping.spec.ts` covers operation dropdown (incl. `average`), range Min/Max toggle, create fields, Structure/Grievance Select2 maps, project RAP summary, APIs, deactivate, help. Helpers in `tests/e2e/support/rap-mapping.ts`. Run: `npm run test:e2e:rap-mapping:fast` (`BASE_URL` configurable).

---

## RAP Mapping — average operation (2026-07-31)

- New RAP field op **`average`**: per-PAP numeric mean of mapped values (non-numeric ignored). Project summary shows mean of per-PAP averages. No migration (validated in PHP).

---

## RAP Mapping — Select2 for Structure/Grievance field picker (2026-07-31)

- Entity field picker (`#entity_field_picker`) uses Select2 (bootstrap-5 theme, search, clear), matching the SES column picker. Options filter by selected source; disabled options are hidden in the dropdown.

---

## RAP Mapping — drag-reorder fields (2026-07-31)

- RAP fields table supports **drag-and-drop reorder** (SortableJS); order persists via `POST /system/rap-mapping/fields/reorder` (`sort_order` 10, 20, …). Manual sort inputs removed from add/edit forms.

---

## RAP Mapping — custom fields, range ops, multi-entity maps (2026-07-31)

- **RAP field CRUD** on System → SES → RAP Mapping: add / edit / activate / deactivate fields (label, unique `field_key`, category, operation, sort). Soft-deactivate keeps maps but excludes the field from summaries.
- **Operations:** `first`, `list`, `sum`, `average`, plus `count`, `count_in_range`, `sum_in_range` (Min/Max in `mode_params_json`). Example: count incomes between 10 000 and 200 000.
- **Multi-entity maps:** source `ses` | `structure` | `grievance` — SES discovered CSV headers, or Structure / Grievance catalog fields. Per-PAP Structure via owner/tagged profile; Grievance via linked `profile_id`.
- **Migration 090**; `GET /api/system/rap-mapping/columns` returns `{ ses, structure, grievance }`. Caps text updated for `manage_rap_mapping`. Help / API / Postman / DEVELOPMENTGUIDE updated.

---

## Restore — shared sanitize for MariaDB sandbox banner (2026-07-31)

- **Bug:** Live MariaDB 10.11+ `mysqldump` ZIPs start with a sandbox banner that older XAMPP `mysql` clients (e.g. 10.4) reject as `Unknown command '\-'`. Restore used that client when found, so the same ZIP could fail on one machine and succeed on another via the PDO fallback.
- **Fix:** `cli/backup_sql_helper.php` strips the sandbox banner (and still rewrites legacy generated-column INSERTs) before **either** import path. `cli/restore.php` logs sanitize actions and `import_path=mysql|pdo` (with client version when available).
- **Test:** `npm run test:backup-sql-generated-columns` (sandbox + generated-column cases).
- **Docs / UI:** README §10, DEVELOPMENTGUIDE, Backup/Restore page note.

---

## SES → RAP Mapping (2026-07-30)

- **System → Data tools → SES → RAP Mapping:** Map SES CSV section/column headers to seeded RAP fields (`first` / `list` / `sum` modes). Discovers columns from current `profile_socio_sections` (optional project filter). Read-only vs Main/SES storage.
- **Library project RAP summary:** `/library/view/{id}/rap` — counts, field distributions, per-PAP mapped values (first 100 with SES). Link from project view **RAP summary**.
- **APIs:** `GET /api/system/rap-mapping`, `GET /api/system/rap-mapping/columns?project_id=`, `GET /api/library/{id}/rap-summary`. Caps: `view_rap_mapping`, `manage_rap_mapping` (Administrator seeded). Migration **089**.
- Tables `rap_field_definitions` + `ses_rap_column_maps` included in SQL backup; truncate clears maps only (definitions kept). Help + Postman + breadcrumbs updated.

---

## Ask Help — in-app help chat (2026-07-30)

- Floating **Ask Help** widget on authenticated pages (teal **?**); knows current `$helpPage` / screen label.
- Answers grounded in in-app Help content (user-level language). Optional OpenAI-compatible API key under **System → General → Ask Help**; without a key, local help-text matching.
- APIs: `GET /api/help/chat/status`, `POST /api/help/chat` (CSRF + per-user hourly rate limit). Migration **088** seeds `help_chat_*` settings.
- CSRF uses `Csrf::check()` (no token rotate) so Ask Help does not invalidate open forms; widget reads/updates `meta[name=csrf-token]` and retries once on stale token.
- Excluded from Live Traffic noise logging. Help / DEVELOPMENTGUIDE / API_CONTRACT / Postman updated.

---

## Breadcrumbs for nested navigation (2026-07-30)

- Layout shows a **breadcrumb trail** above page content (`Home → … → current page`), mirroring nested **System** groups and **Grievance → Options Library**.
- Central map: `App\NavTrail` (keyed by `$currentPage` / `$helpPage`); optional `$breadcrumbs` or `$breadcrumbLeaf` overrides from views.
- Help overall guidance updated; E2E nested System menu asserts breadcrumb text on Backup/Restore and Live Traffic.
- **All-pages E2E:** `tests/e2e/breadcrumbs.spec.ts` visits every main route and asserts the trail (`npm run test:e2e:breadcrumbs:fast`, `BASE_URL` configurable). Help page sets `$currentPage = 'help'`.
- Intermediate **System** / **Grievance Options Library** crumbs are clickable (e.g. System & Configuration → `/system/general`, Data tools → Socio Economic, Traffic & realtime → Live Traffic, Logs & audit → Audit Trail).

---

## System / Grievance nested navigation (2026-07-30)

- **System** sidebar/top menus regrouped into nested groups: **Configuration**, **Data tools**, **Traffic & realtime**, **Logs & audit**, plus **Development**.
- **Grievance → Options Library** is a real nested submenu (sidebar) / header group (top nav).
- Nested leaf links use a larger, higher-contrast font (`~0.95em`, light slate) so third-level items stay readable.
- URLs and capabilities unchanged (including Backup/Restore under Data tools).
- Help paths updated; E2E: `npm run test:e2e:nested-system-menu:fast` (`BASE_URL` configurable).

---

## Live Traffic — System monitor (2026-07-30)

- **System → Live Traffic** (`/system/live-traffic`): Wordfence-style live HTTP log (humans, bots/crawlers, login attempts, 404 warnings, blocked IPs) stored in MySQL (`traffic_events`).
- **Capabilities:** `view_live_traffic`, `manage_live_traffic` (migration **087**, seeded for Administrator).
- **Site-wide IP blocklist** (`traffic_ip_blocks`) enforced in `bootstrap.php` before Auth; complements login throttle. Supports exact IPs, wildcards (`124.123.4.*`), and CIDR (`124.123.4.0/24`). Full management on **System → Blocked IPs** (`/system/blocked-ips`); Live Traffic shows a top preview (latest 5) with **View all blocked IPs**.
- **Geo-IP + reverse DNS** cached in `traffic_geo_cache` (ip-api.com); **Whois** via RDAP (`TrafficWhois`).
- **APIs:** `GET/POST /api/system/live-traffic*` (list, by-ip, whois, block, unblock, blocks). Polling UI excludes its own API paths and presence heartbeats from the log.
- **CSV export:** `GET /system/live-traffic/export` — all stored traffic events (requires `view_live_traffic`).
- **Retention:** default 30 days (`live_traffic_retention_days`); probabilistic prune + `php cli/prune_live_traffic.php`. Tables included in SQL backup/restore; cleared by `truncate_fresh_install.php`.
- **Help / Admin Guide / API_CONTRACT / Postman / DEVELOPMENTGUIDE** updated.

---

## Remap Audit — System admin guide (2026-07-30)

- **System → Remap Audit** (`/system/remap-audit`): per-project status for GRM Channels, Preferred Languages, and In Progress Stages — **On defaults** (Remap not needed), **Needs Remap** (stale default IDs), or **OK**.
- **Capabilities:** `view_remap_audit`, `run_remap_audit` (migration **086**, seeded for Administrator). Run Remap on the page when status is Needs Remap.
- **Service:** `App\RemapAudit` reuses existing remappers; Options Library alerts link here for guidance.
- **Help / Admin Guide / API_CONTRACT** updated.

---

## Changes log split into monthly folders (2026-07-30)

- Monolithic `docs/CHANGES.md` (~120 KB) replaced with a **lightweight index** plus month files under `docs/changes/YYYY-MM/CHANGES.md` (April–July 2026 initially).
- **Update rule:** append new feature notes at the top of the **current month** file only; create a new `YYYY-MM` folder when the month changes.
- Links from README / Mobile guide still point at `docs/CHANGES.md` (index).

---

## GRM Channel & Preferred Language — per project (2026-07-30)

- **Schema:** Migration `085` adds nullable `project_id` to `grievance_grm_channels` and `grievance_preferred_languages` (NULL = global defaults), same pattern as In Progress Stages.
- **Options Library:** Project filter, Initialize from defaults, Re-map existing records; form Scope = Default or project.
- **Grievance form:** GRM channel and preferred languages load after Project is selected via `GET /api/grievance/options?project_id=`; required when the project’s resolved option set is non-empty; IDs must belong to that set.
- **API:** `grm_channels_scope` / `preferred_languages_scope` (`project` \| `default`); option rows include `project_id`. Store/update validate against project scope.
- **CSV import:** Resolves channel/language names against `forProjectOrDefault` for the row’s project.
- **CLI:** `php cli/remap_grm_language_options.php` [--project=] [--only=grm|language]; assess via `php cli/assess_grm_language_scope.php`.
- **When is Remap needed?** If the project has **no** project-specific GRM/languages/stages yet (still on defaults), **do not remap**. Remap only after **Initialize** when existing grievances still store default IDs. Same rule for In Progress Stages. Fresh install: migrate + `seed_grievance_options.php` includes the feature; Remap is unnecessary until you Initialize a project that already has data.
- **Help / API contract / Mobile / Postman** updated (help FAQ + dedicated Options help pages).

---

## Socio Economic Survey (SES) ZIP import (2026-07-29)

- **System → Socio Economic:** Preview/import a flat `.zip` of SES CSVs (`.7z` blocked; **max 10 MB** per ZIP). Match rows by `CONTROL ID` / `Control Number` / `CONTROL_ID` → `profiles.control_number` only; never create profiles or update Main fields. Project-scoped matching. Unmatched / empty control rows logged on the batch audit.
- **Preview accuracy:** Counts compare against existing section content (create / real update / unchanged). Clickable summary counts open detail dialogs (profiles, sections, skipped empty CONTROL ID rows with file+row, unmatched).
- **Profile Socio tab:** Collapsibles by CSV filename; multi-row as nested Row N; version dropdown by zip upload time; change highlights vs previous version; **click changed value** to see previous vs current; **Show empty fields** toggle (browser preference, shared with System SES page).
- **Storage:** `socio_import_batches` (+ per-project stats), `profile_socio_sections` (current), `profile_socio_versions` + `profile_socio_version_sections` (full snapshot per zip upload). Original ZIP under `public/uploads/socio-economic/` (backup/restore + truncate covered).
- **Capabilities:** `import_socio_economic`, `view_socio_economic_audit`, `view_socio_economic` (seeded for Administrator). Migration `084`.
- **API:** `GET /api/profile/{id}/socio-economic?version_id=` (changed fields include `previous_value`). Help + Postman updated.
- **Fix:** CSV BOM handling no longer `rewind()`s ZipArchive streams (non-seekable); avoids PHP warnings that broke JSON preview responses.
- **E2E:** `tests/e2e/socio-economic-ses.spec.ts` — `npm run test:e2e:socio-economic:fast` (`BASE_URL` configurable).

---

## Admin Realtime Dashboard (2026-07-27)

- **Presence heartbeats:** Authenticated layout posts `page_key` + path to `POST /api/presence/heartbeat` (~45s + tab visible). Columns on `user_sessions` (migration `083`): `current_path`, `page_key`, `page_label`, `presence_updated_at`.
- **Admin page:** `System → Realtime Dashboard` (`/system/realtime-dashboard`) — KPIs (active users/sessions, failed logins 24h, email queue, last backup age, open grievances), live active-users table, by-module counts, recent audit + auth snippets. Polls `GET /api/system/realtime-dashboard` every ~10s.
- **Force-end session:** `POST /api/system/realtime-dashboard/sessions/{id}/revoke` (admin + CSRF); audit action `revoked_by_admin`. Separate from Realtime Security (threats/scans) and from Account → Active sessions (self-serve).
- **Help / docs / Postman:** page help `realtime-dashboard`; API contract + Postman collection updated.

---

## App timezone — Activity History vs header clock (2026-07-27)

- **Problem:** Header used browser local time; Activity History used MySQL host time (`CURRENT_TIMESTAMP`) when PHP/MySQL session TZ were unset — e.g. UTC+8 vs MST.
- **Fix:** Org timezone from **System → General** (stored in `app_settings`) drives PHP `date_default_timezone_set` and `SET time_zone = '+HH:MM'` on every PDO connect (MySQL + MariaDB portable). `AuditLog` writes explicit `created_at` via `UserTime::nowSql()`.
- **Display:** `UserTime::formatSystem` / `formatBusiness` — system timestamps (audit, status log `created_at`) vs business datetimes (`effective_at`, `date_recorded`) with **no UTC conversion** on business fields.
- **UI:** Activity History / Audit Trail / `GET /api/history` format system times; header clock labeled **Local**. Grievance effectivity semantics unchanged.
- **Tests:** `tests/cli/app_timezone_usertime_test.php`; Playwright `npm run test:e2e:activity-history-timezone:fast` (`BASE_URL` configurable).

---

## Profile — Person vs Business entity type (2026-07-27)

- Profiles can be **Person** or **Business / Institution**; type is chosen before identity fields and remains editable.
- **Person:** last/first/middle/suffix + age/birthday. **Business:** registered business name (synced into `full_name` for search/joins).
- Representative, contacts, ownership, civil status, spouse, household, and invitation stay available for both (e.g. business as renter).
- List **Name** column, search, Select2 `/api/profiles`, PDF/CSV, structure linked PAPS, help, import, and API `display_name` / `entity_type` / `registered_business_name` are consistent.
- Migration `082`; Playwright: `npm run test:e2e:profile-entity-type:fast`.

---

## Profile list — show name suffix (2026-07-27)

- `/profile` Full Name column (and PDF/CSV via `PdfTable`) now includes the profile **suffix** (Jr., Sr., III, etc.).
- `Profile::listPaginated` selects `p.suffix` so the list can format last, first, middle, suffix.

---

## Library — Project view related counts (2026-07-23)

- **View Project** (`/library/view/{id}`) shows plain totals for Profiles (by ownership: Owner, Co-Owner, Renter, Sharer or Occupants, Caretaker; “Not set” when blank/unknown), Structures (Primary / Secondary), and Grievances (Open / In progress / Closed) plus a Phase breakdown table (including Primary / Secondary structure columns).
- Counts are display-only (no deep links). Same summary on project PDF.
- Aggregates: `Project::relatedCounts()`, `Project::phaseBreakdown()` (active rows only; optional “No phase” row when null `phase_id` exists). Structure Primary/Secondary follows Structure list rules (null/blank → Primary; legacy `associated` → Secondary).

---

## Profile Structure tab — Phase column (2026-07-23)

- Profile view/edit **Structure** tab lists each linked structure's **Phase** (from the structure record).
- `Structure::byOwner()` and `GET /api/profile/{id}/structures` include `phase_id` / `phase_name` so AJAX table refresh stays in sync.


---

## Project phases — Structure, Profile, Grievance (2026-07-23)

- **Library:** `project_phases` per project (migration 081); CRUD on project edit; default **Unassigned** phase seeded per project (and on new project create).
- **Structure:** Phase after Barangay; required when project is selected; empty phase list blocks submit (JS + server); list filter, export column, PDF, API.
- **Profile:** Phase after Barangay (always required with project); structure tag search unchanged (project + municipality + barangay only); list phase filter.
- **Grievance:** Optional phase; PAPS/respondent autofill can copy phase; CSV `phase_name` / `phase_id`; list phase filter; API + PDF.
- **Soft-deleted phase on edit:** disabled select with single inactive option + hidden `phase_id` for POST.
- **Help / Postman / API contract** updated.
- **E2E:** `tests/e2e/project-phases.spec.ts` — `npm run test:e2e:project-phases:fast`


---

## Structure list — Primary only checkbox (2026-07-22)

- **Default `/structure` list:** flat (all classifications), same as before nesting.
- **Filter checkbox “Primary only”:** when checked, main rows are **Primary** with collapsible **Secondary (N)** children; Structure Tag search/filter that matches a Secondary still shows its Primary (matching Secondaries highlighted/expanded).
- Classification = Secondary stays a flat secondary-only list. Export/PDF stay flat.
- Help + Playwright coverage updated.


---

## Structure list filters (2026-07-22)

- **Filter card** on `/structure`: **project**, **municipality**, **barangay** (cascading), Structure Tag # (contains), linked **PAPS** search (name / PAPSID / control # / ownership), **first visit** date from/to, classification, tagging status, show deleted (admin).
- **Columns:** Project, Municipality, and Barangay (plus First visit) available in list/export; toolbar search also matches location names.
- **Toolbar search** always matches Structure ID, tag, description, location names, and linked PAPS (not only when those columns are visible).
- Export and PDF keep the same filters. Help updated. Backup/restore unchanged.


---

## Restore — PDO import skips DROP after mysqldump comments (2026-07-22)

- **Bug:** PDO restore path treated `-- Table structure...` + `DROP TABLE` as one statement starting with `--` and skipped it, so `CREATE TABLE` failed with “table already exists” (common with MariaDB/mysqldump ZIPs when `mysql` client is not on PATH).
- **Fix:** `cli/restore_sql_helper.php` skips line/block comments while splitting statements; keeps MySQL `/*!...*/` versioned comments.
- **Test:** `php tests/cli/restore_sql_comment_drop_test.php`


---

## Profile — Co-Owner ownership & Live-In civil status (2026-07-22)

- **Ownership:** Added **Co-Owner** (`co_owner`). Owner and Co-Owner share the same Civil Status / Spouse–Partner rules.
- **Civil status:** Added **Live-In/Common Law Partner** (`live_in_common_law`). Spouse/Partner name shows for Married **or** Live-In when ownership is Owner or Co-Owner.
- **Structure view / list / PDF / API:** Linked PAPS groups include a **Co-Owner** section (`linked_groups.co_owners`).
- **Fix:** `linkedProfilesGroupedForStructure` now resolves project via `COALESCE(s.project_id, owner, tagged_by)` so structures created with `project_id` (no owner profile) still list linked PAPS.
- **Help / API contract / E2E** updated. No schema migration (slug values fit existing VARCHAR columns).


---

## Security hardening — P1/P2 (2026-07-22)

- **Headers:** `Core\SecurityHeaders` sends CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, and HSTS (HTTPS). Config: `config/app.php` → `security_headers`.
- **Logout CSRF:** Web logout is `POST /logout` with CSRF (account menu form). GET `/logout` removed.
- **API tokens:** Default absolute expiry **7 days**; `last_used_at` (migration **080**) enforces idle timeout (default **72 hours**). Settings: `api_token_expiry_days`, `api_token_idle_minutes`.
- **Login throttle IP:** `X-Forwarded-For` trusted only when `REMOTE_ADDR` is in `trusted_proxies`.
- **Backup download:** `POST /system/backup-restore/download` with CSRF (was GET). Restore remains CLI-only.
- **Profile CSV import:** Requires `.csv` extension and an allowed text/CSV MIME type.
- **Logs:** `logs/.htaccess` denies direct HTTP access (in addition to root rewrite rules).
- **Help modal:** Documented that fragments must stay static/admin-authored (XSS note).
- **E2E:** `tests/e2e/security/p1-p2-hardening.spec.ts` — `npm run test:e2e:security-hardening:fast` (`BASE_URL` configurable).


---

## Profile — single municipality/barangay auto-select (2026-07-21)

- **Profile create/edit:** When a project has only one municipality or barangay, the form now auto-selects it and enables the Structure Tags picker without requiring a manual change event (fixes structure tag AJAX staying disabled in production).


---

## Structure — list classification & status history (2026-07-21)

- **Structure list (`/structure`):** Added **Classification** column (Primary / Secondary / —); sortable, searchable, and included in CSV/PDF export.
- **Structure view:** **Status History** sidebar is active — shows tagging status changes from audit log (and current status when no history exists yet). Saves now record `status_changed` when tagging status fields change.


---

## Profile — structure tab & tag picker classification (2026-07-21)

- **Profile view Structure tab:** Removed **Add structure** button; structures are linked via Structure Tags on the profile form only. Table now includes a **Classification** column.
- **Profile create/edit:** Structure Tags Select2 dropdown shows a table-style list (Tag #, Structure ID, Classification).
- **API:** `GET /api/structure/tag-search` and `GET /api/profile/{id}/structures` include `structure_classification` and `classification_label`.


---

## Profile — multi structure tags, project required (2026-07-21)

- **Web create/edit:** Structure Tags use **Select2 multi-select** (existing tags in the Structure module for the selected **project + municipality + barangay**). Optional; preview list under the select links to each structure. **Project is required**; tag picker stays disabled until project, municipality, and barangay are chosen.
- **Removed:** Auto-create structure rows when saving a profile with a tag.
- **Database:** **`profile_structure_tags`** junction table (migration **079**); legacy **`profiles.profile_structure_tag`** backfilled and kept as first-tag mirror on save.
- **API:** **`GET /api/structure/tag-search`**, **`profile_structure_tags`** array on profile read/write; **`profile_structure_tag`** (first tag) retained for legacy clients. Docs, help, Postman, and Development History updated.
- **E2E:** **`tests/e2e/profile-structure-tags.spec.ts`** — `npm run test:e2e:profile-structure-tags:fast` (tag-search API, multi-tag create/view/edit, project-gated picker).


---

## Structure — Secondary replaces Associated for primary link (2026-07-21)

- **UI/API:** Classification options are **Primary** and **Secondary** only. The Primary-structure picker (Select2 + `GET /api/structure/primary-search`) appears when classification is **Secondary**.
- **Legacy:** Existing DB value `associated` is treated as **Secondary** on read/write (label, form selection, API payload, and save). Column `associated_primary_structure_id` is unchanged. **No migration.**
- **Display/PDF/help/docs:** Linked primary label and guidance updated; API contract and Development Guide reflect the new rule. Backup/restore unaffected (schema unchanged).


---

## Form submit guard — restore Save after cancelled validation (2026-07-17)

- **Bug:** `data-guard-submit` disabled the Save button on submit even when client-side validation (`alert` / `preventDefault`) cancelled the post, leaving users stuck on Grievance create and other guarded forms.
- **Fix:** `public/assets/js/shared/form-submit-guard.js` restores submit buttons when `defaultPrevented` is set after other handlers run.
- **Create Initial Status Effective date:** Prefills from **Date Recorded** and stays in sync when Date Recorded changes (manual override still allowed).
- **Test:** `tests/e2e/grievance/grievance-form-submit-guard.spec.ts` covers Grievance custom alerts, Profile custom validation toasts, and Library native required validation — `npm run test:e2e:form-submit-guard:fast`
- **Combined regression:** `npm run test:e2e:grievance-2026-07-17:fast` runs all E2E coverage for today's initial-status, Date Recorded, import, and submit-guard changes.


---

## Grievance — initial status on create (2026-07-17)

- **Date Recorded required:** Web create/edit forms, API create/update, CSV imports, and the grievance model reject a missing or invalid Date Recorded value. New web forms prefill the current application date/time.
- **Web create form:** Users with `change_grievance_status` can set the initial status, project-scoped progress stage, effective date, note, and image/PDF status attachments. Other users continue to create Open grievances.
- **Atomic history and clocks:** Create writes one matching `grievance_status_log` row and initializes `current_level_started_at` or closure fields from the effective date. This also corrects non-Open CSV imports and API creates whose first history row previously defaulted to Open.
- **API:** `POST /api/grievance/store` supports matching initial status/history fields and multipart status attachments. Non-default status/history requires `edit_grievance` or `change_grievance_status`; In Progress requires a valid project-scoped stage.
- **Guidance and tests:** Help, API/mobile docs, Postman collection, and Playwright coverage updated. No schema migration; database and `public/uploads/grievance/status` remain covered by backup/restore.

