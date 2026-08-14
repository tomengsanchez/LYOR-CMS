# PAPeR – Changes (2026-06)

Part of the [changes index](../CHANGES.md). Newest entries first within this month.

---

## Grievance — closed by last stage (2026-06-30)

- **Database:** `grievances.closed_at` and `grievances.closed_at_progress_level` (migration **078**); backfilled from `grievance_status_log`. Set on close, cleared on reopen.
- **Dashboard widget `closed_by_stage`:** Counts closed tickets grouped by last In Progress stage before closure. Date filter uses **closure effective date** (other widgets still use date recorded).
- **Grievance view / PDF / API:** `closure_summary` (and `closed_at` / `closed_at_progress_level` on grievance payload) shows closed-on date and last stage; *Closed from Open* when no stage.
- **`GET /api/grievance/dashboard`:** New keys `closedByStage`, `closedInRangeTotal`.
- **Help:** Grievance dashboard and detail help updated.
- **E2E:** `tests/e2e/grievance/grievance-closed-by-stage.spec.ts` — `npm run test:e2e:grievance-closed-by-stage:fast`


---

## API — grievance status history for mobile (2026-06-18)

- **`GET /api/grievance/status-log/{id}`:** Returns status history (`grievance_status_log`) — notes, effective dates, progress level labels, attachment URLs (same data as web Status History sidebar).
- **`POST /api/grievance/status-update/{id}`:** Dedicated status change endpoint (mirrors web `POST /grievance/status-update/{id}`). Always writes a status log row, including **note-only** updates on the same status/stage. Accepts JSON or multipart (`status_note` or `note`, `status`, `progress_level`, `status_effective_at`, `status_attachments[]`).
- **`POST /api/grievance/update/{id}`:** Also records status history when `status_note` / attachments are sent without a status change, or when status/level changes (includes note and attachments).


---

## API — project-scoped progress levels for mobile (2026-06-18)

- **`GET /api/grievance/options`:** `progress_levels` now uses the same logic as web (`forProjectOrDefault`): project-specific stages when initialized, otherwise global defaults only — no longer merges defaults + project levels.
- **`progress_levels_scope`:** New field (`project` | `default`) indicates which set was returned.
- **`POST /api/grievance/store`** and **`POST /api/grievance/update/{id}`:** Rejects invalid `progress_level` for the grievance project (same validation as web status update).


---

## API — respondent type categories for mobile (2026-06-18)

- **`GET /api/grievance/options`:** `respondent_types` rows now include `type`, `type_specify`, and `guide` (same categories as web: Directly Affected, Indirectly Affected, Others).
- **`respondent_type_categories`:** New grouped payload so mobile apps can show category-first pickers; `others` sets `allow_other_specify: true` (use with `respondent_type_other_specify` on save).


---

## Grievance CSV import (2026-06-18)

- **Grievance list** — **Import CSV** button (`add_grievance`) opens a two-step modal: **Preview** validates the file and shows a summary (ready / skipped existing case # / failed), then **Import ready rows** inserts new grievances.
- **`App\GrievanceCsvImporter`** — Resolves project, municipality, barangay, and grievance options by ID or name; skips rows whose `grievance_case_number` already exists (manual update via Edit).
- **Routes:** `POST /grievance/import/preview`, `POST /grievance/import`.
- **Assets:** `public/assets/js/grievance/import.js`.
- **Sample template:** `docs/samples/grievance-import-sample.csv`; download via `GET /grievance/import/sample` or `/public/samples/grievance-import-sample.csv`.
- **E2E:** `tests/e2e/grievance/grievance-import.spec.ts` — `npm run test:e2e:grievance-import:fast` (GI-01..GI-04: sample download, preview validation, import, skip existing case #).
- **Fix:** Import modal refreshes `csrf_token` after preview so the follow-up import POST succeeds (CSRF rotates per validated request).

---

## Grievance controller refactor (2026-06-18)

- **`GrievanceController`** (write actions: create, store, edit, update, delete, restore, status) now extends **`GrievanceBrowseController`** → **`GrievanceBaseController`**.
- **`GrievanceBrowseController`** — dashboard, calendar, list, respondents, export/PDF, view, attachment serve routes.
- **`GrievanceBaseController`** — shared constants, auth constructor, validation, attachment upload, escalation helpers, and `attachmentUrl()`.
- **Routes unchanged** — still `GrievanceController@…` in `public/index.php`.

---

## Grievance registration — category optional (2026-06-18)

- **Category of Grievance** is no longer required on create/edit (web, API, and client-side form validation). GRM Channel, Preferred Language, and Type of Grievance remain required when options exist.

---

## Grievance POST-action GET redirects (2026-06-18)

- **Routes:** `GET /grievance/status-update/{id}`, `GET /grievance/update/{id}`, and `GET /grievance/status-log-effective-at/{grievanceId}/{logId}` redirect to the grievance view or edit page instead of **404** when users refresh or bookmark POST-only URLs after saving.
- **UX:** Grievance view clarifies **Date Recorded** (edit form) vs status **Effective date**; help topic updated.
- **Test:** `tests/e2e/grievance/grievance-post-action-redirect.spec.ts`, `npm run test:e2e:grievance-post-action-redirect:fast`.

---

## Concurrency, saves, and API idempotency (2026-06-17)

- **DB transactions** — Grievance create and status changes run status row + status log + audit in one transaction (`App\GrievanceStatusChange`, `App\DbTransaction`).
- **Optimistic locking** — Edit forms for grievance, profile, structure, and library project send hidden `record_updated_at`; API clients may send `expected_updated_at`. Stale saves return a friendly web message or **`409 CONFLICT`** (`CONFLICT` error code).
- **API idempotency** — Optional **`Idempotency-Key`** / **`X-Idempotency-Key`** on grievance/profile/structure store & update replays the first response for 24h (`migration_077_api_idempotency_keys.php`, `App\ApiIdempotency`).
- **UX** — `data-guard-submit` disables submit while saving; **`App\Flash`** shows success banners after redirect (PRG).
- **Assets:** `public/assets/js/shared/form-submit-guard.js`; profile AJAX save refreshes `record_updated_at` from API `updated_at`.
- **Tests:** `php tests/cli/concurrency_optimistic_idempotency_test.php`; Playwright **`npm run test:e2e:concurrency:fast`** (CS-01..CS-05: flash, submit guard, stale lock, API idempotency, 409).
- **Fixes from E2E:** `use App\OptimisticLock` in models; `Project::find()` includes `updated_at`; `form-submit-guard.js` binds when script loads after DOM ready.

---

## Library — project activity history (2026-06-17)

- **Library → Edit Project** — Activity History sidebar (same pattern as profile/structure/grievance view); infinite scroll via `GET /api/history?entity_type=project&entity_id=…`.
- **`AuditLog`** records project `created`, `updated`, `deleted`, and `restored` (field diffs for name, description, escalation day count, affected areas).
- **Audit Trail** filter includes **Projects** module.

---

## Per-project escalation day-count start (2026-06-17)

- **`migration_076_project_escalation_count_start.php`** — `projects.escalation_count_start` ENUM (`effective_date` \| `next_day`), default **`next_day`** for existing rows.
- **`App\GrievanceEscalation`** — `daysOpen()`, `deadlineDate()`, `isOverdue()`, and overdue filters respect per-project start rule; global weekend/holiday settings unchanged.
- **Library → Edit Project** — radio: **Next day** (default) vs **Effective date**; shown on project view.
- **Help:** Library topic documents the setting; grievance list/calendar/dashboard use the project rule automatically.

---

## Grievance Calendar — SLA deadlines (2026-06-17)

- Calendar month grid now shows **deadline** counts (escalate / close) alongside **recorded** counts in one view.
- **`GrievanceEscalation::deadlineDate()`** — computes last day before overdue (respects weekends/holidays).
- Day panel **Overdue** section lists past-due cases only (no “X days left”).

---

## Grievance Calendar (2026-06-17)

- **`/grievance/calendar`** — Month grid of grievances by date recorded; daily counts by status; click a day for ticket list.
- **`GET /api/grievance/calendar`** — Month payload (`month`, `summary`, `days`).
- **`GET /api/grievance/calendar/day`** — Tickets for one date.
- **Nav:** Grievance → Calendar (after Dashboard).
- **Assets:** `public/assets/js/grievance/calendar.js`, `public/assets/css/grievance/calendar.css`.

---

## Playwright — notification system E2E (2026-06-17)

- **`tests/e2e/notifications/notifications.spec.ts`** — Serial Playwright suite for the notification bell: event triggers (profile/grievance/structure, web + API), user preferences, project linkage, bell UI, history filters, and click security.
- **`tests/e2e/support/notifications.ts`** — Helpers: test users with linked projects, `/api/notifications` polling, DB read helper (`db-query.php`).
- **Run:** `npm run test:e2e:notifications:fast` (headless). `BASE_URL` env configurable (default `http://eco.local`).

---

## Operational — Holidays CRUD (2026-06-17)

- **`migration_075_holidays.php`** — `holidays` table: `name`, `description`, `holiday_date`.
- **`App\Models\Holiday`** — list/create/update/delete with validation.
- **`/system/operational`** — Holidays section with modal add/edit and delete; requires `manage_operational_settings` to change data.
- **`GET /api/system/operational`** — response now includes `holidays` array.
- **Help:** System → Operational topic in Help.

---

## Grievance Settings — escalation calendar (2026-06-17)

- **`/grievance/settings`** — checkboxes: exclude weekends / exclude holidays from escalation due counting.
- **`App\GrievanceEscalation`** — shared day-count logic for list badges, `needs_escalation` filter, and dashboard stats.
- **`GET /api/grievance/options`** — includes `escalation_settings`.
- **Help:** Grievance Settings topic in Help.
- **Tests:** `npm run test:e2e:grievance-settings-escalation:fast` (GS-01..GS-06); CLI `php tests/cli/grievance_escalation_business_days_test.php`.

---

## Backup / restore — generated columns (2026-06-16)

- **`cli/backup_sql_helper.php`:** Shared helpers to detect STORED generated columns and strip them from SQL dumps.
- **`cli/backup.php`:** `mysqldump` uses `--skip-generated-columns` when supported; PDO exporter omits generated columns from `INSERT` statements (fixes restore error 3105 after migration 069).
- **`cli/restore.php`:** Sanitizes legacy `database.sql` before import by removing generated column values from `INSERT` lines (including mysqldump multi-line `INSERT … VALUES` blocks).
- **`tests/cli/backup_sql_generated_columns_test.php`:** Unit tests for sanitizer and parser.


---

## Grievance attendant (GRM) (2026-06-16)

- **`migration_073_grievance_attendant.php`:** Adds optional **`attendant_id`** on **`grievances`** (FK → **`users`**, ON DELETE SET NULL).
- **Grievance Registration card:** **Attendant** Select2 — users with at least one **`user_projects`** row; starts blank; type to search via **`GET /api/users/linked-projects`** (optional **`project_id`** filter).
- **Validation:** Optional field; when set, user must exist and be linked to projects; if grievance has a project, attendant must be linked to that project.
- **API / export / PDF / view:** **`attendant_id`** and **`attendant_name`** on grievance read, create, and update payloads.


---

## Users — optional password (reference-only accounts) (2026-06-16)

- **`migration_074_users_optional_password.php`:** `users.password_hash` may be NULL for people needed in data (attendant, field personnel, project links) who are not login users yet.
- **User create/edit:** Password is optional on create; blank password = **Reference only** (cannot sign in). Set a password later to enable login.
- **Auth:** Web and API login reject accounts without a password hash.
- **Users list / view / PDF:** **Login** column shows *Can sign in* vs *Reference only*.


---

## Help — page-specific form guides (2026-06-16)

- **`App/Views/help/pages/`** — Screen-level help partials (e.g. `grievance-create`, `grievance-edit`, `profile-create`, `structure-create`) with required vs optional fields, how to fill the form, and related modules.
- **`$helpPage` in views** — Passes the exact screen to Help (`?from=grievance-create` on `/grievance/create`) while `$currentPage` keeps nav highlighting.
- **`help_resolve_content_key()`** — Picks a page partial when it exists, otherwise falls back to module-level help.


---

## Help — AJAX modal (2026-06-16)

- **`GET /help/fragment?from=`** — Returns help body HTML only (no layout) for the in-app modal.
- **`App/Views/help/content.php`** + **`helpers.php`** — Shared help partial used by full page and fragment.
- **`public/assets/js/layout/help-modal.js`** — Intercepts all `/help` links site-wide; opens Bootstrap **`modal-xl`** scrollable dialog; cross-links load inside the modal. **Open full page** footer link still navigates to `/help`.


---

## Help — Profiles and Grievance features (2026-06-16)

- **`App/Views/help/index.php`:** Expanded contextual help for **Profiles** (location dropdowns, form sections, tabs, import/export, Library linkage) and **Grievance** (registration cards, PAPS vs non-PAPS, attendant, municipality/barangay, status effective date, date recorded validation, escalation).


---

## Grievance date recorded vs status history (2026-06-16)

- **`Grievance::validateDateRecordedAgainstStatusLog()`:** On grievance update (web + API), **`date_recorded`** cannot be later than the earliest status log effective date (keeps timeline consistent with status effective-date rules).
- **Test:** `tests/cli/grievance_date_recorded_status_log_test.php`, `npm run test:e2e:date-recorded-status-log:fast`.


---

## Library municipality/barangay code per project (2026-06-16)

- **`migration_072_municipality_barangay_code_per_project.php`:** Drops global `UNIQUE` on `municipalities.code` and `barangays.code`.
- **`Municipality` / `Barangay` models:** Name and code (municipality) or code (barangay) must be unique **within each linked project** via `municipality_projects` — Project 1 and Project 2 may both use code `001`.
- **Library municipality form:** Project link is required; help text updated.


---

## Grievance municipality and barangay (2026-06-16)

- **`migration_071_grievance_municipality_barangay.php`:** Adds `municipality_id` and `barangay_id` on `grievances` (FK → Library municipalities/barangays).
- **Grievance form (Respondent's Profile):** Required **Municipality** and **Barangay** after **Project**. **PAPS:** read-only, autofilled from profile `custom_municipality_id` / `custom_barangay_id`. **Non-PAPS:** project-scoped dropdowns (`/api/projects/{id}/municipalities` + `/barangays`); `latest-details` autofill from last grievance.
- **Profile + grievance location Select2:** Municipality selection now uses real `<option>` elements, `select2:select`, and enable-before-init so `/barangays` loads after picking a municipality.
- **Municipality after project pick:** Starts blank (placeholder option) even when the project has only one municipality, so the user must select municipality and `change` loads barangays.
- **Grievance create location:** Project Select2 uses `select2:select` + `change` to load municipalities; location fields enable when real options exist (not only when option count &gt; 1).
- **`Project::barangaysForProjectAndMunicipality()`:** When a municipality is linked to a project but has no `project_affected_barangays` rows, falls back to Library master barangays under that municipality.
- **API:** `POST /api/grievance/store` and `update` accept `municipality_id` / `barangay_id`; responses include `municipality_name` / `barangay_name`. `GET /api/respondents/latest-details` returns location fields.
- **Export / PDF / view:** Municipality and barangay shown on grievance detail and CSV export columns.


---

## Grievance status effective date (2026-06-15)

- **`grievance_status_log.effective_at`:** When a status/stage actually occurred (paper workflow / late encoding). Escalation uses this only on **status or level changes**; note-only updates record `effective_at` for history without moving the SLA clock.
- **Status update form:** Optional **Effective date** when status or level changes; defaults to now. Validated: not before `date_recorded`, not in the future.
- **Admin Phase 2:** Edit effective date on past status history entries (web + API); recomputes `current_level_started_at`.
- **`migration_070_grievance_status_log_effective_at.php`:** Column + backfill `effective_at = created_at`.
- **Routes:** `POST /grievance/status-log-effective-at/{grievanceId}/{logId}`, `POST /api/grievance/status-log-effective-at/{grievanceId}/{logId}` (admin).
- **Tests:** `tests/cli/grievance_status_effective_at_test.php`, `tests/e2e/grievance/grievance-effective-date.spec.ts` (`npm run test:e2e:effective-date:fast`).


---

## Grievance dashboard printable report (2026-06-15)

- **`GET /grievance/dashboard/report`:** Print-friendly HTML using current dashboard filters (project, date range) and selected report sections; browser Print from preview toolbar.
- **`GET /grievance/dashboard/pdf`:** Table-based PDF via `PdfExport` + `GrievanceDashboardReportHtml` (requires `export_grievance`).
- **`POST /grievance/dashboard-report-config`:** Saves per-user `report_widgets` and `report_options.recent_limit` in `user_dashboard_config`.
- **`App/DashboardConfig.php`:** `reportWidgets()`, `reportOptions()`, `reportWidgetsFromRequest()`, `recentLimitFromRequest()`; defaults include analytics sections.
- **`App/GrievanceDashboardReportHtml.php`:** Renders grouped Summary / Analytics / Operations sections as tables for print and PDF.
- **`App/GrievanceDashboardStats::buildPayload($recentLimit, $reportMode)`:** Configurable recent rows; higher project limit in report mode.
- **`App/Views/grievance/dashboard.php`:** **Export report** modal — section checkboxes, recent row limit, “Use current dashboard widgets”, Save / Print preview / Download PDF.
- **`public/assets/js/grievance/dashboard-report.js`:** Builds query string from filters + modal selection.


---

## Grievance dashboard per-card display modes (2026-06-15)

- **`tests/e2e/grievance/grievance-dashboard-by-category.spec.ts`:** By Category widget — API counts, list/table/bar displays via customize dashboard.
- **`tests/e2e/support/grievance-dashboard.ts`:** Shared dashboard API + config save helpers.

- **`App/DashboardConfig.php`:** Unified widget keys (merged legacy `chart_*` + list duplicates into single cards). Per-widget `widget_display` config: doughnut/bar/line chart, list, or table. Legacy saved layouts auto-migrate. `GRIEVANCE_WIDGET_GROUPS` for Summary / Analytics / Operations.
- **`App/Views/grievance/dashboard.php`:** Cards rendered in grouped sections (**Summary**, **Analytics**, **Operations**) matching the customize modal.
- **Customize dashboard modal:** Each card has a **Display as** dropdown (chart / list / table). Bar/line/doughnut with 8+ items auto-switch to table in the browser.
- **`App/GrievanceDashboardFilter` / `GrievanceDashboardStats`:** Dashboard date filters and monthly trend use `COALESCE(date_recorded, created_at)` so grievances without a recorded date still appear in **Grievances over time** and date-scoped widgets.
- **`public/assets/js/grievance/dashboard-designer.js`:** Enables/disables display selects when a widget is unchecked.


---

## Grievance dashboard escalation widget (2026-06-15)

- **`GrievanceController::dashboard`:** Progress levels and `lastProgressLevelId` are scoped to the selected project filter (`forProjectOrDefault`); when viewing all projects, `lastProgressLevelId` is `null` (no misleading global last stage).
- **`App/Views/grievance/dashboard.php`:** New **Needs Escalation / Close** widget (`needs_escalation`) with link to `/grievance/list?needs_escalation=1`.
- **`public/assets/js/grievance/dashboard.js`:** Renders `needsEscalationRows` from `/api/grievance/dashboard` with escalate vs close badges; treats missing rows as empty; surfaces fetch errors on the widget. Renders `chartInProgress` and `chartByProject` bar charts (were blank placeholders).
- **`App/DashboardConfig.php`:** `needs_escalation` added to default grievance dashboard widgets.
- **`ApiController::respondentHistory`:** Fixed broken `GrievanceJsonSql::memberOf()` interpolation in SQL (was emitted as literal text, causing fatal syntax error).
- **`tests/e2e/grievance/grievance-escalation-multi-project.spec.ts`:** Asserts dashboard API `needsEscalationRows[].action` (`escalate` for project A overdue at Level 1, `close` for project B overdue at final stage).


---

## Grievance escalation E2E scenario (2026-06-15)

- **`tests/e2e/grievance/grievance-escalation-scenario.spec.ts`:** Playwright serial spec covering the full escalation lifecycle (Level 1→2→3, note-only updates, overdue badges, `needs_escalation=1` filter, close clears escalation) using Development simulated date.
- **`tests/e2e/grievance/grievance-escalation-multi-project.spec.ts`:** Mixed-project list asserts per-project “Should be escalated to …” targets (custom stage name on project A vs defaults on project B).
- **`GrievanceController::index`:** Escalation badges on `/grievance/list` build a progress-level map per grievance `project_id` (cached), fixing wrong next-stage messages when viewing all projects.
- **`tests/e2e/support/escalation.ts`:** Shared helpers for SLA setup, simulated date, status updates, and badge/filter assertions.
- **npm:** `test:e2e:escalation-scenario` (headed) and `test:e2e:escalation-scenario:fast` (headless); `test:e2e:escalation-multi-project:fast`.


---

## UTF-8 BOM parse error fix (2026-06-08)

- Removed a UTF-8 BOM from `App/Controllers/Api/GrievanceController.php` that caused a fatal PHP namespace parse error.


---

## Grievance status history shows attachments (2026-06-08)

- **Status History** sidebar: files uploaded with a status change appear as links under that entry (served via `/serve/grievance`).
- **`GrievanceStatusLog::attachmentLinksForEntry`** builds secure serve URLs from stored attachment paths.
- PDF status history table includes an **Attachments** column (file names).


---

## Grievance status history shows progress level (2026-06-08)

- **Status History** sidebar (grievance view): in-progress entries show stage name, e.g. `In progress — Reception`.
- **`GrievanceStatusLog::byGrievance`** joins `grievance_progress_levels` for `progress_level_name`; PDF export includes a Level column.


---

## Grievance escalation badges — days left / days overdue (2026-06-08)

- **`GrievanceController::applyEscalationDisplay()`:** in-progress stages with `days_to_address` show yellow **`X days left`** (from day one, including new cases); when exceeded, red **`X days overdue — Should be escalated to …`** or **`… Should be closed`**.
- **List / view / PDF:** `escalation_variant` (`warning` | `danger`) drives badge and alert styling.
- **`needs_escalation=1` filter** unchanged — overdue only.

