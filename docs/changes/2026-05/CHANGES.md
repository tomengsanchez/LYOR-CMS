# PAPeR – Changes (2026-05)

Part of the [changes index](../CHANGES.md). Newest entries first within this month.

---

## Unique control number and grievance case number (2026-05-29)

- **Migration 069:** unique active values via generated columns (`control_number_unique`, `grievance_case_number_unique`); empty values stored as NULL (multiple blanks allowed).
- **Validation:** `Profile::validateUniqueControlNumber()`, `Grievance::validateUniqueCaseNumber()` on create/update (web, API, CSV import).
- **Live check APIs:** `GET /api/profile/check-control-number`, `GET /api/grievance/check-case-number` — forms show inline warning and block submit on duplicate.
- **CLI:** `php tests/cli/unique_control_case_test.php`


---

## Grievance case number concurrency (2026-05-29) — CRITICAL audit

- **`Grievance::create`:** when `grievance_case_number` is empty, allocates under **`MySqlNamedLock::LOCK_GRIEVANCE_CASE`** through **`INSERT`** (same pattern as PAPSID/STRID).
- **`generateCaseNumber()`:** preview only; uses the same lock but does not insert — **do not** call it before `create()` for auto-numbered cases.
- Web/API controllers call **`Grievance::create($data)`** directly (no pre-generation).
- **CLI:** `php tests/cli/grievance_case_number_lock_test.php`


---

## Migration runner safety (2026-05-29)

- **`Core\MigrationRunner`:** exclusive `paper_migrate` lock; **stop on first failure**; best-effort `down()` when `up()` throws; records migration only after successful `up()`.
- **`Core\MigrationScope`:** `transaction()` for DML; `ddlThenTransactionalDml()` for idempotent DDL + transactional backfill (MySQL DDL cannot roll back).
- **Example:** migration 066 backfill wrapped in transactional DML.
- **CLI:** `php tests/cli/migration_scope_test.php`


---

## MySQL named locks for multi-server ID generation (2026-05-29)

- **`Core\MySqlNamedLock`:** centralized `GET_LOCK` / `RELEASE_LOCK` with timeout checks and guaranteed release.
- **PAPSID / STRID / grievance case numbers:** allocation + `INSERT` run inside the same lock (`Profile::create`, `Structure::create`, `Grievance::create` when case number is auto-generated).
- **Multi-app-server:** safe when all PHP nodes share **one MySQL primary** (locks are server-wide, not per PHP process). Not valid for read-replica writes, Galera multi-primary, or per-server databases — see `docs/DEVELOPMENTGUIDE.md`.
- **CLI:** `php tests/cli/mysql_named_lock_test.php`


---

## Grievance JSON lookup indexes (2026-05-29)

- **Migration 068:** multi-valued indexes on `grievances.grievance_type_ids` and `grievance_category_ids` (**MySQL 8.0.17+**; skipped on MariaDB / older MySQL).
- **`App/GrievanceJsonSql`:** `memberOf()` uses **`MEMBER OF`** on MySQL 8.0.17+ and **`JSON_CONTAINS(..., CAST(id AS CHAR), '$')`** on MariaDB / older MySQL (MariaDB does not support `CAST AS JSON` or `MEMBER OF`).
- **Queries:** grievance dashboard category/type breakdowns and respondent history name resolution use `GrievanceJsonSql::memberOf()`.
- **CLI:** `php tests/cli/grievance_json_mvi_test.php`


---

## Grievance dashboard API optimized (2026-05-29)

- **`GET /api/grievance/dashboard`:** metrics moved to **`App/GrievanceDashboardStats`** with shared **`App/GrievanceDashboardFilter`** (project scope + `date_from` / `date_to`). Status totals use one `GROUP BY status` query instead of separate count + breakdown scans. Escalation grouping preloads progress levels per project (no N+1 in the escalation row loop). Uses denormalized **`current_level_started_at`** for SLA checks.
- **CLI:** `php tests/cli/grievance_dashboard_filter_test.php`


---

## Main dashboard API optimized (2026-05-29)

- **`GET /api/dashboard`:** entity counts from `profiles` / `structures` / `grievances` with JOINs (no `audit_log IN (subquery)` for created/updated). Optional `date_from` / `date_to` (YYYY-MM-DD). Remaining audit counts (structure images uploaded, grievance status changes) use indexed JOINs.
- **UI:** main dashboard date filter (From / To / Apply / Clear).
- **Migration 067:** `audit_log` index `(entity_type, action, created_at)`.
- **CLI:** `php tests/cli/dashboard_date_range_test.php`


- **Migration 066:** `grievances.current_level_started_at` + index `idx_grievances_escalation_clock`; backfill from `grievance_status_log`.
- **Escalation overdue SQL** (list filter, main dashboard, grievance dashboard API) now uses `DATEDIFF(?, DATE(g.current_level_started_at))` instead of correlated `grievance_status_log` subqueries.
- **Status updates** set/clear the clock on status or progress-level change only (note-only updates preserve the clock).
- **API:** `GET /api/grievance/{id}` includes `current_level_started_at`.
- **CLI:** `php tests/cli/grievance_escalation_clock_test.php`


- **B1/B2:** `public/.htaccess` blocks direct `/uploads/`; `public/uploads/.htaccess` denies all; root `.htaccess` blocks `config/`, `App/`, etc. when doc root is project root.
- **B4:** `setasign/fpdi` updated to **v2.6.7** (CVE-2026-45802).
- **E2E:** `tests/e2e/production-blockers.spec.ts` — sensitive paths, upload block, PDF (fpdi), backup UI + CLI smoke.
- **Run:** `npm run test:e2e:production-blockers` (dev); `npm run test:e2e:production-blockers:prod` enforces B3 (non-default password).
- **`npm run test:e2e`** now includes production-blockers spec.


- **`tests/e2e/grievance/grievance-escalation-badges.spec.ts`:** escalation badges only (not full suite) — SLA, yellow **days left**, simulated date, red **days overdue**, `needs_escalation=1` filter.
- **`tests/e2e/support/watch.ts`:** `pauseToWatch()` holds the screen with an on-page banner (`PW_WATCH_PAUSE_MS`, default 4000).
- **`npm run test:e2e`** now runs **api-smoke + full-system only**; escalation is separate.
- **Watch headed slow:** `npm run test:e2e:escalation` — forces **5000ms** slowMo + **4s** pauses at each badge step.
- **Fast headless:** `npm run test:e2e:escalation:fast`


---

## E2E test suite rebuilt (2026-05-29)

- **New Playwright layout** under `tests/e2e/` after prior folder removal.
- **`tests/e2e/full-system.spec.ts`:** serial full journey — login, dashboard + API, library bootstrap, profile (invitation card), structure (DMS GPS), grievance, users, system pages, export smoke, cleanup.
- **`tests/e2e/api-smoke.spec.ts`:** session-cookie API envelope checks.
- **Support:** `tests/e2e/support/` — auth, global-setup, bootstrap, profile/structure/grievance helpers.
- **Run headed @ 1200ms:** `npm run test:e2e` (local default in `playwright.config.ts`; CI stays headless).
- **Headless fast:** `npm run test:e2e:headless` or `HEADLESS=true PW_SLOW_MO_MS=0`.


---

## Structure GPS — original text columns (2026-05-29)

- **Migration 065:** `gps_latitude_text`, `gps_longitude_text` (VARCHAR 64) store the user’s original input; `gps_latitude` / `gps_longitude` remain DECIMAL for maps and API decimals.
- **`Structure::expandTaggingPayload()`:** saves trimmed raw strings to `*_text` and parsed floats to decimal columns.
- **Forms / view / PDF:** display stored text when present (`gpsCoordinateDisplay()`); map links use decimals via `gpsMapLinkFromStructure()`.
- **API `GET /api/structure/{id}`:** includes `gps_latitude_text`, `gps_longitude_text` alongside decimal `gps_latitude` / `gps_longitude`.


---

## Structure GPS — degrees/minutes format (2026-05-29)

- **`Structure::parseGpsCoordinate()`:** accepts decimal degrees and N/E/S/W degrees–minutes (e.g. `N15°58.209`, `E120°9.687`) for save and Google Maps links.
- **Structure edit form:** live **Open in Google Maps** preview when both coordinates parse.


---

## Structure view — Visitation Data tab (2026-05-29)

- **`App/Views/structure/view.php`:** new **Visitation Data** tab; first/second/third visit dates and remarks moved out of **Structure tagging information**.
- **`_structure_visitation_display.php`**, **`_structure_visitation_fields.php`:** shared visit field blocks for view and edit form.


---

## Backup / restore — invitation schema (2026-05-29)

- **`cli/backup_schema_helper.php`:** schema snapshot (last migration, `profiles` invitation columns) for manifest and post-restore checks.
- **`cli/backup.php`:** manifest `schema` block and `manifest_version` 2; logs invitation column readiness at backup time.
- **`cli/restore.php`:** runs pending migrations after SQL import by default (`--no-migrate` to skip); `--skip-safety-backup` for automation; prints schema report vs manifest.
- **`tests/cli/backup_restore_invitation_roundtrip_test.php`:** dev round-trip test (`npm run test:backup-restore`) — backup, restore, assert invitation fields preserved.
- **`tests/e2e/system/backup-restore-ui.spec.ts`:** Playwright — admin UI backup create/download; optional UI backup + CLI restore when `E2E_BACKUP_RESTORE=1` (`npm run test:e2e:backup-restore-ui:full`).


---

## E2E — profile invitation card (2026-05-29)

- **`tests/e2e/profile/profile-invitation-card.spec.ts`:** RSVP field enable/disable matrix, distribution **Others, specify:** visibility, create/view/edit persistence, API store normalization (Attend clears representative/reasons; non-Other clears `invitation_distribution_status_other`).
- **`tests/e2e/support/invitation-card-helpers.ts`:** shared RSVP/distribution constants and form helpers.
- **`tests/e2e/profile/profile-field-visits-crud-history.spec.ts`:** updated for field-visit inputs removed from the invitation card (list delete/restore only).
- **`App/Controllers/Api/ProfileController.php`:** `POST /api/profile/update/{id}` clears legacy `visit_*` and invitation visit-status fields when the request omits all `visit_*` keys (parity with web **Save main** / form without visit inputs).
- **`App/Models/Profile.php`:** fixed `Profile::create()` INSERT placeholder count (64 columns) that blocked profile store during E2E.


---

## Profile invitation card fields (2026-05-29)

- **Schema:** `migration_064_profile_invitation_fields.php` adds RSVP, reasons, visit personnel, distribution status, and related columns on `profiles`.
- **UI:** Invitation card on profile create/edit/view includes CommCare-aligned selects (RSVP, Status of Distribution) plus conditional text fields; **Date Received** removed from the card (2026-05-29). **Others, specify:** (distribution) shows only when Status of Distribution is **Others, specify:** (`invitation-card.js`). After that field the card ends; **Status of First/Second Visit**, **field visits (1st–3rd)**, and profile **Project** on view were removed—**Remarks**, **Field Personnel**, and **Attachments** remain below the card. first/second visit personnel blocks, visit status textareas, and existing 1st/2nd/3rd field visits (`App/Views/profile/partials/invitation_card_form.php`, `invitation_card_view.php`). RSVP-dependent invitation fields (`public/assets/js/profile/invitation-card.js`; server clears on save): **Attend (Dadalo)** disables/clears Name of Representative, both reason fields, and Specify; **Will not attend, but will have a representative attend** enables only Name of Representative; **Did not accept invitation** enables only Reason for not accepting; **Will not attend (Hindi makakadalo)** enables only Reason for not attending; **Has specific needs?** enables only Specify.
- **Persistence:** Web and API store/update via `Profile::resolveInvitationFieldsForSave()` with value-set normalization; audit logs track invitation field changes.
- **API read:** `GET /api/profile/{id}` returns `invitation_*` keys plus `invitation_rsvp_label` and `invitation_distribution_status_label` for display.
- **PDF:** Profile detail PDF includes an Invitation information section.
- **Docs/Postman:** `docs/API_CONTRACT.md`, `docs/postman/PAPeR-API.postman_collection.json`, `docs/DevelopmentHistory/5.28.2026.json`.


---

## Profile representative information (2026-05-29)

- **UI:** PAPS **Last / First / Middle name / Suffix** at the top of the Main tab (profile identity). **Representative information** card (before Household information) has separate **`representative_*`** name fields plus suffix.
- **Schema:** `migration_062_profiles_paps_suffix.php` adds `profiles.suffix` for PAPS when missing (after migration 061 may have removed it).
- **Schema:** `migration_061_profiles_representative_name.php` adds `representative_last_name`, `representative_first_name`, `representative_middle_name`, `representative_suffix`; removes mistaken `profiles.suffix` from migration 060 when present.
- **Persistence:** Web and API store/update representative name fields independently of PAPS `first_name` / `middle_name` / `last_name`.
- **Representative contacts:** `migration_063_profiles_representative_contacts.php` adds `representative_contact_number_1` … `_3` on the Representative information card (form, view, API, PDF).
- **Docs:** `docs/API_CONTRACT.md`, `docs/DevelopmentHistory/5.28.2026.json`.


---

## Profile household/location fields (2026-05-28)

- **Schema:** Added `migration_059_profile_household_location_fields.php` with new `profiles` columns: `total_household`, `household_number`, `structure_number`, `house_number`, `building_number`, `lot_block_number`, `street_name`, `village_subd`, `barangay_text`, `city_municipality`.
- **Web + API persistence:** Profile create/update flows now save these fields; `GET /api/profile/{id}`, `POST /api/profile/store`, and `POST /api/profile/update/{id}` read/write them.
- **Read surfaces:** `/profile/view/{id}` and profile PDF include the new fields in the Main/Profile details block.
- **Docs/Postman:** Updated `docs/API_CONTRACT.md`, `docs/postman/PAPeR-API.postman_collection.json`, and `docs/DevelopmentHistory/5.28.2026.json`.


---

## Central contacts module (2026-05-21)

- **`contacts` table:** Normalized rows (`entity_type` `profile` | `user`, `entity_id`, `person_label`, `number`, `sort_order`). Migration **057** backfills from `profiles.contacts_json`; **058** adds **`view_contacts`** for roles with `view_profiles` or `view_users`.
- **Write path:** Profile create/update/import and User create/update call **`ContactService`** (full replace per owner). Profiles keep denormalized **`contact_number`** / **`contacts_json`** for list search.
- **Read-only module:** **`GET /contacts`** (web, under **Library** in the nav) and **`GET /api/contact/list`** — no create/edit/delete in Contacts; owner name links to **`/profile/edit/{id}`** or **`/users/edit/{id}`**. Deleted PAPS are excluded from the list.
- **User forms:** Repeatable contact rows on user add/edit (account **email** remains separate).

---

## Configurable uploads path (2026-05-20)

- **`config/app.php` → `uploads_path`:** Optional absolute path to the `public/uploads` directory (documented in `config/app-sample.php`). Use when the app `ROOT` and the folder that holds uploaded files differ (e.g. multiple `public_*` deploy trees on one host).
- **`App/UploadPaths` + `UPLOADS_ROOT`:** Structure, profile, and grievance upload/serve handlers resolve filesystem paths via `UploadPaths` (defaults to `ROOT/public/uploads`).

---

## Mobile 2FA developer guide (2026-05-19)

- **`docs/MOBILE_2FA_GUIDE.md`:** New dedicated mobile-dev guide for the email 2FA verify flow. Contains the request/response contracts, ASCII sequence diagram, state machine, full error matrix, retry/resend/lockout rules, secure-storage checklist, Postman/Playwright/curl quickstarts, backwards-compat notes, and ready-to-use TypeScript, Dart (Flutter), Kotlin (OkHttp), and Swift snippets.
- **Doc cross-links:** Linked from `README.md` documentation map, `docs/MOBILE_APP_INTEGRATION.md` §3.4, and `docs/API_AUTH.md` §3.1.

---

## API email 2FA verify flow (2026-05-19)

- **`POST /api/auth/login`:** When `enable_email_2fa` is on, valid credentials now return **`success: true`** with **`data.pending_2fa`** and **`data.challenge_id`** (200) instead of `TWO_FACTOR_REQUIRED` (403). OTP is emailed to the account; account email is exposed as **`data.email_hint`** (masked).
- **`POST /api/auth/2fa/verify`:** New endpoint. Body **`{ challenge_id, code }`** → on success returns same shape as direct login (`data.token`, `expires_at`, `user`). Wrong code → **`TWO_FACTOR_INVALID_CODE`** (401), expired → **`TWO_FACTOR_CHALLENGE_EXPIRED`** (410), too many failed attempts → **`TWO_FACTOR_LOCKED`** (429).
- **`POST /api/auth/2fa/resend`:** New endpoint. Body **`{ challenge_id }`** → rotates the OTP for the same challenge id, resets attempts, re-sends email, extends expiry to `2fa_expiration_minutes`.
- **`App\ApiTwoFactorChallenge` + migration `056_api_2fa_challenges`:** New `api_2fa_challenges` table (challenge_id, sha256 code hash, expires_at, attempts/max_attempts, consumed_at). Default 5 attempts; default expiry from `2fa_expiration_minutes`.
- **New error codes:** `TWO_FACTOR_NO_EMAIL` (403), `TWO_FACTOR_SEND_FAILED` (502), `TWO_FACTOR_INVALID_CODE` (401), `TWO_FACTOR_CHALLENGE_EXPIRED` (410), `TWO_FACTOR_CHALLENGE_NOT_FOUND` (404), `TWO_FACTOR_LOCKED` (429). Legacy `TWO_FACTOR_REQUIRED` (403) now means "server cannot start 2FA for this account" (no email or send failure path).
- **Docs / Postman:** `docs/API_AUTH.md` §3.1, `docs/MOBILE_APP_INTEGRATION.md` §3.4–3.5, `docs/API_ERROR_CODES.md`, `docs/api-error-codes.json`; Postman collection regenerated (`Auth (REST)` folder now has Login + 2FA verify + 2FA resend, with `twofa_challenge_id` env var auto-captured).
- **E2E:** `tests/e2e/smoke/api-2fa-verify-headless.spec.ts` (DB-driven, toggles `enable_email_2fa` via Security Settings, captures OTP straight from `api_2fa_challenges`).

---

## API error codes + standardized envelope (2026-05-18)

- **`App\ApiErrorCode`**, **`App\ApiErrorRegistry`**, **`Core\Controller`:** `apiSuccess()`, `apiError()`, `apiForbidden()`, `apiValidationError()`, `requireCapabilityApi()`, etc. All `/api/*` errors return stable **`error.code`** values.
- **`GET /api/meta/error-codes`:** Public registry for mobile (no auth).
- **`GET /api/auth/me`:** Now includes **`capabilities`** array.
- **`GET /api/grievance/options`:** Lookup rows for grievance create/edit pickers.
- **Refactored:** `App/Controllers/Api/*` controllers use standardized responses; **`cli/refactor_api_errors.php`** (one-off codemod helper).
- **Success envelope:** All `App/Controllers/Api/*` success paths now call **`apiSuccess()`** (no raw `$this->json()`); domain payloads live under **`data`** only. Codemod: **`cli/refactor_api_success.php`**.
- **Docs:** `docs/API_ERROR_CODES.md`, `docs/api-error-codes.json`, Postman meta + grievance options requests.

---

## Mobile app integration guide (2026-05-18)

- **`docs/MOBILE_APP_INTEGRATION.md`:** Onboarding for native/cross-platform clients — Bearer auth, envelope rules, capabilities/project scope, location picker sequence, profile JSON + structure multipart examples, grievance options, limitations (2FA, Library CRUD, attachment uploads).
- **Cross-links:** `README.md` documentation map, `docs/API_CONTRACT.md` intro.

---

## Municipality `findOrCreateByName` — required `code` (2026-05-18)

- **`App\Models\Municipality::findOrCreateByName`:** Inserts with a temporary unique code, then sets **`MUN-AUTO-{id}`** (same pattern as **`Barangay::findOrCreateByMunicipalityIdAndName`**). **`ensureAutoCode`** backfills legacy rows missing `code`.
- **Fixes:** `npm run test:e2e:full:headless:seed` and **`seed_projects_users_field_team.php`** after migration 052 made **`municipalities.code`** NOT NULL.

---

## Playwright E2E — full application headless suite (2026-05-18)

- **`tests/e2e/smoke/full-application-headless.spec.ts`:** Serial headless journey — admin login, main dashboard widgets + **`GET /api/dashboard`**, profile create + list filter, grievance create + list row, structure/users module shells, grievance respondents page shell. If no project has barangay data, the first step bootstraps one via Library (same helpers as grievance full self-contained).
- **`package.json`:** **`npm run test:e2e:full:headless`** (works on empty or existing DB), **`npm run test:e2e:full:headless:seed`** (sets **`E2E_DB_SEED=1`** before run; note: `seed_projects_users_field_team.php` may fail until municipality `code` is set in that seeder).
- **`tests/e2e/smoke/api-all-endpoints-headless.spec.ts`:** Bearer-token sweep of all **`/api/*`** routes (envelope + CRUD smoke). Run: **`npm run test:e2e:api:headless`**; helpers in **`tests/e2e/support/api-client.ts`**.
- **Config:** **`BASE_URL`** (default **`http://eco.local`**), **`ADMIN_USER`** / **`ADMIN_PASS`**, optional **`HEADLESS=false`** to debug with a visible browser.
- **Docs:** `docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md`, `docs/DevelopmentHistory/5.18.2026.json`.

---

## Structure form: Library project / municipality / barangay (Select2) (2026-05-14)

- **`database/migration_055_structures_project_location.php`:** Adds nullable **`structures.project_id`**, **`municipality_id`**, **`barangay_id`** (FKs) for optional location aligned with Library / profile rules.
- **`App/Models/Structure.php`:** Persists location on create/update/`createWithStrid`; **`find()`** returns the three columns; list/search/primary-search/tag resolution use **`COALESCE(s.project_id, p.project_id, pt.project_id)`** so standalone structures scope to the chosen project. **`autoCreateFromProfileIfNeeded`** copies profile **`project_id`** and **`custom_municipality_id`** / **`custom_barangay_id`**. **`update()`** preserves location when **`project_id`** / **`municipality_id`** / **`barangay_id`** keys are omitted (API clients).
- **`App/Controllers/StructureController.php`:** **`validatedStructureLocationFromPost()`** validates against **`Project::municipalitiesForProject`** / **`barangaysForProjectAndMunicipality`** and user project access; web store/update merge location; **`show()`** resolves display names for the tagging tab.
- **`App/Views/structure/form.php`** + **`public/assets/js/structure/form.js`:** Select2 project search (**`/api/projects`**), chained municipality and barangay (**`/api/projects/{id}/municipalities`**, **`/barangays?municipality_id=`**), same pattern as the profile form.
- **`App/Controllers/Api/ApiController.php`:** **`GET /api/projects`**, **`/municipalities`**, **`/barangays`** for a project also allowed when the user has **`view_structure` / `add_structure` / `edit_structure`**.
- **`App/Controllers/Api/StructureController.php`:** Store applies validated location; update merges location only when at least one location field is posted; **`GET /api/structure/{id}`** includes **`project_id`**, **`municipality_id`**, **`barangay_id`**.
- **Docs / Postman:** `docs/API_CONTRACT.md`, `docs/postman/generate-collection.cjs` + regenerated **`PAPeR-API.postman_collection.json`**, `docs/DevelopmentHistory/5.14.2026.json`.

---

## Playwright E2E — grievance options auto-seed + full smoke (2026-05-14)

- **`tests/e2e/support/global-setup.ts`:** On every Playwright run, runs **`php database/seeders/seed_grievance_options.php`** when `E2E_SKIP_GRIEVANCE_OPTIONS_SEED` is not `1`, so grievance web specs no longer hang on empty GRM/language/type/category controls. Full DB reset + seed remains behind **`E2E_DB_SEED=1`** (unchanged flow, without double-running the options seeder unnecessarily).
- **`tests/e2e/support/grievance-form-web.ts`:** Shared helpers (`listProjects`, `pickProject`, `selectProjectOnGrievanceForm`, `checkRequiredGrievanceLookupInputs`, `unwrapApi`) used by dashboard and grievance specs.
- **`tests/e2e/support/grievance-self-contained-helpers.ts`:** `createProjectViaLibrary` matches current **`/library/create`** (name + description only). New **`ensureMunicipalityAndBarangayForProject`** links a Library municipality to the project and adds a master barangay so **`firstLocationForProject`** / profile create APIs resolve locations (replaces removed inline affected-area repeater).
- **`tests/e2e/dashboard/dashboard-main.spec.ts`:** Grievance create helper fills textareas + GRM lookups before names (avoids respondent blur side effects), selects **Male**, uses **`#grievanceForm`** submit with **`waitForURL`**, and **`test.setTimeout(90_000)`** for the status-change scenario; **`selectProjectOnGrievanceForm`** now calls **`jQuery('#projectSelect').val(...).trigger('change')`** when present and asserts **`#projectSelect`** value (Select2 sync flake fix).
- **Docs:** `docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md`, `docs/DevelopmentHistory/5.14.2026.json`, `package.json` (`test:e2e:smoke-full`).
- **Auth / Playwright defaults:** `tests/e2e/support/auth.ts` post-login settle waits on **`main.content`** (authenticated shell) instead of **`a[href='/profile'].nav-link`** (sidebar Profile has no `nav-link`). **`playwright.config.ts`** default test timeout raised from **30s → 45s** so login + navigation waits do not hit the global test timeout on slower XAMPP runs.

---

## Library barangay CRUD + master fields (2026-05-12)

- **`database/migration_054_barangays_code_description.php`:** adds **`barangays.code`** (required, unique) and **`barangays.description`**; backfills existing rows with **`BRGY-`** zero-padded ids.
- **`App\Models\Barangay`:** list/search (**`Barangay::all`**), create/update/delete, **`linkedProjects`**, and **`find`** now include municipality display name; **`findOrCreateByMunicipalityIdAndName`** assigns a reserved **`BRGY-AUTO-{id}`** code for legacy inserts.
- **`BarangayController` + views** under **`/library/barangays`** (index, view, create, edit) with fields **Name**, **Code**, **Municipality** (Select2 against **`GET /api/municipalities`**), and **Description**; external script **`public/assets/js/library/barangays/form.js`**.
- **`App\Controllers\Api\ApiController::municipalities`** and **`GET /api/municipalities`** route for the searchable municipality dropdown.
- **Navigation:** Library **Brgy** menu targets **`/library/barangays`** (top nav + sidebar).
- **Docs / Postman / E2E:** `docs/API_CONTRACT.md`, `docs/postman/generate-collection.cjs` + regenerated collection, `docs/DevelopmentHistory/5.12.2026.json`; Playwright **`tests/e2e/library/barangay-crud.spec.ts`** and **`npm run test:e2e:barangay-crud`**.
- **Municipality detail:** `/library/municipalities/view/{id}` now lists **linked barangays** (master `barangays` rows for that municipality) with links to each barangay view.
- **Profile location vs Library workflow:** When a project has **no** `project_affected_barangays` rows, profile municipality/barangay options and **`Project::validateProfileLocation`** use **`municipality_projects`** (Library → Municipality → link project) and master **`barangays`** instead of requiring areas from Library → Edit project.
- **E2E:** `tests/e2e/profile/profile-location-junction-only.spec.ts` (junction-only project → profile create); 1200ms slowMo: **`npm run test:e2e:profile-location-junction:headless-slow1200`** (headless) or **`npm run test:e2e:profile-location-junction:headed:slow1200`** (headed).

---

## Library navigation submenu update (2026-05-07)

- **`App/Views/layout/main.php`:** Library submenu now shows **Project**, **Municipality**, and **Brgy** in both top navigation and sidebar navigation.
- **Fresh-install truncate fix:** `cli/truncate_fresh_install.php` now also truncates municipality-related tables (`municipalities`, `barangays`, `project_affected_barangays`, `municipality_projects`) so municipality data is fully cleared on reset.
- **Library project form UI change:** Removed municipality/barangay area inputs from `/library/create` and `/library/edit/{id}`; affected areas remain visible on `/library/view/{id}` only.
- **Municipality CRUD:** Added `MunicipalityController` + views (`/library/municipalities`) with **Name**, **Code**, **Description** fields; `Municipality` model now supports list/create/update/delete.
- **Municipality-project persistence:** Projects dropdown now persists via junction table **`municipality_projects`**; list now shows linked project(s), and added municipality detail page `/library/municipalities/view/{id}`.
- **Municipality form UI:** Added searchable **Projects** dropdown field (after **Code**) in `App/Views/library/municipalities/form.php`, powered by `public/assets/js/library/municipalities/form.js` using `/api/projects`.
- **`database/migration_052_municipalities_code_description.php`:** adds `municipalities.code` (required, unique) and `municipalities.description`.
- **`database/migration_053_municipality_projects_link.php`:** adds junction table `municipality_projects (municipality_id, project_id)` for municipality-to-project linkage.
- **Routes:** added `/library/municipalities` CRUD endpoints in `public/index.php`; Library submenu Municipality now links to that module.
- **E2E:** Added `tests/e2e/library/municipality-crud.spec.ts` covering municipality create → view → edit → delete with linked project persistence check; added run script `npm run test:e2e:municipality-crud`.

---

## Barangays master table + profile/project FK ids (2026-05-05)

- **`database/migration_051_barangays_table.php`:** table **`barangays`** (`municipality_id`, `name`, UNIQUE per municipality); **`project_affected_barangays.barangay_id`** (drops varchar **`barangay`**); **`profiles.custom_barangay_id`** (drops **`custom_barangay`** varchar).
- **`App\Models\Barangay`:** `findOrCreateByMunicipalityIdAndName` and lookups; **`Project`**, **`Profile`**, web/API controllers, seeders, Library + profile JS updated for **`custom_barangay_id`** and barangay API objects **`{ id, name }`**.
- **REST / CSV:** Prefer **`custom_barangay_id`**; **`custom_barangay`** name still accepted on profile write when resolvable against the project’s affected areas. **`GET /api/projects/{id}/barangays`** returns **`barangays: [{ id, name }]`** (flat list uses the same shape; legacy TEXT-only projects may surface **`id: 0`** with name only).
- **Docs / Postman / E2E:** `docs/API_CONTRACT.md`, `docs/DEVELOPMENTGUIDE.md`, `docs/postman/PAPeR-API.postman_collection.json`, `docs/DevelopmentHistory/5.5.2026-barangays-table.json`; Playwright helpers/specs updated for barangay **id** in `#barangaySelect`.
- **E2E follow-up:** `profile-field-visits-crud-history.spec.ts` now uses **`picked.barangayId`** for `#barangaySelect` (was still passing a removed **`barangay`** field).
- **E2E (2026-05-05):** **`setProfileCreateFormProject`** — profile create flows set **`#projectSelect`** via **jQuery `.val().trigger('change')`** so Select2 (ajax) matches the native value before POST; **`submitNewProfile`** always fills **date of invitation** and allows **30s** for redirect; **`profile-generate-50`** Library fallback uses **`area_municipality[]` / `area_barangay[]`** (removed textarea); **`users-roles-full`** opens roles list with **`?q=Administrator`** for the locked-administrator assertion.
- **Email provider switch (2026-05-05):** Email settings now include **`email_provider`** (`smtp` or `mailersend`), MailerSend token/sender fields, and provider-aware test mail text. **`Core\Mailer`** routes sends to SMTP or MailerSend API based on app settings; queued notifications continue to use `cli/send_queued_emails.php` unchanged.

---

## Library + profiles — municipality / barangay coverage (2026-05-04)

- **`database/migration_049_project_affected_barangays_normalized.php`:** table **`project_affected_barangays`**; profile municipality column introduced on varchar path; legacy **`projects.affected_barangays`** lines imported as municipality **`Unassigned`**.
- **`database/migration_050_municipalities_table.php`:** table **`municipalities`**; **`project_affected_barangays.municipality_id`**; **`profiles.custom_municipality_id`** (drops free-text **`custom_municipality`**).
- **Library (web):** `App/Views/library/form.php` + **`public/assets/js/library/form.js`** — rows with municipality id + barangay; `LibraryController` calls **`Project::replaceAffectedAreas`** on save; view/PDF list structured areas.
- **Profiles (web):** municipality + barangay Select2; **`public/assets/js/profile/form.js`** loads **`/api/projects/{id}/municipalities`** (`{id,name}`) then **`/barangays?municipality_id=`**; model/controllers/API persist **`custom_municipality_id`**; validation **`Project::validateProfileLocation`** (municipality id + barangay).
- **REST:** **`GET /api/projects/{id}/municipalities`**; **`GET /api/projects/{id}/barangays`** with **`municipality_id`** or legacy **`municipality`** (optional; without query, flat list preserved). **`GET/POST /api/profile/*`** expose and accept **`custom_municipality_id`**; **`custom_municipality`** name remains on read and as optional write alias.
- **Seeders:** demo projects call **`Project::replaceAffectedAreas`** after insert with **multiple named municipalities per project** (e.g. Sta. Cruz / Lumban on Alpha, Calamba / Los Baños on Beta—not only **Unassigned**); profile seeders pick **`municipality_id` + `barangay`** pairs.
- **Docs / Postman / E2E:** `docs/API_CONTRACT.md`, `docs/postman/PAPeR-API.postman_collection.json`, `docs/DevelopmentHistory/5.4.2026-normalized-project-areas.json`, `docs/DevelopmentHistory/5.4.2026-municipalities-table.json`; Playwright specs and **`grievance-self-contained-helpers`** updated for the Library form and location APIs.

---

## Profiles — three field-visit date + remarks sets (2026-05-04)

- **`database/migration_048_profile_three_visits.php`:** `profiles.visit_1_date` … `visit_3_remarks` (after `date_of_invitation`).
- **Web:** `App/Views/profile/form.php` (create/edit Main tab), `view.php`, `App/Views/pdf/profile_detail.php`.
- **Model/API:** `App/Models/Profile.php` (persist + list SELECTs), `ProfileController` + `Api\ProfileController` (payload, audit, JSON shape).
- **Web audit parity:** `ProfileController::delete` records **`deleted`**; **`restore`** records **`restored`** (aligned with API), so Activity History after restore shows the full lifecycle.
- **E2E:** `tests/e2e/profile/profile-field-visits-crud-history.spec.ts` — create/read/update visit fields, soft-delete + restore, assert sidebar **Created / Updated** (including `visit_*` change lines) **/ Deleted / Restored**. Headed with **1200 ms `slowMo`** + longer timeouts: **`npm run test:e2e:profile-field-visits-crud:headed:slow1200`**. Headed with longer timeouts only (no pacing): **`npm run test:e2e:profile-field-visits-crud:headed:slow-run`** (`PW_SLOW_RUN=1`).
- **Export:** optional CSV columns in `App/ListConfig.php`. Docs: `docs/API_CONTRACT.md`, `docs/postman/PAPeR-API.postman_collection.json`, `docs/DEVELOPMENTGUIDE.md`, `docs/DevelopmentHistory/5.4.2026.json`.

---

## Developer mini-site — `dev-help/` (2026-05-04)

- **`dev-help/index.php`** rewritten as a **structured in-page guide** (stack, request flow, HTML vs REST, auth/capabilities, frontend conventions, REST overview, module table, testing, CLI) with **doc-ref asides** linking to Markdown for depth. **`dev-help/assets/style.css`** updated for prose sections and tables; **`dev-help/assets/site.js`** unchanged (theme toggle). **`dev-help/.htaccess`** sets `DirectoryIndex index.php`. Documented in **`docs/DEVELOPMENTGUIDE.md`** and **`README.md`**.

---

## Postman — full module coverage + generator (2026-05-04)

- **`docs/postman/PAPeR-API.postman_collection.json`**: **all REST `/api/*` routes** from `public/index.php` plus **Web (session)** folders (public auth + 2FA, core/account, profile, structure, grievance + options stubs, library, settings, system, users/roles, sessions, static serve). Regenerate: **`node docs/postman/generate-collection.cjs`** or **`npm run postman:collection`** (source **`docs/postman/generate-collection.cjs`**).
- **`docs/postman/PAPeR-Local.postman_environment.json`**: `csrf_token` for optional web POST experiments. **`docs/postman/README.md`**: import, collection id variables, regenerate. **`docs/API_AUTH.md`** §6 updated. Top-level **`postman/`** remains gitignored for private scratch files.

---

## Grievance respondent profiles — toolbar, filters, sort (2026-05-04)

- **`/grievance/respondents`:** Card-style toolbar with search (name / mobile / email / linked PAPS), **Sort by**, **Per page**, **Is PAPS** (any / yes / no / mixed), **Linked PAPS profile**, **Gender**; active-filter badge and totals line; **Export PDF** uses the same query params as the list.
- **`/grievance/respondent`:** Redirects to **`/grievance/respondents`** preserving the query string (singular URL alias).
- **`GrievanceRespondent::listPaginated`:** Optional filter/sort arguments; PDF export respects the same filters.
- **Assets:** `public/assets/css/grievance/respondents-toolbar.css`, `public/assets/js/grievance/respondents.js`; layout supports optional **`$pageHead`** for page-scoped `<link>` tags.
- **Docs:** `docs/API_CONTRACT.md` (web query params), `docs/DEVELOPMENTGUIDE.md`; E2E respondents spec asserts toolbar controls.

---

## E2E — grievance respondents Is PAPS column (2026-05-04)

- Added `tests/e2e/grievance/grievance-respondents-is-paps.spec.ts` and `npm run test:e2e:grievance-respondents-is-paps`: validates `/grievance/respondents` **Is PAPS** header and cell labels (`Yes` / `No` / `Yes, No`) or empty-state.

---

## E2E — grievance dashboard escalation + profile PDF specs (2026-05-04)

- `grievance-dashboard-escalation.spec.ts`: `createGrievance` aligned with non-PAPS create (date recorded, mobile, one-time incident, scroll + `#grievanceForm` submit); **Update Status** uses `scrollIntoViewIfNeeded` + `click({ force: true })` to avoid layout “not stable” timeouts; scenario timeout raised to 120s.
- `profile-export-pdf.spec.ts`: on non-2xx **GET `/profile/pdf`**, fail with status + truncated body and a hint when **503 / “composer install”** (missing **mpdf/mpdf**).

---

## E2E — `createGrievanceWithLibraryIds` non-PAPS parity (2026-05-04)

- `tests/e2e/support/grievance-self-contained-helpers.ts`: grievance create helper now fills **`mobile_number`**, **one-time incident** (`#incidentOne` + `incident_date`), waits for **`#grievanceForm`**, scrolls GRM/library checks and submits via **`#grievanceForm`’s submit button** so store validation matches the non-PAPS `grievance-create` flow. Middle name is left empty so list assertions (`Jose DelaCruzOpen…`, `Ramon DelAL1-…`) stay aligned with respondent link text.

---

## npm E2E scripts — `playwright.cmd` inside PowerShell (2026-05-04)

- Windows **ExecutionPolicy** blocks `node_modules/.bin/playwright.ps1` when npm runs a `powershell -NoProfile -Command "…; playwright test …"` script. All such scripts in `package.json` now call **`playwright.cmd test`** (and the users slow script no longer uses `npx.cmd playwright`).

---

## Profile seeders — audit_log for main dashboard (2026-05-04)

- Main dashboard profile metrics (`GET /api/dashboard` → `data.profile.created` / `updated`) count **`audit_log`** rows, not raw `profiles` rows.
- Added `AuditLog::recordWithCreatedBy()` for CLI contexts without a session; `database/seeders/seed_profiles.php` and demo-profile inserts in `database/seeders/seed_projects_users_field_team.php` now record `entity_type=profile`, `action=created` (with `changes.source`) after each `Profile::createWithPapsid`, attributed to the `admin` user when present.
- Documented in `docs/API_CONTRACT.md`.

---

## E2E — grievance full self-contained (no PHP project/library seed) (2026-05-04)

- Added `tests/e2e/support/grievance-self-contained-helpers.ts` and `tests/e2e/grievance/grievance-full-self-contained.spec.ts`: admin creates two projects via Library, **two profiles via `/profile/create`** (no PHP profile seeder), asserts main **`/api/dashboard`** profile `created` count increases, opens **`/`**, then creates grievance option rows (GRM, language, type, category), default progress stages with SLA, and exercises grievance dashboard, list filters, simulated-date escalation (API + UI), progress change, and Activity/Status history assertions.
- Spec uses **`test.use({ headless: Boolean(process.env.CI) })`** so local runs are **headed** by default for watchability.
- `submitNewProfile` in `profile-structure-helpers.ts` accepts optional **`dateOfInvitation`** (YYYY-MM-DD).
- Spec skips when `E2E_DB_SEED` is set so it never runs together with PHP global seed. Added `npm run test:e2e:grievance-full-self-contained` (clears `E2E_DB_SEED` in the script, single worker) and `npm run test:e2e:grievance-full-self-contained:headed:slow1200` (`PW_SLOW_MO_MS=1200` + `--headed`).
- Documented in `docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md`.

---

## E2E — headed login + three users + re-login verify (2026-05-04)

- Added `tests/e2e/users/login-create-three-users-verify.spec.ts`: admin logs in, creates three users, clears session, logs in again, confirms all three appear from `/users` search, then deletes them.
- Spec uses `test.use({ headless: Boolean(process.env.CI) })` so **local runs show a real browser** even when you only run `npx playwright test tests/e2e/users/login-create-three-users-verify.spec.ts` (CI remains headless).
- Added `npm run test:e2e:users-login-three:headed` (headed, no seed) and `npm run test:e2e:users-login-three:headed:slow1200` (seed + slowMo + headed). The slow script uses `npx.cmd` inside PowerShell so strict **ExecutionPolicy** (blocked `npx.ps1`) does not break the run.
- Documented the spec and commands in `docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md`.

---

## E2E stability — PAPSID lock + grievance list filter timeout (2026-05-04)

- **`Profile::create`:** `GET_LOCK('papsid_generate')` now wraps both PAPSID allocation and the `INSERT`, fixing duplicate `uk_papsid` under parallel Playwright workers (previously the lock was released before insert). Added private `computeNextPapsid()`; `generatePAPSID()` still uses lock + compute for standalone callers.
- **`grievance-list-filters.spec.ts`:** `test.describe.configure({ timeout: 120_000 })` for the serial grievance list suite so the full filter + simulated-date flow can finish under load.

---

## Local dev URL alignment — Playwright + docs (2026-05-04)

- Default Playwright `baseURL` (when `BASE_URL` is unset) is now `http://eco.local` instead of `http://ecosys-it` (`playwright.config.ts`). CI and other hosts should set `BASE_URL` explicitly.
- `.env.playwright.example`, `docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md`, and `.cursor/commands/paper-e2e-last.md` examples updated to match.
- `config/app.php` (gitignored local file): CORS allowlist extended with `http://eco.local` and `http://eco.local:4200` for SPA dev against the same vhost.

---

## Structure view — clickable GPS coordinates (2026-05-01)

- **`/structure/view/{id}`** now renders **GPS latitude** and **GPS longitude** as clickable links to Google Maps (opens in a new tab) when both coordinates are present.


---

## Structure tagging status — add Exceeded number of Visits (2026-05-01)

- Added new Structure tagging status option: **Exceeded number of Visits** (`exceeded_number_of_visits`) in `App\Models\Structure::taggingStatusOptions()`. This is now available in Structure create/edit and is rendered consistently in list/view/export labels via existing status label helpers.


---

## List PDF — `per_page` and row cap (2026-05-01)

- **`GET /profile/pdf`** and **`GET /structure/pdf`** now read **`per_page`** from the query string (clamped **10–500**, default **250**), matching the list toolbar PDF link. Previously each action always fetched up to **2500** rows, which could exhaust PHP memory during mPDF rendering on large databases.
- **E2E:** `profile-age-minimum.spec.ts` targets **`.alert-danger:not(.d-none)`** (avoids strict-mode conflict with a hidden preview error). **`profile-soft-delete.spec.ts`** expects the list **Name** column format **Last, First** (`Delete{uniq}, PW`). **`profile-export-pdf.spec.ts`** asserts **`%PDF-`** and **`application/pdf`** after **`per_page=15`**. **`profile-edit.spec.ts`** and **`profile-ownership-fields.spec.ts`** no longer require **`resp.ok()`** inside **`waitForResponse`** (non-2xx responses were ignored until timeout). **`profile-ownership-fields`** “Save main on edit” ensures contact number and **`#barangaySelect`** (via **`selectOption`**) so **`validateProfileFormForSubmit()`** passes when Select2 lags the native `<select>`.


---

## Structure — API store: merge tagging visibility (2026-05-01)

- **`StructureController::mergeStructureTaggingFromRequest`** is now **`protected`** (was `private`) so **`Api\StructureController`** can call it. A **`private`** parent method caused a PHP fatal on **`POST /api/structure/store`** / **`update`** from the profile Structure tab modal (HTML error body instead of JSON).


---

## Structure — edit form Back to view (2026-05-01)

- **`/structure/edit/{id}`:** The **Back** button now goes to **`/structure/view/{id}`** instead of the structure list. **Add Structure** still uses **Back** → `/structure`.

