# Automated Functional Test Design

This document defines a practical automated functional testing strategy for PAPeR, with `/profile` as the first implementation target.

**Broader test map / pyramid:** see [TEST_STRATEGY.md](TEST_STRATEGY.md).  
**History testing prerequisites** (Activity / Status / SES / notifications): [TESTING_HISTORY_PREREQUISITES.md](TESTING_HISTORY_PREREQUISITES.md).  
**Log E2E / gate results:** [test-results/README.md](test-results/README.md).

> **Next automation target:** After `/profile`, the team will prioritize automated functional coverage for the **`/grievance` module** (list filters, respondent flows, status transitions/escalation paths, soft-delete visibility/actions, and permission enforcement).
> **Fixture policy:** Use Philippine-context test data across all E2E suites (especially profile/structure/grievance): Filipino names, local mobile formats, and barangay/LGU-style narratives.

## Quick Start (implemented scaffold)

The repository now includes a starter Playwright setup:

- `playwright.config.ts`
- `tests/e2e/support/global-setup.ts`
- `tests/e2e/support/auth.ts` — admin login waits for navigation off `/login`, then for **`main.content`** (authenticated layout shell from `layout/main.php`) so post-login steps do not depend on a specific Profile link variant (sidebar vs top nav).
- `tests/e2e/profile/profile-columns.spec.ts`
- `.env.playwright.example` (versioned template)
- `.env.playwright` (local only — gitignored; copy from the example)
- npm scripts in `package.json`

Run locally:

1. Install Node dependencies:
   - `npm install`
2. Install browser binaries (first run only):
   - `npx playwright install`
3. Set environment variables (PowerShell example), or copy `.env.playwright.example` → `.env.playwright`:
   - `$env:BASE_URL=\"http://eco.local\"`
   - `$env:ADMIN_USER=\"admin\"`
   - `$env:ADMIN_PASS=\"admin123\"`
   - See `.env.playwright.example`
4. **Full system E2E (serial, all major modules):**
   - Headless: `npm run test:e2e:full`
   - Headed slow 1200ms: `npm run test:e2e:full:headed:slow1200`
5. **API smoke:** `npm run test:e2e:api`
6. **Everything:** `npm run test:e2e:all` or `npm run test:e2e:all:headed:slow1200`
7. Optional DB reset + seed: `npm run test:e2e:seed` or `npm run test:e2e:seed:headed:slow1200`

**Living suites (spec file present):** prefer the `:fast` / headed scripts whose paths resolve under `tests/e2e/` — e.g. SES (`test:e2e:socio-economic:fast`), RAP (`test:e2e:rap-mapping:fast`), grievance import, notifications, concurrency, activity-history-timezone, nested-system-menu, responsive, security-hardening, `full-system` / `api-smoke`.

### Known gaps (spec missing; npm runners removed)

Orphan `package.json` scripts that pointed at absent specs were **removed** (escalation badges/scenario/multi-project, dashboard-by-category, effective-date, backup-restore-ui). Design notes below stay for when specs are restored — re-add matching npm scripts then.

| Former npm script | Expected spec (still absent) |
|-------------------|------------------------------|
| `test:e2e:escalation` / `:fast` / `…:headed:slow1200` | `grievance/grievance-escalation-badges.spec.ts` |
| `test:e2e:escalation-scenario` / `:fast` | `grievance/grievance-escalation-scenario.spec.ts` |
| `test:e2e:escalation-multi-project:fast` | `grievance/grievance-escalation-multi-project.spec.ts` |
| `test:e2e:grievance-dashboard-by-category:fast` | `grievance/grievance-dashboard-by-category.spec.ts` |
| `test:e2e:effective-date:fast` | `grievance/grievance-effective-date.spec.ts` |
| `test:e2e:backup-restore-ui` / `:full` | `system/backup-restore-ui.spec.ts` |

Also never restored: `test:e2e:grievance-respondents-is-paps`, `test:e2e:grievance-activity-history:*` (use `test:e2e:activity-history-timezone:fast` for timezone/history assertions).

Legacy per-module spec paths below are **removed**; extend `full-system.spec.ts` or add new specs under `tests/e2e/` as needed.

<details>
<summary>Legacy npm scripts (removed)</summary>

Previously documented runners such as `test:e2e:profile-regression`, `test:e2e:grievance-create:headed:slow`, etc. referred to deleted spec files. Use the commands above instead.

</details>

### Former quick start (profile smoke — superseded)

Historical runners below (including `test:e2e:grievance-activity-history:headed:slow`) are **not** in current `package.json`. Use Quick Start + Known gaps above.

### Grievance CSV import (Playwright)

- Spec: `tests/e2e/grievance/grievance-import.spec.ts`
- Helpers: `tests/e2e/support/grievance-import.ts` (build CSV, open modal, preview/import assertions)
- Scenarios: **GI-01** sample download (`GET /grievance/import/sample`); **GI-02** preview flags unknown project; **GI-03** preview + import new row; **GI-04** preview skips existing case number
- Stable selectors: `#grievanceImportModal`, `#grievance-import-preview`, `#grievance-import-submit`, `#grievance-import-summary`
- Run: `npm run test:e2e:grievance-import:fast` (uses `BASE_URL`, default `http://eco.local`)

### Socio Economic (SES) ZIP import (Playwright)

- Spec: `tests/e2e/socio-economic-ses.spec.ts`
- Helpers: `tests/e2e/support/ses-import.ts` (flat/nested ZIP via Compress-Archive, preview/import helpers)
- Scenarios: **SES-01** System page + empty-fields preference; **SES-02** nested ZIP rejected; **SES-03** create profile + import + unchanged re-preview; **SES-04** changed field previous vs current modal; **SES-05** API `previous_value` on changed fields
- Run: `npm run test:e2e:socio-economic:fast` (uses `BASE_URL`, default `http://eco.local`)

### Grievance escalation full scenario (Playwright)

- **Status: gap** — spec `grievance-escalation-scenario.spec.ts` absent; npm runners removed.
- Design intent (when restored): days left, note-only (no clock reset), overdue + filter, level reset, final-level “Should be closed”, close clears escalation; helpers historically in `tests/e2e/support/escalation.ts`.
- Related living suite: `tests/e2e/grievance/grievance-settings-escalation.spec.ts` (`npm run test:e2e:grievance-settings-escalation:fast`).

### Grievance dashboard by category (Playwright)

- **Status: gap** — `grievance-dashboard-by-category.spec.ts` absent; npm runner removed.
- Design intent: empty project API → mixed-category grievances → API counts → list / table / bar UI per `widget_display.by_category`.

### Grievance dashboard report (manual / future E2E)

- Print: `GET /grievance/dashboard/report` with `project_id`, `date_from`, `date_to`, `report_widgets[]`, `recent_limit`
- PDF: `GET /grievance/dashboard/pdf` (same query params; requires `export_grievance`)
- UI: **Export report** on `/grievance` — section checkboxes independent of on-screen widget visibility; **Use current dashboard widgets** copies visible cards
- Future E2E: open print preview with filters, assert section headings and 200 response on PDF route

### Grievance escalation multi-project list + dashboard API (Playwright)

- **Status: gap** — `grievance-escalation-multi-project.spec.ts` absent; npm runner removed.
- Design intent: two projects with different SLAs; list escalation labels; `GET /api/grievance/dashboard` `needsEscalationRows[].action` = `escalate` vs `close`.

### Grievance dashboard escalation scenario design (new)

- Intended coverage was multi-project + escalation-scenario specs (both currently missing — see Known gaps).
- Optional future spec `grievance-dashboard-escalation.spec.ts` for dedicated dashboard UI widget assertions.

### Grievance full self-contained (no PHP seed for project or library)

- **Status: gap** — design below is retained; `grievance-full-self-contained.spec.ts` is **not** currently in `tests/e2e/`.
- Scenario intent (when restored):
  - **Do not** set `E2E_DB_SEED=1` when running this spec (it skips if set).
  - Playwright creates projects, profiles, option rows, stages, then exercises dashboard/list/escalation/history.
  - Clears Development simulated date at the end.

### Grievance respondents — Is PAPS column (planned)

- **Status: gap** — no npm script and no `grievance-respondents-is-paps.spec.ts` in tree.
- Scenario intent: `/grievance/respondents` Search / Sort / Export PDF; **Is PAPS** cells match `Yes` / `No` / `Yes, No` (or empty-state).

### Grievance list filters scenario design (new)

- **Status: gap** — `grievance-list-filters.spec.ts` not in tree (design only).
- Scenario intent:
  - Create multiple grievances across two projects with distinct statuses and record dates.
  - Verify list filters for `status`, `project_id`, `progress_level`, `respondent_id`, `date_from`, and `date_to`.
  - Verify `needs_escalation=1` by setting deterministic stage SLA (`days_to_address`) and advancing Development simulated date.
  - Use Philippine-context test fixtures (Filipino names and realistic barangay/LGU grievance narratives) for closer UAT parity.

### Users login + bulk create + re-auth verification (new)

- **Status: gap** — `users/login-create-three-users-verify.spec.ts` not in tree (design only).
- Scenario intent:
  - Sign in as admin, create three distinct accounts (first available role), confirm each lands on `/users/view/{id}`.
  - Clear cookies and sign in as admin again (“test again”).
  - On `/users`, search each username and assert a table row exists.
  - Delete the three users to leave the database tidy.

### Main dashboard scenario design (new)

- **Status: gap** — `dashboard/dashboard-main.spec.ts` not in tree; partial coverage via `full-system.spec.ts` / `api-smoke.spec.ts`.
- Scenario intent:
  - Validate main dashboard widget/chart rendering on `/` (Profile, Structure, Grievance, Users).
  - Validate each dashboard “View All” route navigation to module list pages.
  - Validate `/api/dashboard` payload contract (`profile`, `structure`, `grievance`, `users` sections).
  - Validate grievance widget behavior after create + status transition scenario.

## 1) Goals

- Catch UI and behavior regressions early (filters, columns, modals, restore/delete, imports).
- Validate API contracts used by frontend flows (`/api/*` envelope, auth behavior).
- Keep tests deterministic and maintainable for a custom PHP MVC app.

## 2) Recommended Test Stack

- **UI functional tests (E2E):** Playwright
  - Reliable auto-waiting
  - Good trace/video artifacts in CI
  - Strong selector strategy support
- **API functional tests:** PHPUnit (or Pest) + HTTP client
  - Validate response shape/status/auth for UI-dependent endpoints

## 3) Test Layers

### A. E2E UI layer (primary value)

Use for user-facing workflows:

- login/logout
- profile list page and filters
- custom columns dialog
- import dialog behavior
- soft delete/restore flows

### B. API functional layer

Use for endpoint-level behavior that UI depends on:

- `/api/profile/list`
- `/api/projects`
- `/api/users/field-personnel`
- `/api/system/log`

### C. Optional model/controller behavior tests

Add later for logic-heavy methods (for example `Profile::listPaginated` edge cases).

## 4) Repository Layout (proposed)

```text
tests/
  e2e/
    auth/
      login.spec.ts
    profile/
      profile-list-filters.spec.ts
      profile-columns.spec.ts
      profile-import.spec.ts
      profile-soft-delete.spec.ts
      profile-structure-tab.spec.ts
    system/
      debug-log-modal.spec.ts
  api/
    AuthApiTest.php
    ProfileApiTest.php
    ProjectApiTest.php
```

## 5) Environment and Data Design

### Required variables

- `BASE_URL` (example: `http://eco.local`)
- `ADMIN_USER`, `ADMIN_PASS` — in **`playwright.config.ts`**, when **`CI`** is **not** set, defaults **`admin`** / **`admin123`** are applied if these are unset (so bare `npx playwright test` works locally). **CI** must inject real credentials via secrets.
- `STANDARD_USER`, `STANDARD_PASS`
- `HEADLESS` (optional)
- `E2E_DB_SEED` (`1` to enable Playwright global setup database reset + seeding)
- `E2E_SEED_PROFILES` (optional profile seed count for E2E setup; default `40`)
- `E2E_SKIP_GRIEVANCE_OPTIONS_SEED` (`1` to skip running `database/seeders/seed_grievance_options.php` from global setup — not recommended; grievance create/dashboard E2E assumes Options Library rows exist)
- `E2E_SKIP_STRUCTURE_OPTIONS_SEED` (`1` to skip `database/seeders/seed_structure_options.php` — structure tagging status defaults still come from migration **092** when empty)
- `PW_SLOW_MO_MS` (optional Playwright `launchOptions.slowMo` delay in milliseconds; inserts a pause between browser operations for headed debugging)
- `PW_SLOW_RUN` (`1` or `true`) — increases test and expect timeouts (same multiplier as slow-mo runs) **without** enabling `slowMo`, for headed runs that only need more time, not artificial pacing.
  - Either `PW_SLOW_MO_MS > 0` or `PW_SLOW_RUN` triggers the longer timeouts in `playwright.config.ts`.

### Data setup

- **Default Playwright runs:** `tests/e2e/support/global-setup.ts` always executes `php database/seeders/seed_grievance_options.php` before tests (unless `E2E_SKIP_GRIEVANCE_OPTIONS_SEED=1`). That seeder is idempotent and fills empty grievance option tables so `/grievance/create` has GRM channel / language / type / category inputs.
- **Heavy seed (`E2E_DB_SEED=1`):** additionally truncates and seeds projects, users, and profiles (see global-setup source).
- Dedicated QA database (not shared with dev experiments)
- Deterministic fixture records:
  - known projects
  - known field personnel
  - profiles with varied `project_id`, `custom_barangay`, `date_of_invitation`, soft-delete state

## 6) Selector Strategy

Prefer deterministic selectors:

- `data-testid="profile-filter-project"`
- `data-testid="profile-columns-button"`
- `data-testid="profile-import-modal"`
- `data-testid="profile-table"`

Fallback when test IDs are unavailable:

- role/text selectors (`button:has-text("Columns")`)
- stable element IDs (`#profileImportModal`, `#profile-import-form`)

## 7) `/profile` Baseline Automated Coverage

### Smoke suite (run per PR)

1. Login as admin
2. Open `/profile`
3. Apply `Project` filter
4. Open `Columns` modal
5. Verify `Field Personnel` is present in custom columns
6. Toggle and verify table header/cell behavior
7. Clear filters
8. Logout

### Regression suite (nightly)

1. Combined filters (`project + field_personnel + control_number`)
2. Soft delete + restore (admin)
3. Non-admin cannot access deleted-only records
4. Import modal opens and invalid CSV shows validation feedback
5. Debug log modal can open and render content

## 8) Assertion Model

Each automated test should include:

1. **UI assertion** (element visibility/state)
2. **Data assertion** (rows reflect selected criteria)
3. **Contract assertion** where applicable (network response shape or status)

## 9) Flakiness Controls

- Use explicit UI expectations (`toBeVisible`, `toHaveText`, etc.)
- Avoid fixed sleeps where possible
- Re-query elements after rerender-heavy actions
- Use CI retries sparingly (for example `retries: 1` only in CI)

## 10) CI Strategy

### Pull requests

- Install dependencies
- Start app services
- Run smoke E2E
- Upload artifacts on failure:
  - screenshots
  - traces
  - videos

### Nightly

- Run full E2E regression + API suite

## 11) Rollout Plan

### Phase 1 (quick wins)

- Add Playwright scaffold
- Add auth helper fixture
- Implement 3 profile smoke tests

### Phase 2

- Add import and soft-delete E2E tests
- Add core API functional tests

### Phase 3

- Expand module coverage (structure, grievance, users)
- Add test IDs to fragile pages

## 12) Mapping to Existing Manual QA IDs

| Manual QA ID | Automated target file |
|---|---|
| PROF-006 Project filter | `tests/e2e/profile/profile-list-filters.spec.ts` |
| PROF-016 Field Personnel in columns | `tests/e2e/profile/profile-columns.spec.ts` |
| PROF-027 Restore profile | `tests/e2e/profile/profile-soft-delete.spec.ts` |
| PROF-021/022 Import validation | `tests/e2e/profile/profile-import.spec.ts` |

## 13) Current `/profile` Automation Coverage Matrix (2026-04-16)

| QA section | Coverage status | Automated spec(s) |
|---|---|---|
| A. Access & Permissions | Implemented (admin + standard-user path when creds are set) | `tests/e2e/profile/profile-permissions.spec.ts` |
| B. List Rendering & Baseline | Implemented (open list, row actions via add/edit/delete/restore flows) | `tests/e2e/profile/profile-add.spec.ts`, `tests/e2e/profile/profile-edit.spec.ts`, `tests/e2e/profile/profile-soft-delete.spec.ts` |
| C. Filters | Implemented | `tests/e2e/profile/profile-filters.spec.ts` |
| D. Sorting & Columns | Implemented (custom columns + Field Personnel) | `tests/e2e/profile/profile-columns.spec.ts` |
| E. Import Modal & CSV Import | Implemented | `tests/e2e/profile/profile-import.spec.ts` |
| F. CRUD Entry-point Validation | Implemented | `tests/e2e/profile/profile-add.spec.ts`, `tests/e2e/profile/profile-edit.spec.ts` (main save via **`POST /api/profile/update/{id}`**), `tests/e2e/profile/profile-soft-delete.spec.ts` |
| F3. Invitation card (RSVP + distribution) | Implemented | `tests/e2e/profile/profile-invitation-card.spec.ts`, `tests/e2e/support/invitation-card-helpers.ts` |
| SYS. Backup/restore invitation round-trip | Implemented (CLI, dev DB only) | `tests/cli/backup_restore_invitation_roundtrip_test.php` (`npm run test:backup-restore`) |
| SYS. Backup/Restore web UI | Gap (spec + npm runners removed) | Restore `tests/e2e/system/backup-restore-ui.spec.ts` + scripts when ready |
| F2. Profile Structure tab (in-page) | Implemented (tab + modal + create with image + store contract + table) | `tests/e2e/profile/profile-structure-tab.spec.ts` |
| G. Soft-delete View Modes | Implemented (`active`, `with`, `only`) | `tests/e2e/profile/profile-soft-delete.spec.ts`, `tests/e2e/profile/profile-permissions.spec.ts` |
| H. Export/PDF | Implemented | `tests/e2e/profile/profile-export-pdf.spec.ts` |
| I. Security / Negative / Robustness | Implemented for query tampering, CSRF negative, XSS-safe rendering | `tests/e2e/profile/profile-security.spec.ts` |
| J. UX/Compatibility | Planned (can be added with viewport/browser matrix and throttling profiles) | _pending dedicated spec(s)_ |

## 14) Minimal Playwright Spec Blueprint (reference)

```ts
import { test, expect } from '@playwright/test';

test('profile columns includes field personnel', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="username"]', process.env.ADMIN_USER || '');
  await page.fill('input[name="password"]', process.env.ADMIN_PASS || '');
  await page.click('button[type="submit"]');

  await page.goto('/profile');
  await page.getByRole('button', { name: 'Columns' }).click();
  await expect(page.getByText('Field Personnel')).toBeVisible();
});
```

This is intentionally small; production tests should use shared fixtures and stable selectors.
