# Simple CMS – Development Guide

> **Simple CMS** (2026-08): Public website + admin at `/admin`. Content modules: pages, posts, categories, tags, media, menus, comments, widgets, block builder, permalinks, RSS. Legacy PAPeR domain code is archived under `database/migrations_legacy/` and `App/Views/help/pages_legacy/`.

This guide describes structure, routing, database schema, settings, and conventions for developers working on the CMS.

**Related docs:** [DOCUMENTATION.md](DOCUMENTATION.md) · [CONFIGURATION.md](CONFIGURATION.md) · [API_CONTRACT.md](API_CONTRACT.md) · [CHANGES.md](CHANGES.md) · [docs/postman/Simple-CMS-API.postman_collection.json](postman/Simple-CMS-API.postman_collection.json)

---

## 1. Modules and routes

| Module | Admin | Public | Notes |
|--------|-------|--------|-------|
| **Home** | — | `/` | Static page (Reading settings) or latest posts |
| **Pages** | `/admin/pages` | `/p/{slug}`, plain permalinks, `/index.json` | Visual layout + block builder, SEO, hierarchy |
| **Posts** | `/admin/posts` | `/blog`, `/blog/{slug}`, custom permalinks | Visual layout, comments, tags, featured image |
| **Categories** | `/admin/categories` | `/blog/category/{slug}` | |
| **Tags** | `/admin/tags` | `/blog/tag/{slug}` | Comma-separated on post form |
| **Comments** | `/admin/comments` | Form on posts | Moderation, replies, rate limit |
| **Menus** | `/admin/menus` | Primary header nav | |
| **Widgets** | `/admin/widgets` | Sidebar + footer | Drag to reorder |
| **Media** | `/admin/media` | `/share/media/{id}` | |
| **General** | `/admin/system/general` | Theme, reading, discussion, permalinks, SEO | |
| **Users / Roles** | `/admin/users`, `/admin/users/roles` | — | Capabilities in `App\Capabilities` |

Legacy paths (`/login`, `/pages`, …) **301 redirect** to `/admin/...` via `LegacyRedirectController`.

**Public syndication:** `/sitemap.xml`, `/robots.txt`, `/llms.txt`, `/llms-full.txt`, `/feed.xml`, `/site.json`, `/blog.json`, `/blog/{slug}.json` (when enabled in General → SEO).

---

## 2. Project structure

```
<project-root>/
├── App/
│   ├── Controllers/          # Web + Api\*
│   ├── Models/               # Page, Post, Comment, Widget, …
│   ├── Views/                # admin views + public/ + help/
│   ├── Capabilities.php      # Role capabilities + menu keys
│   ├── ContentBlocks.php     # Block builder parse/render
│   ├── LayoutBuilder.php     # Divi-style Section/Row/Column/Module
│   ├── Permalink.php         # URL generation + catch-all resolver
│   ├── ReadingSettings.php   # Homepage / posts per page
│   ├── DiscussionSettings.php
│   ├── PermalinkSettings.php
│   ├── PublicTheme.php       # Site theme customizer
│   ├── PublicSeo.php         # SEO, JSON-LD, JSON exports
│   ├── MediaImageSizes.php   # Responsive sizes + srcset (WP-style)
│   └── CommentRateLimit.php  # Per-IP comment throttle
├── Core/                     # Router, Controller, Auth, Database, Csrf, MigrationRunner
├── config/
│   ├── database.php          # MySQL/MariaDB (copy from database-sample.php)
│   └── app.php               # base_url, security headers (optional)
├── database/
│   ├── migration_000 … 016   # Active CMS migrations
│   └── migrations_legacy/    # Archived PAPeR migrations
├── public/
│   ├── index.php             # All routes
│   └── assets/js/            # External JS only (no inline behavior in views)
├── cli/
│   ├── migrate.php
│   ├── backup.php / restore.php
│   └── …
├── tests/
│   ├── cli/                  # Smoke tests (cms_*_smoke_test.php)
│   └── e2e/                  # Playwright (cms-smoke, cms-wp-extended)
├── docs/                     # This guide, CHANGES, API, Postman
├── bootstrap.php
└── index.php                 # Forwards to public/ when docroot = project root
```

---

## 3. Stack and architecture

- **Backend:** Custom PHP 8+ MVC (no full framework). See [ADR-0002](adr/0002-custom-php-mvc.md).
- **Database:** MySQL 5.7+ / MariaDB 10.2+ via PDO (`utf8mb4`). See [ADR-0003](adr/0003-mysql-mariadb-compatibility.md).
- **Frontend (admin):** Bootstrap 5.3, layout in `App/Views/layout/main.php`, `public/assets/css/layout/admin.css`.
- **Frontend (public):** Bootstrap 5.3, `App/Views/public/layout.php`, `public/assets/css/public/themes.css` + `site.css`.
- **Auth:** Session-based (`Core\Auth`); Administrator bypasses capability checks. API: Bearer tokens (`App\ApiToken`) on `/api/*`.
- **CSRF:** Required on admin POST actions (`Core\Csrf::validate()`).

---

## 4. Routing

Routes are registered in `public/index.php`. Handler format: `ControllerName@methodName` → `App\Controllers\ControllerName`.

**Order matters:** Specific routes first; **permalink catch-all** (`/{s1}`, `/{s1}/{s2}`, …) must be **last** among public GET routes.

Path parameters: `/admin/pages/edit/{id}` → `PageController::edit(int $id)`.

API handlers: `'Api\PostController@listApi'` → `App\Controllers\Api\PostController`.

---

## 5. Database schema (CMS)

### Core (migration_000–003)

- **users**, **roles**, **role_capabilities**
- **app_settings** — key/value site config
- **migrations** — applied migration names
- **audit_log** — entity history
- **notifications**, **email_queue**
- **user_dashboard_config** — per-user UI prefs
- **backup_archives**

### Content (migration_004–007, 009–013)

| Table | Purpose |
|-------|---------|
| `cms_pages` | Static pages; `parent_id`, `blocks_json`, SEO columns |
| `cms_posts` | Blog posts; `category_id`, `featured_image_id`, `blocks_json` |
| `cms_categories` | Post categories |
| `cms_tags`, `cms_post_tags` | Post tags (many-to-many) |
| `cms_media` | Uploads library |
| `cms_media_sizes` | Intermediate image sizes (thumbnail, medium, large, …) |
| `cms_menus`, `cms_menu_items` | Navigation menus |
| `cms_comments` | Post comments (moderation statuses) |
| `cms_widgets` | Sidebar/footer widgets |

Settings for reading, discussion, permalinks, and public theme are stored in **app_settings** (not separate tables).

---

## 6. Migrations

- **Run:** `php cli/migrate.php`
- **Status:** `php cli/migrate.php --status`
- **Rollback:** `php cli/migrate.php --rollback [--steps=N]`

Active CMS migrations:

| File | Summary |
|------|---------|
| `migration_000_initial` | roles, users, app_settings |
| `migration_001_user_auth` | auth columns |
| `migration_002_notifications_email` | notifications, email_queue |
| `migration_003_audit_backup` | audit_log, backup_archives |
| `migration_004_cms_pages` | cms_pages |
| `migration_005_cms_categories_posts` | categories, posts |
| `migration_006_cms_media` | cms_media |
| `migration_007_cms_seed` | welcome page, default menu |
| `migration_008_user_dashboard_config` | UI prefs |
| `migration_009_cms_post_featured_image` | featured_image_id |
| `migration_010_cms_page_seo` | page SEO + LLM fields |
| `migration_011_content_layout` | content_layout on pages/posts |
| `migration_012_wp_features` | tags, menus, page hierarchy, reading |
| `migration_013_wp_comments_widgets_blocks` | comments, widgets, blocks_json |
| `migration_014_post_seo_llm` | post SEO title, description, LLM summary, noindex |
| `migration_015_media_responsive_sizes` | cms_media_sizes + WordPress-style variants |
| `migration_016_layout_builder` | layout_json on cms_pages / cms_posts (visual builder) |

Format: PHP file returning `name`, `up`, `down` callables. DDL steps should be idempotent where practical (`SHOW COLUMNS` guards).

---

## 7. Site settings (System → General)

| Area | Class | Keys (app_settings) |
|------|-------|------------------------|
| Branding | `App\Models\AppSettings` | app_name, company_name, logo |
| Reading | `ReadingSettings` | homepage static vs posts, posts_per_page |
| Discussion | `DiscussionSettings` | comments, moderation, sidebar, **comment_rate_limit** |
| Permalinks | `PermalinkSettings` | page/post URL patterns |
| Public theme | `PublicTheme` | presets, fonts, widths, color mode |
| Site SEO | `AppSettings::getSiteSeoConfig()` | sitemap, RSS, JSON export, llms.txt, AI crawlers, OG defaults |

Preview unsaved theme: `/?theme_preview=1` (admin only).

---

## 8. Important conventions

### Capabilities

Defined in `App\Capabilities`. Controllers call `$this->requireCapability('view_pages')`. Menu visibility uses `Auth::can()` (admin bypasses).

Key CMS capabilities: `view_pages`, `view_posts`, `moderate_comments`, `manage_categories`, `view_settings`, `view_media`.

### JavaScript

**External files only** under `public/assets/js/`. Views may output JSON config blocks for JS; no inline `onclick` / behavior scripts.

Examples: `public/assets/js/content/blocks.js`, `public/assets/js/builder/editor.js`, `public/assets/js/widgets/form.js`, `public/assets/js/public/comments.js`.

### Visual layout builder (Divi-style)

JSON stored in `layout_json` on pages/posts (migration **016**). Structure: Section → Row → Column → Module. Phase 1 modules: heading, text, image, button, CTA, spacer, divider, HTML, blurb.

- **Admin:** `GET /admin/builder/page/{id}` / `GET /admin/builder/post/{id}`; save via `POST .../save` (FormData `layout_json` + CSRF, check without rotate).
- **Entry:** “Edit via Frontend editor” on page/post forms (after the entity is saved).
- **Row columns:** Content panel layout picker (1–4 equal + common splits); row chrome **Columns** opens it. Modules are preserved when the layout changes.
- **Modules:** Side-panel type picker (no `prompt`); empty-column **+ Add module**; ↑ ↓ / Dup on modules; ↑ ↓ on sections and rows; Ctrl/Cmd+S to save.
- **PHP:** `App\LayoutBuilder` parse/normalize/render; assets `public/assets/js/builder/editor.js`, `public/assets/css/admin/builder.css`.
- **Public precedence** (`ContentBlocks::renderEntity`): `layout_json` → `blocks_json` → HTML `body`.
- **Backup:** column included in full DB dump; no special restore steps.

### Block builder

JSON stored in `blocks_json` on pages/posts. Admin: block types in `App\ContentBlocks`. Public: used when no visual layout is present. Image blocks store `media_id` + public `url` and render with `srcset`.

### Media / responsive images

JPG/PNG/WebP uploads generate intermediate files via `App\MediaImageSizes` (GD). Public markup: `Media::responsiveImg()`. Serve: `/share/media/{id}/{size}`. Backfill: `php cli/regenerate_media_sizes.php`. Variants live under `public/uploads/media/` and are included in backup ZIPs.

**Editor UX:** Page/post forms upload via `POST /admin/media/upload-json` (`MediaController@uploadJson`, CSRF check without rotate). Block builder listens for `cms:media-uploaded`. Posts can quick-create categories via `POST /admin/categories/quick-store`.

### Permalinks

`App\Permalink::urlForPage()` / `urlForPost()` used for menus, blog links, sitemap, RSS, and social meta. Catch-all resolver in `PublicController@permalinkResolve`.

Plain page/post slugs cannot collide across `cms_pages` and `cms_posts` (`CmsSlug::conflictsWithOtherContent`).

### Comments

Public submit: `POST /comment/post/{id}`. Honeypot field `website`. Rate limit: `CommentRateLimit` counts submissions per IP in the last hour (`discussion_comment_rate_limit`, default 10; 0 = unlimited).

### Backup / restore

- **Backup:** `php cli/backup.php` or System → Backup & Restore (full DB dump + uploads).
- **Restore:** CLI only — `php cli/restore.php --from=path/to.zip`. Includes all `cms_*` tables and `app_settings`.
- **Verify:** `npm run test:backup-restore` (when configured).

### Audit

`App\AuditLog::record($entityType, $entityId, $action)` on create/update/delete for pages, posts, comments, widgets, etc.

---

## 9. REST API

Registered in `public/index.php` under `/api/`. JSON envelope: `{ success, data, error }`.

| Endpoint | Auth | Purpose |
|----------|------|---------|
| `POST /api/auth/login` | — | Bearer token |
| `GET /api/pages`, `/api/posts`, `/api/media` | Bearer | List (read-only) |
| `GET /api/system/general` | Bearer (admin) | Branding, SEO, reading, discussion, permalinks |
| `GET /api/settings/ui` | Bearer | User UI prefs |

Postman: `docs/postman/Simple-CMS-API.postman_collection.json`.

---

## 10. Testing

### CLI smoke tests

```bash
php tests/cli/cms_wp_features_smoke_test.php
php tests/cli/cms_wp_extended_smoke_test.php
php tests/cli/cms_rss_smoke_test.php
php tests/cli/cms_llm_discovery_smoke_test.php
php tests/cli/cms_media_responsive_smoke_test.php
```

Or: `npm run test:cms-wp-features`, `npm run test:cms-wp-extended`, `npm run test:cms-rss`, `npm run test:cms-llm`, `npm run test:cms-media-responsive`, `npm run test:cms-inline-media`.

### Playwright E2E

Copy `.env.playwright.example` → `.env.playwright`:

```env
BASE_URL=http://eco.local
ADMIN_USER=admin
ADMIN_PASS=admin123
```

```bash
npm run test:e2e:cms          # headless
npm run test:e2e:cms:headed # watch mode
```

---

## 11. Configuration

- **Database:** `config/database.php` from `config/database-sample.php`
- **Base URL:** `config/app.php` → `base_url` for subfolder installs
- **Document root:** Point vhost at `public/` (recommended)

---

## 12. Default login

- **Username:** `admin`
- **Password:** `admin123`

Change in production. Never commit `config/database.php` or `.env.playwright`.

---

## 13. Change log and history

- Monthly changes: `docs/changes/YYYY-MM/CHANGES.md`
- Dated notes: `docs/DevelopmentHistory/M.D.YYYY-*.json`

When adding features: update CHANGES, help pages under `App/Views/help/pages/`, and Postman if API routes change.
