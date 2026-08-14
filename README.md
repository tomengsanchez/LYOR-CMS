# Simple CMS

A lightweight custom PHP MVC content management system with pages, posts, categories, and media library.

Built on PHP 8+, MySQL/MariaDB, Bootstrap 5, and jQuery. Retains admin essentials: auth (2FA), users/roles, settings, backup/restore, and audit trail.

**Requirements:** PHP 8.0+, MySQL 5.7+ (or MariaDB 10.2+), Apache with mod_rewrite (or nginx equivalent).

## Routes

| URL | Purpose |
|-----|---------|
| `/` | Public homepage (published page slug `welcome` or `home`) |
| `/p/{slug}` | Public static page |
| `/blog`, `/blog/{slug}` | Public blog |
| `/admin` | Admin dashboard (requires login) |
| `/admin/login` | Sign in |
| `/admin/pages`, `/admin/posts`, … | CMS and admin modules |
| `/api/*` | REST API (unchanged) |

**More detail:** [docs/DEVELOPMENTGUIDE.md](docs/DEVELOPMENTGUIDE.md), [docs/DOCUMENTATION.md](docs/DOCUMENTATION.md), [CONTRIBUTING.md](CONTRIBUTING.md).

## Documentation map

Everything below lives under `docs/` unless noted. **Master index:** [DOCUMENTATION.md](docs/DOCUMENTATION.md).

**`docs/DevelopmentHistory/`** files are named by **calendar date**, not a version sequence: **`M.D.YYYY.json`** means month, day, and four-digit year (e.g. **`4.14.2026.json`** is **14 April 2026**; **`4.10.2026.json`** is **10 April 2026**). Each file’s JSON also includes a `date` field in **`YYYY-MM-DD`** form for clarity.

| File | Purpose |
|------|---------|
| [DOCUMENTATION.md](docs/DOCUMENTATION.md) | Software engineering documentation map (all layers) |
| [REQUIREMENTS.md](docs/REQUIREMENTS.md) | Product goals, scope, functional requirements |
| [NFR.md](docs/NFR.md) | Non-functional requirements (perf, DR, security, a11y) |
| [GLOSSARY.md](docs/GLOSSARY.md) | Domain glossary (PAPSID, GRM, SES, RAP, …) |
| [CAPABILITY_MATRIX.md](docs/CAPABILITY_MATRIX.md) | Roles / capabilities / menus / API |
| [RUNBOOK.md](docs/RUNBOOK.md) | Operations: cron, backup/restore drills, incidents |
| [CONFIGURATION.md](docs/CONFIGURATION.md) | Config files, `app_settings`, env vars |
| [DEPLOYMENT.md](docs/DEPLOYMENT.md) | Hosting topology and deploy checklist |
| [SECURITY.md](docs/SECURITY.md) / [SECURITY.md](SECURITY.md) | Security policy & threat model (root stub) |
| [TEST_STRATEGY.md](docs/TEST_STRATEGY.md) | Test pyramid and coverage map |
| [TESTING_HISTORY_PREREQUISITES.md](docs/TESTING_HISTORY_PREREQUISITES.md) | Prerequisites before testing Activity / Status / SES / notification history |
| [test-results/](docs/test-results/README.md) | Logged E2E / automated test run results (monthly) |
| [RELEASE.md](docs/RELEASE.md) | Release / rollback checklist |
| [CONTRIBUTING.md](CONTRIBUTING.md) | How to contribute (repo root) |
| [DEVELOPMENTGUIDE.md](docs/DEVELOPMENTGUIDE.md) | Project layout, stack, routing, database schema, migrations, conventions |
| [FrameworksGuide.txt](docs/FrameworksGuide.txt) | Compact stack and architecture summary |
| [adr/README.md](docs/adr/README.md) | Architectural Decision Records (why binding technical choices were made) |
| [ERD.md](docs/ERD.md) | Entity-relationship diagrams (Mermaid) for the database schema |
| [UML.md](docs/UML.md) | UML diagrams (Mermaid): components, classes, sequences, state |
| [UML_SES_IMPORT.md](docs/UML_SES_IMPORT.md) | SES ZIP import feature UML (control_number match, versions) |
| [CHANGES.md](docs/CHANGES.md) | Change log index (monthly files under `docs/changes/YYYY-MM/`) |
| [API_CONTRACT.md](docs/API_CONTRACT.md) | JSON response envelope, status codes, domain rules for integrators |
| [API_AUTH.md](docs/API_AUTH.md) | REST login / Bearer token usage and Postman-style examples |
| [MOBILE_APP_INTEGRATION.md](docs/MOBILE_APP_INTEGRATION.md) | Mobile/third-party app: auth, capabilities, location pickers, multipart examples |
| [MOBILE_2FA_GUIDE.md](docs/MOBILE_2FA_GUIDE.md) | Mobile email 2FA verify flow (login challenge → OTP → token) with TS/Dart/Kotlin/Swift snippets |
| [API_ERROR_CODES.md](docs/API_ERROR_CODES.md) | Stable API `error.code` registry for mobile clients |
| [postman/README.md](docs/postman/README.md) | Postman: **Simple-CMS-API** (current) + legacy PAPeR exports |
| [dev-help/](dev-help/) | In-browser developer guide (architecture, HTTP layers, modules); links to full `docs/` where needed |
| [FRONTEND_JS_CONVENTIONS.md](docs/FRONTEND_JS_CONVENTIONS.md) | Frontend JS standards (external scripts, config bridge, no inline handlers) |
| [QA_ESCALATION_REGRESSION.md](docs/QA_ESCALATION_REGRESSION.md) | Manual QA checklist for grievance escalation behavior |
| [AUTOMATED_FUNCTIONAL_TEST_DESIGN.md](docs/AUTOMATED_FUNCTIONAL_TEST_DESIGN.md) | Automated functional testing strategy, suite structure, and rollout plan |
| [DevelopmentHistory/4.6.2026.json](docs/DevelopmentHistory/4.6.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.7.2026.json](docs/DevelopmentHistory/4.7.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.8.2026.json](docs/DevelopmentHistory/4.8.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.10.2026.json](docs/DevelopmentHistory/4.10.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.14.2026.json](docs/DevelopmentHistory/4.14.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.15.2026.json](docs/DevelopmentHistory/4.15.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.16.2026.json](docs/DevelopmentHistory/4.16.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.17.2026.json](docs/DevelopmentHistory/4.17.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.18.2026.json](docs/DevelopmentHistory/4.18.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.23.2026.json](docs/DevelopmentHistory/4.23.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/4.30.2026.json](docs/DevelopmentHistory/4.30.2026.json) | Dated development notes (JSON) |
| [DevelopmentHistory/5.1.2026.json](docs/DevelopmentHistory/5.1.2026.json) | Dated development notes (JSON) |
| [SecurityAudit/](docs/SecurityAudit/) | Security audit notes (e.g. `sec-aud-*.json`) |

## Setup

### 1. Configuration

Copy the sample config and edit with your MySQL credentials:

```bash
cp config/database-sample.php config/database.php
```

Edit `config/database.php`:

- host, dbname, username, password

Optional (for subfolder installs): `cp config/app-sample.php config/app.php` and set `base_url` (e.g. `/paper`).

### 2. Composer dependencies (recommended)

PDF list and detail exports use **mPDF** (`mpdf/mpdf` in `composer.json`). From the project root:

```bash
composer install
```

Without vendor packages, PDF routes respond with **503** and prompt to run `composer install` (see `App\PdfExport::requireLibrary()`).

### 3. Database

Create a MySQL database:

```sql
CREATE DATABASE paper_db2;
```

(Use the same name as in `config/database.php`.)

### 4. Run Migrations

From the project root:

```bash
php cli/migrate.php
```

This applies all `database/migration_*.php` scripts in order (roles, users, app_settings, projects, profiles, structures, grievances, notifications, API tokens, sessions, etc.). Use `php cli/migrate.php --status` to see migration status.

### 5. Seed Grievance Options (Recommended)

Seed commonly used default data for the Grievance module (vulnerabilities, respondent types, GRM channels, preferred languages, grievance types, categories):

```bash
php database/seeders/seed_grievance_options.php
php database/seeders/seed_structure_options.php
```

Safe to re-run: skips tables that already have data.

### 6. Optional seeds (sample data)

Run from the project root only when you want demo or extra data. Assumes migrations (and usually grievance options) are already applied.

| Command | Purpose |
|--------|---------|
| `php database/seeders/seed_profiles.php` | Bulk demo profiles (default **500** rows; `--count=N`, `--profiles=N`, `-n N`, `-c N`, or trailing number). Requires at least one active project; each run inserts new `SEED-PROFILE-*` rows. |
| `php database/seeders/seed_projects_users_field_team.php` | Demo projects (with barangays), Field Team role, demo user, optional `SEED-DEMO-*` profiles (`--profiles=N` or `-p N` or trailing number). Idempotent; does **not** create structures. See script header in `database/seeders/seed_projects_users_field_team.php`. |
| `php database/seeders/seed_grievances.php` | Bulk demo grievances for list/export performance testing (default **1000** rows; `--count=N`, `--grievances=N`, `-n N`, `-c N`, or trailing number). Requires at least one **project**; uses lookup data when present. Case numbers use prefix `SEED-GRV-`. Each run inserts new rows. |

To reset most application data while keeping **migrations**, **roles/capabilities**, and the **`admin`** user, use **`php cli/truncate_fresh_install.php`** (optional `--yes` to skip the confirmation prompt). It truncates transactional tables, clears `app_settings`, removes non-admin users, and clears related tokens/sessions. Afterward run `php database/seeders/seed_grievance_options.php`, `php database/seeders/seed_structure_options.php` (and optional seeds above) as needed. Details: [docs/DEVELOPMENTGUIDE.md](docs/DEVELOPMENTGUIDE.md).

### 7. Web Server

**Option A – Document root = `public/`** (recommended)

- Point your web server document root to the `public/` folder
- Apache: `DocumentRoot /path/to/<project-root>/public`

**Option B – Document root = project root**

- Ensure `index.php` in the project root forwards to `public/index.php`
- Apache: Enable mod_rewrite and ensure `.htaccess` is in `public/` (or adjust RewriteBase)

### 8. Default Login

- **Username:** admin  
- **Password:** admin123

Change after first login in production.

**Verify production blockers before go-live:**

```bash
# Dev/staging (allows default admin123; checks B1, B2, B4, B5)
npm run test:e2e:production-blockers

# Production deploy (also fails if ADMIN_PASS is still admin123)
set E2E_PRODUCTION_BLOCKERS=1
set ADMIN_PASS=your-new-password
npm run test:e2e:production-blockers:prod
```

Blockers: doc root `public/`, block direct `/uploads/`, change default password, update dependencies (`composer audit`), copy backups off-server.

### 9. Background email (optional)

If notification emails are enabled, run periodically (e.g. cron every 1–5 minutes):

```bash
php cli/send_queued_emails.php
```

### 10. Backup and restore

Full backups are a **single ZIP** under `storage/backups/` containing `manifest.json`, `database.sql`, and the `public/uploads/` tree (profile, structure, grievance, app assets). **Treat ZIP files as secret** (full DB + files).

You can create/download backups from the admin UI at **System > Backup/Restore**.
Restore is intentionally **CLI-only**.

**Create a backup** (from project root):

```bash
php cli/backup.php
```

Options:

- `--output=path\to\file.zip` — default is `storage/backups/paper-backup-YYYYmmdd-HHMMSS.zip`
- `--mysqldump=C:\xampp\mysql\bin\mysqldump.exe` — if `mysqldump` is not on `PATH` (Windows/XAMPP)
- `--no-uploads` — SQL only inside the archive (smaller). Prints `Uploads: skipped (--no-uploads)` and sets `includes_uploads: false` in `manifest.json`. The admin **Exclude uploads** checkbox uses the same flag.
- `--large-mode` — GB-scale mode: stream mysqldump to disk (no in-memory SQL), run disk-space preflight estimate, lower ZIP compression overhead, and print progress while archiving uploads

If `mysqldump` is not available, the script falls back to a **PHP PDO** dump (prefer mysqldump on production for routines/triggers and fidelity).

Env: `MYSQLDUMP_PATH` can point to the `mysqldump` executable.

**Restore (CLI-only)** (overwrites tables in the database in `config/database.php` and restores uploads unless disabled):

```bash
php cli/restore.php --from=storage/backups/paper-backup-20260417-120000.zip
```

Add `--yes` to skip the confirmation prompt (automation only). Options: `--no-uploads`, `--mysql=...` (or env `MYSQL_PATH`), `--no-migrate` (skip post-restore `php cli/migrate.php`), `--skip-safety-backup` (automation/tests only), `--no-completion-audit` (skip dump vs live row-count check), `--no-auto-rollback` (fail audit without restoring latest safety/backup ZIP), `--force` (allow foreign/missing manifest `app` or dbname mismatch), `--keep-extra-tables` (do not wipe leftover tables before import). Without the `mysql` client, import uses PDO (slower; very large dumps may need the client). Both paths run the same SQL sanitize first (MariaDB sandbox banner + legacy generated-column INSERTs), so restore behavior does not depend on which importer is used. Restore logs `import_path=mysql|pdo`. By default the target schema is wiped before import. After import, restore audits INSERT row counts from the **original** dump against the live DB; on mismatch it auto-rolls back from the pre-restore snapshot (or newest ZIP under `storage/backups`).
For very large archives, add `--large-mode` to enable preflight extraction-space checks and progress output for long SQL/upload restore steps.
After confirmation (`YES` or `--yes`), restore automatically creates a safety backup named `storage/backups/paper-before-restore-YYYYmmdd-HHMMSS.zip`. If this pre-restore backup fails, restore aborts (unless `--skip-safety-backup`).
By default, restore runs **pending migrations** after importing SQL so the schema matches the current app (including profile invitation card columns from `migration_064`). Backups include a `schema` block in `manifest.json` recording migration state and invitation column presence.

**Verify backup + invitation fields (dev only — replaces the whole DB):**

```bash
npm run test:backup-restore
```

Always test restore on a **copy** of production first. Keep copies off-server.

## Structure

The folder name of the project root may be anything (e.g. `htdocs`, `paper`). Important paths:

```
<project-root>/
├── App/
│   ├── Controllers/     # HTTP + Api/ subfolder for REST
│   ├── Models/
│   └── Views/
├── Core/                # Router, Database, Auth, Controller, migrations runner, etc.
├── config/
├── cli/                 # migrate.php, backup.php, restore.php, send_queued_emails.php, …
├── storage/backups/   # default output for cli/backup.php (archives not committed)
├── database/            # migration_*.php; seeders/ (PHP seed scripts)
├── composer.json        # PHP deps (e.g. mPDF for PDF export); run composer install
├── docs/                # Developer docs (see Documentation map section in this README)
├── public/              # Web root – index.php defines routes
│   └── assets/js/       # External JS per module/view (no inline view behavior)
├── docs/postman/        # Postman v2.1 collection + environment (import); optional local `postman/` is gitignored
├── dev-help/            # Developer mini-site (open /dev-help/ when vhost maps to project root)
├── sample-imports/      # Optional CSV samples (e.g. profile import)
├── dbdump/              # Optional local SQL dumps (not used by the app at runtime)
├── bootstrap.php
└── index.php            # When doc root = project root: forwards to public/index.php
```

Some working copies may include other top-level folders (for example a duplicate tree) that are **not** part of the documented application; use the tree above as the canonical layout.

## Features

- **Roles:** Administrator, Standard User, Coordinator
- **Menu links:** Profile, Structure, Grievance, Library (Project, Municipality, Brgy, **Contacts** read-only directory), Settings, notifications, account, help, system tools (by capability), including **System > Realtime Security** for admin monitoring
- **Contacts:** Under **Library** in the nav. Read-only list of phone contacts for PAPS (profiles) and users; edit via Profile/User forms (`view_contacts`). API: `GET /api/contact/list`.
- **Profile:** PAPSID, control number, name fields, barangay, birthday, date of invitation, multi-row contacts (stored in `contacts` table), **required project**, optional multi-select **Structure Tags** (existing tags only), field personnel, CSV/PDF export; **view** and **edit** use tabs (Main, Socio Economic, Structure, Validation Data). The **Structure** tab lists linked structures (view/edit modal; no in-profile create). **Main** edit saves can use **`POST /api/profile/update/{id}`** (AJAX). The standalone **Structure** menu module is used to create structures and manage the global list/export.
- **Structure:** CRUD with images, classification (Primary/Secondary), tagging status; list shows Classification and Status columns; view page **Status History** for tagging status changes
- **Grievance:** Dashboard, list, respondents library (registered rows plus inline/PAPS name groups), options admin, CSV export; non-PAPS create/edit defers respondent autocomplete APIs until first name has 3+ characters (see `docs/API_CONTRACT.md`)
- **Library:** Searchable project/coordinator management
- **Settings:** UI, notifications, email (SMTP + test), security (2FA, session timeout, password policy)
- **REST API:** Bearer tokens and session; see `docs/API_CONTRACT.md` and `public/index.php` for routes
- **Frontend JS pattern:** View behavior is separated into `public/assets/js/<module>/...`; views pass only minimal `window.*Config` data when needed

---

## Database refactor notes (EAV → flat)

1. **Migration files** – `database/migration_000_initial.php` creates base tables and seeds admin; `database/migration_001_from_eav_to_flat.php` creates flat entity tables and migrates EAV data where applicable.

   Format for new migrations (rollback requires a callable `down`):

   ```php
   return [
       'name' => 'migration_XXX_description',
       'up' => function (\PDO $db): void { /* ... */ },
       'down' => function (\PDO $db): void { /* ... */ },
   ];
   ```

2. **Migration runner** – `Core/MigrationRunner.php` loads `database/migration_*.php`; `cli/migrate.php` runs pending migrations, `--status`, and `--rollback [--steps=N]`.

3. **Models** – Domain classes live under `App/Models` and use PDO directly on flat tables (not EAV).

4. **Legacy** – Older standalone SQL seeds and the historical `seed_profiles_structures*.php` family were removed in favor of migrations plus the PHP seeders under `database/seeders/` (`seed_grievance_options.php`, `seed_projects_users_field_team.php`).
#   L Y O R - C M S  
 