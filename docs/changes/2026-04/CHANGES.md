# PAPeR – Changes (2026-04)

Part of the [changes index](../CHANGES.md). Newest entries first within this month.

---

## E2E (Playwright) — optional database seeding hook (2026-04-30)

- Added Playwright `globalSetup` (`tests/e2e/support/global-setup.ts`) that can reset and seed the database before E2E runs when `E2E_DB_SEED=1`.
- Seed flow runs: `cli/truncate_fresh_install.php --yes`, `seed_grievance_options.php`, `seed_projects_users_field_team.php`, and `seed_profiles.php` (profile count can be overridden with `E2E_SEED_PROFILES`, default `40`).
- Added npm shortcuts: `npm run test:e2e:seed` and `npm run test:e2e:seed:headed`.
- Fixed `tests/e2e/profile/profile-soft-delete.spec.ts` create fixture to include required Profile fields (project/barangay, age/birthday, invitation date, contact person) so seeded runs no longer fail at `/profile/store`.
- Added full Structure workflow E2E spec `tests/e2e/structure/structure-module-full.spec.ts` covering list/details/quick-view modal, view tabs, edit/save, and delete/restore flow.
- Added headed Structure aggregate command `npm run test:e2e:structure-all:headed` to run Structure-module related specs with seeded data.
- Added configurable Playwright slow motion for headed viewing via `PW_SLOW_MO_MS`, plus convenience scripts `npm run test:e2e:seed:headed:slow` and `npm run test:e2e:structure-all:headed:slow`.
- Slow headed runs now auto-scale Playwright timeouts in `playwright.config.ts` (`timeout` and `expect.timeout`) when `PW_SLOW_MO_MS` is set, preventing false failures caused by 30s default limits.
- Added grievance creation E2E suite `tests/e2e/grievance/grievance-create.spec.ts` with slow-headed scenarios for non-PAPS create, PAPS create, and required GRM-mode validation paths.
- Added convenience command `npm run test:e2e:grievance-create:headed:slow` (seeded + slow headed, single worker) for watchable grievance-create scenario runs.
- Added grievance dashboard E2E suite `tests/e2e/grievance/grievance-dashboard.spec.ts` for slow-headed full scenarios (KPI/chart loading, project filtering, and dashboard widget customization persistence).
- Added convenience command `npm run test:e2e:grievance-dashboard:headed:slow` (seeded + slow headed, single worker).
- Added grievance activity history E2E suite `tests/e2e/grievance/grievance-activity-history.spec.ts` to validate Activity History and Status History entries after create, edit, and status-change actions.
- Added convenience command `npm run test:e2e:grievance-activity-history:headed:slow` (seeded + slow headed, single worker).
- Added escalation-focused grievance dashboard E2E suite `tests/e2e/grievance/grievance-dashboard-escalation.spec.ts` that creates many grievance scenarios across stages/projects, uses simulated date to trigger escalation windows, and validates dashboard escalation payload/filter behavior.
- Added convenience command `npm run test:e2e:grievance-dashboard-escalation:headed:slow` (seeded + slow headed, single worker).
- Hardened `grievance-dashboard-escalation.spec.ts` by selecting progress levels dynamically from available option values and by using a future simulated date relative to runtime, avoiding false zero-escalation results.
- Tightened list assertions in `grievance-dashboard-escalation.spec.ts` to grievance row links (`getByRole("link", ...)`) to avoid strict-mode collisions with respondent dropdown options.
- Added grievance list filters E2E suite `tests/e2e/grievance/grievance-list-filters.spec.ts` covering status, project, stage, respondent profile, date range, and `needs_escalation` filters on `/grievance/list`.
- Added dedicated slow-headed 500ms runner `npm run test:e2e:grievance-list-filters:headed:slow500`.
- List-filter suite sets deterministic first-stage SLA (`days_to_address=1`) and uses Development simulated date for reliable `needs_escalation=1` assertions.
- Added main dashboard E2E suite `tests/e2e/dashboard/dashboard-main.spec.ts` covering full `/` scenarios: widget/chart rendering, View All navigation, API contract checks, and grievance metric/status-change flow.
- Added dedicated slow-headed 1200ms runner `npm run test:e2e:dashboard-main:headed:slow1200`.
- Localized recent grievance/dashboard E2E fixtures to Philippine context: Filipino respondent names, PH mobile patterns, and complaint/resolution narratives that match barangay/LGU project workflows.
- Expanded Philippine-context localization to profile/structure E2E fixtures and shared helpers (including integration/linking flows): Filipino names, locally realistic contact labels/numbers, and PH-oriented structure/location wording.
- Localized remaining non-profile/structure grievance fixtures (`grievance-dashboard.spec.ts`, `grievance-activity-history.spec.ts`) to Philippine context and stabilized one recent-list assertion.
- Updated E2E run modes: added `test:e2e:seed:fast:background` for faster seeded background runs (no slowMo), and standardized slow headed watch mode to `1200ms` (`test:e2e:seed:headed:slow`, `test:e2e:dashboard-main:headed:slow1200`).
- Added dedicated slow-headed 1200ms script for profile-structure linkage: `npm run test:e2e:profile-structure-linking:headed:slow1200`.
- Hardened Playwright login helper (`tests/e2e/support/auth.ts`) to wait for the submit button and await URL transition away from `/login`, reducing occasional headed-run flake (`Target page, context or browser has been closed`) in early test setup.
- Added `tests/e2e/system/realtime-security.spec.ts` to validate security reliability for `/system/realtime-security` (auth gating, telemetry cards/events rendering, settings persistence via summary API, and malware-check contract).
- Added slow-headed runner `npm run test:e2e:realtime-security:headed:slow1200` and verified the suite passes (`4 passed`).
- Added `tests/e2e/users/users-roles-full.spec.ts` for `/users/roles` full-functionality E2E (auth gate, roles list/search, add role with capabilities, edit role capability updates, and Administrator-role lock behavior).
- Added slow-headed runner `npm run test:e2e:users-roles:headed:slow1200` and verified the suite passes (`3 passed`).
- Added `tests/e2e/users/users-full-capabilities.spec.ts` for `/users` full-functionality E2E with generated-role capability validation across modules (create role/user, user CRUD path, login as generated user, allowed vs blocked module access checks, cleanup delete).
- Added slow-headed runner `npm run test:e2e:users:headed:slow1200` and verified the suite passes (`2 passed`).
- Added `tests/e2e/profile/profile-generate-50.spec.ts` to generate 50 profiles using Playwright flow only (explicitly not using PHP seeders), with fallback path when project/barangay data is unavailable.
- Added command `npm run test:e2e:profile-generate-50` (removes `E2E_DB_SEED`/`PW_SLOW_MO_MS` for clean non-seeder execution) and validated successful run (`1 passed`, 50 profiles created).
- Profile generator now auto-creates prerequisite project data with affected barangays via UI (`/library/create`) when no project/barangay exists, ensuring barangay-dependent profile generation works even in clean non-seeded environments.


---

## Structure — tagging extension fields (visit dates, GPS, classification, status) (2026-04-30)

- **Structure list (`/structure`):** Added **Status** column (`tagging_status`) with human-readable labels; sortable and searchable when that column is selected; CSV/PDF list exports use the same labels via `CsvExporter` / `PdfTable`.
- **Database:** **`migration_047_structure_tagging_fields`** adds location, three visit date/remark pairs, classification (`primary` / `secondary` / `associated`) with optional **`associated_primary_structure_id`** (FK to another structure when **Associated**), actual usage, GPS coordinates, tagging status (Tagged, Owner Refused, Owner not Around, Vacant, Abandoned, Under Construction, Temporary, Other + specify, with **Reason for refusal** when refused).
- **Web:** **`/structure/edit/{id}`** and **`/structure/create`** include the new **Tagging details** block after Structure Tag #; **Associated** shows a Select2 search against **`GET /api/structure/primary-search`** (primary-classified structures only). **`/structure/view/{id}`** Structure tagging information tab and structure PDF show the same fields.
- **API:** **`GET /api/structure/{id}`** and multipart create/update accept the new fields; **`GET /api/structure/primary-search`** supports the associated-primary picker.
- **Docs:** `docs/API_CONTRACT.md`, `docs/DEVELOPMENTGUIDE.md`, `docs/DevelopmentHistory/5.1.2026.json`, README DevelopmentHistory map.


---

## Structure — view page tabs (2026-04-30)

- **`/structure/view/{id}`:** Main content is split into three Bootstrap tabs: **Structure tagging information** (STRID, structure tag #, extended tagging fields when migration 047 is applied, description, tagging and structure images), **Paps information** (linked profiles by role, same tables as before), and **Detailed Measurements** (`other_details`, aligned with the edit form’s **Other details** field).
- **Tests/docs:** E2E structure linking test activates the Paps tab before assertions; `docs/API_CONTRACT.md`, `docs/DEVELOPMENTGUIDE.md`, `docs/DevelopmentHistory/4.30.2026.json`, README DevelopmentHistory map.


---

## Structure — edit form: no Paps/Owner picker (2026-04-30)

- **`/structure/edit/{id}`** and **`/structure/create`:** No **Paps/Owner** field; short help text explains tag-based linking. **`POST /structure/update/{id}`** keeps the stored **`owner_id`** unchanged. **`POST /structure/store`** creates with null **`owner_id`** unless **`owner_id`** is posted (e.g. API/embed).
- **`POST /api/structure/store`:** **`owner_id`** is optional (was required).
- **Docs:** `docs/API_CONTRACT.md`, `docs/DEVELOPMENTGUIDE.md`, `docs/DevelopmentHistory/4.30.2026.json`.


---

## Profile — ownership-dependent civil status and spouse flow (2026-04-29)

- **Form logic (`/profile/create`, `/profile/edit/{id}`):** Added **Civil Status** (`single`, `married`, `widowed`) that appears only when **Type of structure ownership = Owner**.
- **Conditional spouse field:** **Spouse** now appears only when **Owner + Married**; switching away from those conditions clears hidden values on the client.
- **Persistence rules (web + API):** Added `profiles.civil_status` and updated payload normalization so `spouse_name` is stored only when `structure_ownership_type=owner` and `civil_status=married`.
- **Database:** Added `migration_046_profile_civil_status` for `profiles.civil_status VARCHAR(20) NULL`.
- **Read surfaces:** Profile view and profile PDF now show Civil Status for Owner records and show Spouse only for Married owners.
- **Tests and docs:** Updated profile ownership E2E behavior and API/docs references (`tests/e2e/profile/profile-ownership-fields.spec.ts`, `docs/API_CONTRACT.md`, `docs/DEVELOPMENTGUIDE.md`, `docs/DevelopmentHistory/4.29.2026.json`).


---

## System — Realtime Security module (2026-04-23)

- **New admin page:** Added **`System > Realtime Security`** (`/system/realtime-security`) as a Wordfence-like operational security panel.
- **Realtime telemetry:** Dashboard cards summarize last-24h auth activity from `logs/auth.log` (attempts, failed logins, blocked attempts, and a computed risk level), plus a recent auth events table.
- **Config controls:** Added persisted app settings (`realtime_security_*`) for monitoring enablement, suspicious-IP auto-block mode, failed-login threshold, detection window, and lockout-alert visibility.
- **Malware scan button:** Added **Run Malware Check** action (`POST /system/realtime-security/malware-check`) that scans PHP files in `App/`, `Core/`, `public/`, and `cli/` for suspicious patterns (e.g. `eval(`, `base64_decode(`, `shell_exec(`), then stores and displays a findings report.
- **Routes:** Added web routes `GET /system/realtime-security`, `POST /system/realtime-security/update`, and `POST /system/realtime-security/malware-check`; added API routes `GET /api/system/realtime-security` and `POST /api/system/realtime-security/malware-check` (admin-only JSON envelope).
- **AJAX latest events:** `Latest auth events` (and top metric cards) now update via polling `GET /api/system/realtime-security` every 10 seconds using `public/assets/js/realtime_security/index.js`, so admins no longer need to refresh the page to see new auth events.
- **IP auto-block enforcement:** Login throttling now enforces IP block at the **Realtime Security failed-login threshold** when `Realtime Security` is enabled and `Auto-block suspicious IPs` is on. This applies to both web and API login attempts through `Core/LoginThrottle`.
- **Navigation:** Added **Realtime Security** under the **System** menu (top-nav and sidebar layouts), including active-state highlighting.
- **Docs:** `docs/API_CONTRACT.md`, `docs/DEVELOPMENTGUIDE.md`, and `docs/DevelopmentHistory/4.23.2026.json`.


---

## Operations — System Backup UI (downloadable), restore kept CLI-only (2026-04-18)

- **System menu:** Added **`System > Backup/Restore`** page for administrators to run backup from the web UI.
- **Backup UI behavior:** Runs `cli/backup.php` from web action, shows command output, lists archives under `storage/backups`, and provides **download** links for each ZIP (including quick "latest backup" download).
- **Restore hardening:** Removed web restore flow/routes to reduce misuse risk; restore is intentionally **CLI-only** (`php cli/restore.php --from=... --yes`).
- **Windows/XAMPP safety:** Backup web action resolves and validates a true CLI `php.exe` (supports `PHP_CLI_PATH`) before execution to avoid using non-CLI binaries.
- **GB-scale backup mode:** `cli/backup.php --large-mode` adds streamed SQL dump output (no in-memory SQL blob), disk-space preflight estimation, lower ZIP compression overhead (`CM_STORE`), and periodic upload-archiving progress logs.
- **GB-scale restore mode:** `cli/restore.php --large-mode` adds archive/extraction preflight checks (compressed/uncompressed sizes and temp free space), long-run safeguards, and progress logs for SQL import (PDO path) and upload file restoration.
- **Pre-restore safety snapshot:** After restore confirmation (`YES` or `--yes`), `cli/restore.php` now creates `storage/backups/paper-before-restore-*.zip` before importing SQL. Restore aborts if this safety backup fails.
- **Restore preview before YES:** CLI now prints pre-restore impact summary and warnings (target DB, SQL statement preview counts, destination DB size/table summary, uploads replace/add/retain estimates with sample paths).


---

## Operations — CLI backup and restore (2026-04-18)

- **`cli/backup.php`:** Creates a single ZIP (`storage/backups/paper-backup-*.zip` by default) with **`manifest.json`**, **`database.sql`**, and **`uploads/`** (copy of `public/uploads/`). Uses **`mysqldump`** when available (`MYSQLDUMP_PATH` or `--mysqldump=...`, plus common XAMPP path on Windows); otherwise a **PDO**-based SQL export. Flag **`--no-uploads`** for DB-only archives.
- **`cli/restore.php`:** **`--from=backup.zip`** extracts and imports SQL into the database from **`config/database.php`**, then restores **`public/uploads`** from the archive unless **`--no-uploads`**. Prefers **`mysql`** client (`MYSQL_PATH` or `--mysql=...`); falls back to PDO SQL parsing for small/medium dumps. Requires confirmation unless **`--yes`**.
- **`storage/backups/.gitignore`:** Ignores backup artifacts; only `.gitignore` is tracked.
- **`config/app.php`:** CORS / OPTIONS handling runs only when **not** CLI so `bootstrap.php` (migrate, backup, restore, etc.) does not trigger undefined `REQUEST_METHOD` or exit on preflight.
- **Docs:** `README.md` (§10), `docs/DEVELOPMENTGUIDE.md`, `docs/DevelopmentHistory/4.18.2026.json`.


---

## Dashboard (main) — unwrap `/api/dashboard` JSON envelope in browser (2026-04-17)

- **Issue:** `Core\Controller::json()` wraps every `/api/*` body as **`{ success, data, error }`**. The main dashboard page (`App/Views/dashboard/index.php` → **`public/assets/js/dashboard/index.js`**) still passed the full response into `renderDashboard()`, so **`data.profile` / `data.structure`** were read as **undefined** (real values live under **`response.data.profile`**, etc.). Network showed correct JSON while tiles and charts stayed empty or default.
- **Fix:** **`unwrapApiPayload()`** in **`dashboard/index.js`** (same pattern as **`public/assets/js/grievance/dashboard.js`**): on success, pass **`resp.data`** into **`renderDashboard`**; reject when **`success === false`**.
- **Docs:** `docs/CHANGES.md`, `docs/API_CONTRACT.md`, `docs/DEVELOPMENTGUIDE.md`, `docs/FRONTEND_JS_CONVENTIONS.md`, `docs/DevelopmentHistory/4.17.2026.json`, `README.md` (DevelopmentHistory map).


---

## Profile — tabbed view/edit, in-page Structure CRUD, main save via API (2026-04-16)

- **Tabs:** Profile **view** and **edit** use Bootstrap nav tabs: Main, Socio Economic, Structure, and Validation Data (`App/Views/profile/view.php`, `App/Views/profile/form.php`).
- **Structure tab (in-page):** Shared modal partial `App/Views/partials/profile_structure_crud.php` and `public/assets/js/profile/structure-tab.js` with `window.profileStructureTabConfig` (profile id, owner label, `baseUrl`, capability flags, image column on view). The table loads **`GET /api/profile/{id}/structures`**. Create/update submit **`multipart/form-data`** to **`POST /api/structure/store`** and **`POST /api/structure/update/{id}`** (fields `tagging_images[]`, `structure_images[]`, optional `tagging_images_remove[]` / `structure_images_remove[]`). Delete: **`POST /api/structure/delete/{id}`**. Actions respect **`view_structure`**, **`add_structure`**, **`edit_structure`**, **`delete_structure`**.
- **API:** `App\Controllers\Api\StructureController::storeApi` responds with **`{ "success": true, "data": { "id": <new id> } }`** and records **`AuditLog`** for structure create. Structures-by-owner JSON is served from the API layer (see `public/index.php` → `Api\ApiController@profileStructures`).
- **Edit — Main tab:** Primary field saves use AJAX from **`public/assets/js/profile/form.js`** to **`POST /api/profile/update/{id}`** (JSON envelope with updated profile), avoiding a full form POST/redirect for that path.
- **E2E:** **`tests/e2e/profile/profile-structure-tab.spec.ts`** covers Structure tab visibility, Add-structure modal, and create-with-image (asserts store response and refreshed table row). **`tests/e2e/profile/profile-edit.spec.ts`** follows the AJAX main-save flow. **`playwright.config.ts`** applies default **`ADMIN_USER`** / **`ADMIN_PASS`** when **`CI`** is unset so local Playwright runs do not require shell environment variables. **`@types/node`** added for test typings.
- **Cross-doc:** `docs/DEVELOPMENTGUIDE.md`, `docs/API_CONTRACT.md`, `docs/FRONTEND_JS_CONVENTIONS.md`, `docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md`, `docs/FrameworksGuide.txt` (API line lists `GET /api/profile/{id}/structures`), `docs/DevelopmentHistory/4.16.2026.json`, `README.md` (features + DevelopmentHistory map).


---

## Documentation — setup tree, migrations, seeders (2026-04)

- **README.md:** Setup steps renumbered; added **Composer / mPDF** (`composer install`) for staff PDF export; project tree under `database/` now points at **`seeders/`** (not non-existent `seed_*.php` at `database/` root); noted **`composer.json`** in the structure outline.
- **docs/DEVELOPMENTGUIDE.md:** Project tree includes **`composer.json` / `vendor/`** note; **§2** mentions mPDF + `PdfExport::requireLibrary()`; **§5** documents two migrations sharing the **`011`** filename prefix (order matches on-disk sort).
- **docs/FrameworksGuide.txt:** Seeder list aligned with shipped scripts (**`seed_profiles.php`**); path clarified as **`database/seeders/`**; brief Composer/mPDF note for PDF.


---

## Profile — automated functional coverage expansion (2026-04-15)

- **Playwright coverage completed for pending QA sections:** added profile suites for **Export/PDF** and **Security/Negative/Robustness**, and expanded soft-delete assertions to cover full admin deleted-mode matrix (`active`, `with`, `only`).
- **New specs:** `tests/e2e/profile/profile-export-pdf.spec.ts`, `tests/e2e/profile/profile-security.spec.ts`; existing specs updated: `profile-soft-delete.spec.ts`, `profile-import.spec.ts`, `profile-permissions.spec.ts`, plus `tests/e2e/support/auth.ts` and `package.json` scripts.
- **Security negatives automated:** query parameter tampering fallback, CSRF rejection for direct delete POST without token, and script-like input render safety check on list rows.
- **Docs:** `docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md` now includes a `/profile` QA-to-automation coverage matrix (sections A-J status and mapped spec files).


---

## Profile — list filters and minimum age 18 (2026-04-15)

- **List filters (web + `GET /api/profile/list`):** Facets for project, field personnel, barangay, age min/max, date of invitation range, PAPSID, and control number (`Profile::parseListFiltersFromRequest`, `Profile::listPaginated`). Export/PDF list actions respect the same GET filters.
- **Minimum age:** `Profile::MINIMUM_PROFILE_AGE` (18) enforced on web create/edit, CSV import, and REST store/update via `validateMinimumAgeInput()`; profile form birthday `max` and helper text aligned.
- **Docs:** `docs/DevelopmentHistory/4.15.2026.json` (full breakdown).


---

## Users — password history check and admin form autofill (2026-04)

- **Password history:** `App\PasswordPolicy::validateForUser()` loaded prior hashes with `LIMIT ?`. On MySQL + PDO the limit can be bound as a quoted string (`'5'`), which triggers **SQLSTATE 1064**. The query now uses a **capped integer** `LIMIT` in the SQL text (still scoped by `user_id` bind only).
- **User add/edit (`App/Views/users/form.php`):** `autocomplete="off"` on the form and text fields; `autocomplete="new-password"` on the password field; project picker includes common extension ignore attributes so browsers and password managers are less likely to corrupt fields or encoding.
- **Docs:** `docs/DEVELOPMENTGUIDE.md` §6, `docs/DevelopmentHistory/4.15.2026.json`, `README.md` (DevelopmentHistory map).


---

## Grievance respondents list and create-form autocomplete (2026-04)

- **Respondent profiles page (`/grievance/respondents`):** List query unions registered **`grievance_respondents`** rows with **inline** groups of grievances that have **no** `respondent_id`, including **PAPS** grievances whose display names come from the linked **profile** when name fields on the grievance are empty. **Is PAPS** reflects grievance-level mix: **`GrievanceRespondent::formatIsPapsLabel()`** shows **Yes**, **No**, or **Yes, No** when both occur; same logic for the respondents **PDF** export.
- **Grievance create respondent lookups:** Autocomplete datalists, **history**, and **latest-details** APIs are not called until the user has typed **at least 3 letters** in **first name** (enforced in **`public/assets/js/grievance/form.js`** and **`App\Controllers\Api\ApiController`**). Name matching now includes linked profile fallback (`profiles` via grievance/respondent `profile_id`) for first/middle/last/history/latest endpoints, so profile-linked respondents are searchable and detectable as having prior grievances. The grievance form shows a short hint under the first name field.
- **Docs:** `docs/DEVELOPMENTGUIDE.md` §3, `docs/API_CONTRACT.md` (respondent helpers), `docs/FrameworksGuide.txt`, `docs/FRONTEND_JS_CONVENTIONS.md`, `docs/DevelopmentHistory/4.14.2026.json`.


---

## Security — session regeneration on login (2026-04)

- **What changed:** `Core\Auth::login()` now calls `session_regenerate_id(true)` when the PHP session is active, immediately after setting `user_id` and `last_activity`. Addresses **SEC-2026-001** (session fixation) from `docs/SecurityAudit/sec-aud-4.10.2026.json`.
- **Why:** Both password login and email 2FA completion use `Auth::login()` followed by `UserSession::onLogin()`, so the new session id is the one stored in `user_sessions`.
- **Docs:** `docs/DEVELOPMENTGUIDE.md` §2 Auth, `docs/FrameworksGuide.txt`, security audit file, and `docs/DevelopmentHistory/4.14.2026.json` updated accordingly.


---

## Documentation aligned with repository (2026-04)

- **README.md**, **docs/DEVELOPMENTGUIDE.md**, **docs/FrameworksGuide.txt**, and **docs/API_CONTRACT.md** now describe the seeders and CLI tools that actually ship (`seed_grievance_options.php`, `seed_projects_users_field_team.php`, `cli/truncate_fresh_install.php`), **migration 038** (soft delete), **System > Operational** routes and **`GET /api/system/operational`**, optional **`sample-imports/`** and **`dbdump/`**, **`docs/SecurityAudit/`** in the doc map, and the **HTTP route index** for `/api/*`.
- **Legacy references removed:** Historical `seed_profiles_structures*.php`, `cli/truncate_seed_tables.php`, and `cli/truncate_grievances.php` are no longer listed as current paths (they are not present in this tree).


---

## Profiles — questionnaire fields removed (2026-04)

- **What changed:** PAP profile **Relevant Information**, **Additional Information**, **Type of Structure Ownership**, and **HH income** (and their notes, flags, and per-section attachments) were removed from the web UI, PDF profile export, REST API payloads, grievance “PAPS info” preview, notification email field lists, and CSV/list queries that referenced those columns.
- **Database:** **`migration_037_remove_profile_questionnaire_fields`** drops the associated `profiles` columns (including `structure_ownership_types` from migration 032). Fresh installs still run migrations 003 and 032 before037, so the net schema matches upgraded databases.
- **Existing installations:** Migration 037 is upgrade-safe because it checks current `profiles` columns (`SHOW COLUMNS`) and only drops columns that exist. Still take a backup before applying because removed column data is irreversible unless restored from backup.
- **Unchanged:** The **Structure** module (physical structures linked to a profile), **`structure_count`** on profiles, and structure images/attachments under `/serve/structure` are unchanged.


---

## Installation and data

- **Grievance options seed data:** Added `database/seeders/seed_grievance_options.php` to seed commonly used default data for the Grievance module (vulnerabilities, respondent types, GRM channels, preferred languages, grievance types, categories). Safe to re-run; skips tables that already have data. README installation steps updated to include: run `php database/seeders/seed_grievance_options.php` after migrations.


---

## Soft delete restore and deleted filters (2026-04)

- **Restore actions added:** Core entities now support restore (`is_deleted=0`, `deleted_at=NULL`, `deleted_by=NULL`) from soft-deleted state.
  - Web routes: `/profile/restore/{id}`, `/structure/restore/{id}`, `/library/restore/{id}`, `/grievance/restore/{id}`
  - API routes: `/api/profile/restore/{id}`, `/api/structure/restore/{id}`
- **Admin-only list filters:** Profile, Structure, Library, and Grievance list pages now provide a **Show deleted** selector:
  - `active` (default), `with`, `only`
- **Admin-only restore UI:** Deleted records in those list pages show a **Restore** action instead of Edit/Delete.
- **Model/query behavior:** List methods in `Profile`, `Structure`, `Project`, and `Grievance` now accept deleted-mode filtering to support active/with/only views.
- **Case numbering safety:** Grievance case number generation now ignores soft-deleted grievances.


---

## Profile audit and view polish (2026-04)

- **Profile audit change detection:** Contact changes no longer produce false positives when contacts are unchanged.
- **Readable contact audit output:** `contacts_json` change entries now render as human-readable contact text (e.g., `name: number`) instead of raw JSON blobs.
- **Profile view cleanup:** The **Structures** card is hidden when a profile has no linked structures (removes the empty-state card text).


---

## Grievance dashboard SQL fix (2026-04)

- Fixed API grievance dashboard join filter ambiguity (`is_deleted`) by using alias-safe conditions in joined queries to prevent SQLSTATE 23000 / ambiguous column errors.


---

## User profile and account

- **My Profile page:** New `/account` route and `AccountController`; view shows logged-in user’s profile (username, display name, email, role, linked projects). Accessible from the user dropdown in the header (top right) as “My Profile”. Edit link shown when user has `edit_users` capability.


---

## Notifications system

- **Notification icon and dropdown:** Notification bell icon added beside the user dropdown in both sidebar and top-nav layouts. Badge shows count of unread notifications; dropdown lists recent unread items with “View all notifications” link.
- **Real-time badge:** AJAX polling (no WebSockets) for notification count; polling interval 15 seconds. API: `GET /api/notifications` (returns count and list for bell dropdown).
- **Notification settings:** On Administrator General Settings (`/settings`), new “Notification Settings” card with checkboxes:
  - Notify when **New Profile** on linked projects  
  - Notify when **Profile updated** on linked projects  
  - Notify when **New Grievance** on linked projects  
  - Notify when **Grievance status change** on linked projects  
  - (New Structure uses the same preference as New Profile for linked projects.)
- **Defaults for all users:** Migration 017 and notification preference logic ensure all existing and new users have these notification options **checked by default** (stored in `user_dashboard_config`, module `notification_preferences`).
- **Clickable notifications:** Clicking a notification in the bell or on the notifications page marks it as opened (`clicked_at` set), redirects to the related entity (profile/structure/grievance view). Notifications are no longer deleted on click so they remain in history.
- **Notification message content:** Grievance status-change notifications include **from → to** status (and level) in the message. Profile-update notifications include **modified fields** in the message (same style as Activity History: field: from → to).
- **Notification history page:** New page `/notifications` (Notification History) with:
  - Paginated table of all notifications (new and opened) for the current user  
  - **Filters:** date range (From/To), **Module** (Profile, Structure, Grievance), **Project**  
  - Columns: When, Message, Type, Status (New/Opened), Action (Open)  
  - Pagination preserves filter query params  
- **Database:** Migration 016 added `notifications` table; migration 019 added `project_id` and `clicked_at` to support project filter and “opened” state. New notifications store `project_id` for filtering.


---

## Activity and audit history

- **Audit log:** New `audit_log` table (migration 018) and `App\AuditLog` service. Records entity_type, entity_id, action (e.g. created, updated, status_changed), optional JSON `changes`, created_at, created_by.
- **History sidebar:** New partial `App/Views/partials/history_sidebar.php` used on Profile, Structure, and Grievance **view** pages. Shows:
  - **Activity History:** creation and updates from audit_log with who/when and field-level changes (from → to).
  - **Status History:** grievances use `grievance_status_log` (including per-entry attachment links when files were uploaded with a status change). **Structures** show tagging status history from `audit_log` (`status_changed` on tagging status fields) on the view page sidebar.
- **Recording:** ProfileController, StructureController, and GrievanceController record create/update/status_changed in AuditLog. Profile update only records when there are actual field changes; boolean fields compared robustly to avoid noise.


---

## CLI fresh-install reset

- **`cli/truncate_fresh_install.php`:** Destructive reset that keeps the `migrations` table, roles/capabilities, and the `admin` user, truncates transactional and grievance lookup tables, clears `app_settings`, removes non-admin users, and clears related sessions/tokens/preferences. Use `php cli/truncate_fresh_install.php` (prompts for `YES`) or `php cli/truncate_fresh_install.php --yes`. Afterward run `php database/seeders/seed_grievance_options.php` and optional `php database/seeders/seed_projects_users_field_team.php` as needed. See **docs/DEVELOPMENTGUIDE.md** §6 for the authoritative table list.


---

## Bug fixes and small improvements

- **Profile form:** Fixed parse error in profile form view (unescaped quotes in JavaScript referencing CSRF meta tag).
- **NotificationService::getForUser:** Fixed LIMIT parameter binding (PDO does not support bound LIMIT; value inlined safely).
- **StructureController::handleUpload:** Visibility changed from private to protected so `Api\StructureController` can call it.
- **Profile view:** Activity history sidebar now always visible on profile view (removed incorrect conditional that hid it when user had no structures). Removed stray `endif` that caused parse error on profile view.
- **Profile update audit:** Refined diff logic for boolean fields and “no change” detection so irrelevant updates are not logged.


---

## Frontend JavaScript separation

- **Externalized view JS:** JavaScript behavior from PHP views is separated into `public/assets/js/<module>/...` (module/view-aligned files such as profile, structure, grievance, users, library, dashboard, security settings, debug log, and shared partial/layout scripts).
- **Config bridge pattern:** Views pass minimal runtime data through `window.*Config` objects (for example `window.profileFormConfig`) then load external scripts.
- **No inline handlers policy:** Inline event attributes (`onclick`, `onsubmit`, `onchange`, `onerror`, etc.) were removed and replaced with delegated/shared listeners in external JS (e.g. global confirm and page-jump hooks in `public/assets/js/layout/main.js`).


---

## File and route reference

| Area | Files / routes |
|------|-----------------|
| Account | `App/Controllers/AccountController.php`, `App/Views/account/index.php`, route `/account` |
| Notifications | `App/Controllers/NotificationController.php`, `App/NotificationService.php`, `App/UserNotificationSettings.php`, `App/Views/notifications/index.php`, routes `/notifications`, `/notifications/click/{id}`, `GET /api/notifications` |
| Settings (notifications) | `App/Controllers/SettingsController.php` (updateNotifications), `App/Views/settings/index.php` (Notification Settings card) |
| Audit / history | `App/AuditLog.php`, `App/Views/partials/history_sidebar.php`, migrations 018 |
| Notifications DB | migrations 016 (notifications table), 017 (notification defaults), 019 (project_id, clicked_at) |
| Layout (bell, user menu) | `App/Views/layout/main.php` (notification dropdown, “My Profile”, “Notifications”, “View all notifications”) |


*This summary reflects changes implemented during development. For current structure and conventions, see DEVELOPMENTGUIDE.md.*

