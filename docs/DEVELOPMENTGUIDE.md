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
| **Posts** | `/admin/posts` | `/blog`, `/blog/{slug}`, archives, author, custom permalinks | Visual layout, comments, tags, sticky, schedule |
| **Categories** | `/admin/categories` | `/blog/category/{slug}` | |
| **Tags** | `/admin/tags` | `/blog/tag/{slug}` | Comma-separated on post form |
| **Comments** | `/admin/comments` | Form on posts | Moderation, replies, rate limit |
| **Menus** | `/admin/menus` | Primary header nav | |
| **Widgets** | `/admin/widgets` | Header, after header, homepage, sidebar, after content, footer | Drag to reorder |
| **Media** | `/admin/media` | `/share/media/{id}` (external → redirect to `source_url`) | Upload or **Register URL** (no download); `caption` / `source_url` on `cms_media` |
| **General** | `/admin/system/general` | Theme, reading, discussion, newsletter, permalinks, SEO | |
| **Users / Roles** | `/admin/users`, `/admin/users/roles` | — | Capabilities in `App\Capabilities` |

Legacy paths (`/login`, `/pages`, …) **301 redirect** to `/admin/...` via `LegacyRedirectController`.

**Public syndication:** `/sitemap.xml`, `/robots.txt`, `/llms.txt`, `/llms-full.txt`, `/feed.xml`, `/site.json`, `/blog.json`, `/blog/{slug}.json` (when enabled in General → SEO). **Site search:** `/search`. **Archives:** `/blog/archive/{year}` / `{month}`, `/blog/author/{username}`. Editors preview unpublished content with `?preview=1`.

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
│   ├── LayoutBuilder.php     # Facade: Section/Row/Column/Module (`LayoutBuilder::`)
│   ├── LayoutBuilder/        # ModuleCatalog, Normalizer, Renderer, Sanitize, Css
│   ├── Permalink.php         # URL generation + catch-all resolver
│   ├── ReadingSettings.php   # Homepage / posts per page
│   ├── DiscussionSettings.php
│   ├── NewsletterSettings.php
│   ├── PermalinkSettings.php
│   ├── PublicTheme.php       # Site theme customizer
│   ├── ThemeStylePack.php    # Style pack zip import/export
│   ├── PublicToc.php         # Heading ids + on-page TOC
│   ├── PublicSeo.php         # SEO, JSON-LD, JSON exports
│   ├── MediaImageSizes.php   # Responsive sizes + srcset (WP-style)
│   └── CommentRateLimit.php  # Per-IP comment throttle
├── Core/                     # Router, Controller, Auth, Database, Csrf, MigrationRunner
├── config/
│   ├── database.php          # MySQL/MariaDB (copy from database-sample.php)
│   └── app.php               # base_url, security headers (optional)
├── database/
│   ├── migration_000 … 025   # Active CMS migrations
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
└── index.php                 # Front controller when DocumentRoot = project root
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
| `cms_pages` | Static pages; `parent_id`, `blocks_json`, SEO columns, optional `password_hash` |
| `cms_posts` | Blog posts; `category_id`, `featured_image_id`, `blocks_json`, `is_sticky`, optional `password_hash` |
| `cms_categories` | Post categories |
| `cms_tags`, `cms_post_tags` | Post tags (many-to-many) |
| `cms_media` | Uploads library |
| `cms_media_sizes` | Intermediate image sizes (thumbnail, medium, large, …) |
| `cms_menus`, `cms_menu_items` | Navigation menus |
| `cms_comments` | Post comments (moderation statuses) |
| `cms_widgets` | Public widget areas (header / after_header / home / sidebar / after_content / footer) |
| `cms_newsletter_subscribers` | Newsletter list (pending / confirmed / unsubscribed + confirm/unsub tokens) |

Settings for reading, discussion, newsletter, permalinks, and public theme are stored in **app_settings** (not separate tables).

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
| `migration_017_revisions_redirects` | cms_content_revisions + cms_redirects |
| `migration_018_layout_templates` | cms_layout_templates (reusable builder layouts) |
| `migration_019_content_width_full_default` | Default public content width → full (1320px) |
| `migration_021_ai_citation_bluf` | citation_snippet + faq_json on pages/posts |
| `migration_022_media_source_caption` | cms_media source_url + caption |
| `migration_023_post_sticky` | cms_posts.is_sticky |
| `migration_024_content_password` | Optional password_hash on cms_pages / cms_posts |
| `migration_025_newsletter_subscribers` | cms_newsletter_subscribers (email list + tokens) |

Format: PHP file returning `name`, `up`, `down` callables. DDL steps should be idempotent where practical (`SHOW COLUMNS` guards).

---

## 7. Site settings (System → General)

| Area | Class | Keys (app_settings) |
|------|-------|------------------------|
| Branding | `App\Models\AppSettings` | app_name, company_name, logo |
| Reading | `ReadingSettings` | homepage static vs posts, posts_per_page |
| Discussion | `DiscussionSettings` | comments, moderation, sidebar, **comment_rate_limit** |
| Newsletter | `NewsletterSettings` | enabled, double opt-in, **rate_limit** |
| Permalinks | `PermalinkSettings` | page/post URL patterns |
| Public theme | `PublicTheme` / `ThemeStylePack` | presets, fonts, widths, color mode; style-pack zip import/export |
| Site SEO | `AppSettings::getSiteSeoConfig()` | sitemap, RSS, JSON export, llms.txt, AI crawlers, OG defaults, AI citation/E-E-A-T/topic clusters |
| Content unlock | `ContentPassword` | `cms_content_pass_key` (HMAC for visitor unlock cookies; created on first use) |

Preview unsaved theme: `/?theme_preview=1` (admin only). **Customizer:** `/admin/customize` — sidebar controls + iframe (`customize_frame=1`), session sync via `POST /admin/customize/preview`, publish via `POST /admin/customize/publish` (`CustomizeController`, `theme-preview-bridge.js`). **Style packs:** library at `public/uploads/theme-packs/library/{id}/`; activate/delete/install-bundled + import/export/sample routes on `/admin/customize/*-style-pack`. Bundled zips: example, Play · Build · Sound, **Manly**, **Pulse**, **Enterprise**, **The Filipino Men** (paper journal; `php cli/seed_filipino_men_site.php` for chrome-only site; essays via `php cli/import_atom_feed.php`). Rebuild zips: `php cli/build_style_pack.php`.

---

## 8. Important conventions

### Capabilities

Defined in `App\Capabilities`. Controllers call `$this->requireCapability('view_pages')`. Menu visibility uses `Auth::can()` (admin bypasses).

Key CMS capabilities: `view_pages`, `view_posts`, `moderate_comments`, `manage_categories`, `view_settings`, `view_media`.

### JavaScript

**External files only** under `public/assets/js/`. Views may output JSON config blocks for JS; no inline `onclick` / behavior scripts.

Examples: `public/assets/js/content/blocks.js`, `public/assets/js/content/bulk-list.js`, `public/assets/js/builder/*.js` (boot `editor.js` plus `ns.js`, `history.js`, `model.js`, `styles.js`, `canvas.js`, `layers.js`, `dnd.js`, `actions.js`, `wysiwyg.js`, `panel.js`, `save.js`, `ui.js`), `public/assets/js/widgets/form.js`, `public/assets/js/public/comments.js`, `public/assets/js/public/enhance.js`.

### Content revisions

Snapshots in `cms_content_revisions` (max 50 per page/post). Recorded before `Page::update` / `Post::update` / `saveLayoutJson`. Restore from page/post view: `POST /admin/pages|posts/restore/{id}/{revisionId}`.

### Redirects

`cms_redirects` admin at `/admin/redirects` (`manage_settings`). Public: `Redirect::applyForRequestPath` on permalink 404 and missing `/p/{slug}` / `/blog/{slug}`.

### Content library search

`GET /admin/search?q=` via `App\ContentSearch` (pages, posts, media). Header search box + Content → Library search.

### Visual layout builder (Divi-style)

JSON stored in `layout_json` on pages/posts (migration **016**). Structure: Section → Row → Column → Module. Modules: heading, text, image, button, CTA, spacer, divider, HTML, blurb, **carousel**, **accordion** (`<details>`), **tabs** (CSS radios), **icon list**, **gallery** (2–4 col grid), **testimonials**, **video** (YouTube nocookie / Vimeo / HTTPS file), **inner row** (one nested column row; further nesting stripped on normalize).

- **Admin:** `GET /admin/builder/page/{id}` / `GET /admin/builder/post/{id}`; save via `POST .../save` (FormData `layout_json` + CSRF, check without rotate).
- **Entry:** “Edit via Frontend editor” on page/post forms (after the entity is saved).
- **Device preview:** Desktop / Tablet / Mobile toolbar toggles set canvas `max-width` (1100 / 768 / 390) and which Design bag is edited (`settings` vs `settings_tablet` / `settings_mobile`, same for `design_*`). Public CSS: `@media (max-width: 1023.98px)` / `767.98px`. Editor preview prefixes rules with `[data-device]` because the canvas is not an iframe.
- **Theme colors:** Design color fields accept `#hex` or tokens (`accent`, `accent-soft`, `text`, `muted`, `surface`, `bg`, `border`) compiled to `var(--pub-…)`. Tokens follow Appearance → Customize without rewriting each module.
- **Background image:** Section / row / column Design can set `bg_media_id` / `bg_image` plus overlay color and 0–80% strength. Compiled to `background-image` (optional `linear-gradient` overlay + `url("…")`). URLs are sanitized (no quotes/parentheses). Image is site-wide; overlay can be per-device.
- **Section extras:** Allowlisted shape dividers (`wave`/`tilt`/`curve`/`triangle` hardcoded SVG; color/height keys compile to child CSS). Section `bg_video_url` uses `parseVideoUrl` then a constructed mute/loop youtube-nocookie or Vimeo background iframe (or `<video>`); overflow is clipped on `.cms-layout-section-bg`, not the section (sticky still works). The canvas never loads the iframe. Row `col_reverse_mobile` adds `cms-layout-row--reverse-mobile` (public `@media` below 768px; editor Mobile preview). Shape type, video URL, and reverse are site-wide (not tablet/mobile bags).
- **Design text-align:** Compiled onto `.cms-el-*` and `.cms-el-* > *` so inner wrappers (Blurb’s nested `.cms-mod-blurb`) cannot keep a hardcoded align. Blurb default remains centered when Design align is empty; blurb images follow text-align (no `margin:auto`). Heading, text, CTA, button, testimonials, and icon list did not hardcode `text-align`.
- **Templates:** Shared library in `cms_layout_templates` (migration **018**). Toolbar **Templates** → load / save current / delete. Endpoints: `GET|POST /admin/builder/templates`, `GET /admin/builder/templates/{id}`, `POST .../{id}/delete`.
- **Row columns:** Content panel layout picker (1–4 equal + common splits); row chrome **Columns** opens it. Individual columns: **1–12** width slider + **min height** / vertical align. Drag the edge between two columns to split widths. Modules are preserved when the row layout changes. **Inner row** module repeats the same picker on `mod.columns` (max one nest; nested inner_row nodes are dropped). Canvas inner columns use `cms-lb-inner-col` / `data-kind=inner_column` so outer module DnD still uses `.cms-lb-mod`.
- **Modules:** Side-panel type picker with short descriptions and a filter box (`LayoutBuilder::moduleCatalog()` via editor `data-modules` also carries Content **fields**, **defaults**, and Design **groups**; carousel, accordion, tabs, icon_list, gallery, testimonial, and inner_row use `custom`; video uses catalog fields; media/html preview stay special in `panel.js`; Text/CTA/Blurb fields use `type: rich` with `wysiwyg.js` before `panel.js`); empty-column **+ Add module** or **+** after a module; PHP `normalizeModuleData` / `renderModuleInner` remain type switches (`inner_row` stores columns on the module and `renderColumn`s them). Accordion renders as `<details>` on the public site (no extra JS). Tabs use radio inputs + CSS (no extra JS). Icon list text/icons are escaped. Accordion/Tabs **bodies** and Text/CTA/Blurb copy use `sanitizeRichText` (allowlisted tags; heading stays escaped). Gallery captions/links are sanitized like carousel slides. Testimonials quotes/names are escaped. Video URLs are parsed to a constructed YouTube nocookie or Vimeo player iframe (or `<video>` for HTTPS files); the canvas never loads those iframes. Default CSP (`Core\SecurityHeaders`) allows `frame-src` youtube-nocookie + player.vimeo.com and `media-src` HTTPS. Content / Design / Advanced inspector (Design packs are per module type — Spacer is spacing only; Hover only if color/type/chrome; Advanced also has relative/sticky and z-index 1–100 on design/settings, never hover; Copy style skips them); Design **Normal / Hover** (hover stored in `design_hover`, same CSS on every device; compiled `:hover` includes `.btn` / `.public-site` so button themes do not win); **Copy style** / **Paste style** (appearance bags only; reverse-columns is omitted); drag **⋮⋮** to reorder; **Layers** tree (search box; drag rows to reorder; hover previews on the canvas; inner column/module layers are not draggable); click heading/text on canvas to type in place (text modules save inner HTML); **Copy** / **Paste** toolbar, right-click menu, and Ctrl/Cmd+C/V/X (in-memory only); **?** for shortcuts; Alt+arrows to nudge; 85/100/115% zoom; **Undo / Redo**; autosave about every 12s when dirty (deferred while typing; does not rebuild the canvas). Zoom / device / Layers stay for the browser tab (`sessionStorage`). Confirm before delete. Button and CTA support `style` (`primary|secondary|outline`) and `new_tab`. Column settings `min_height` and `valign` live in `layout_json` (backup via SQL dump). **Design / settings CSS** (colors including theme tokens, spacing as TRBL that stores a shorthand string, min-height, text align, font size/weight/line-height, allowlisted font-family / letter-spacing / text-transform keys, relative/sticky position + z-index 1–100, border/radius, allowlisted box-shadow, section background image + overlay, shape divider color/height, module hover) patches that node’s rules in `#cmsBuilderLiveCss` without rebuilding the canvas, including per-device overrides; width, valign, and hide-on-device patch classes in place. Shape type, video URL, reverse-columns, custom CSS class, and section type still rebuild. **Content** edits patch the selected module’s inner HTML (carousel and accordion/tabs/icon-list/gallery/testimonial add/remove still rebuild; inner_row always rebuilds). Public HTML prepends `<style class="cms-layout-css">` compiled by `LayoutBuilder::compileStylesheet` (same safeColor / safeSpacing / safeHeight checks; not stored — derived from `layout_json`; walks inner_row columns).
- **PHP:** `App\LayoutBuilder` facade plus `App/LayoutBuilder/` (`ModuleCatalog`, `Normalizer`, `Renderer`, `Sanitize`, `Css`); `App\Models\LayoutTemplate`; assets `public/assets/js/builder/` (split modules including `styles.js`, boot `editor.js`) and `public/assets/css/admin/builder.css`.
- **Public precedence** (`ContentBlocks::renderEntity`): `layout_json` → `blocks_json` → HTML `body`.
- **Backup:** `layout_json` and `cms_layout_templates` included in full DB dump; no special restore steps.

### Block builder

JSON stored in `blocks_json` on pages/posts. Admin: block types in `App\ContentBlocks`. Public: used when no visual layout is present. Image blocks store `media_id` + public `url` and render with `srcset`.

### Media / responsive images

JPG/PNG/WebP uploads generate intermediate files via `App\MediaImageSizes` (GD). Public markup: `Media::responsiveImg()`. Serve: `/share/media/{id}/{size}`. Backfill: `php cli/regenerate_media_sizes.php`. Variants live under `public/uploads/media/` and are included in backup ZIPs.

**Editor UX:** Page/post forms upload via `POST /admin/media/upload-json` (`MediaController@uploadJson`, CSRF check without rotate). Block builder listens for `cms:media-uploaded`. Posts can quick-create categories via `POST /admin/categories/quick-store`.

### Permalinks

`App\Permalink::urlForPage()` / `urlForPost()` used for menus, blog links, sitemap, RSS, and social meta. Catch-all resolver in `PublicController@permalinkResolve`.

Plain page/post slugs cannot collide across `cms_pages` and `cms_posts` (`CmsSlug::conflictsWithOtherContent`).

Published pages/posts may set a visitor password (`ContentPassword`, `password_hash`). Unlock: `POST /unlock/page/{id}` and `/unlock/post/{id}`. Editors with `edit_pages` / `edit_posts` bypass. Do not return hashes on the API.

### Comments

Public submit: `POST /comment/post/{id}`. Honeypot field `website`. Rate limit: `CommentRateLimit` counts submissions per IP in the last hour (`discussion_comment_rate_limit`, default 10; 0 = unlimited).

### Newsletter

Public form: `GET/POST /subscribe`, confirm `GET /subscribe/confirm/{token}`, unsubscribe `GET/POST /unsubscribe/{token}`. Widget type `newsletter`. Table `cms_newsletter_subscribers`. Default double opt-in via `NewsletterMail` / `Mailer::send`. Honeypot `website`, consent checkbox, per-IP/session rate limit. Admin: `/admin/subscribers`. Capabilities `view_subscribers`, `manage_subscribers`, `export_subscribers`.

### Backup / restore

- **Backup:** `php cli/backup.php` or System → Backup & Restore (full DB dump + uploads).
- **Restore:** CLI only — `php cli/restore.php --from=path/to.zip`. Includes all `cms_*` tables and `app_settings` (visitor password hashes and `cms_content_pass_key`; newsletter rows and `newsletter_*` keys).
- **Verify:** `npm run test:backup-restore` (when configured).

### Fresh-install truncate (destructive, never production)

```bash
php cli/truncate_fresh_install.php          # prompts YES
php cli/truncate_fresh_install.php --yes
php cli/truncate_fresh_install.php --yes --no-reseed    # empty content
php cli/truncate_fresh_install.php --yes --keep-uploads # leave public/uploads/media files
```

**Keeps:** `migrations`, `roles`, `role_capabilities`, `admin` user.  
**Clears:** all `cms_*` content tables, `app_settings`, notifications/audit/email/API sessions, `backup_archives` rows, leftover legacy PAPeR tables if present, and media files under `public/uploads/media/` (unless `--keep-uploads`).  
**Default reseed:** Welcome page, General category, Hello World post, Primary Menu (Home + Blog). ZIP files under `storage/backups/` are not deleted.

### Audit

`App\AuditLog::record($entityType, $entityId, $action)` on create/update/delete for pages, posts, comments, widgets, etc.

---

## 9. REST API

Registered in `public/index.php` under `/api/`. JSON envelope: `{ success, data, error }`.

| Endpoint | Auth | Purpose |
|----------|------|---------|
| `POST /api/auth/login` | — | Bearer token |
| `GET /api/pages`, `/api/posts`, `/api/media` | Bearer | List |
| `POST /api/pages`, `POST /api/posts` | Bearer (`add_*`) | Create (JSON body; optional `layout` / `layout_json`) |
| `PATCH /api/pages/{id}`, `PATCH /api/posts/{id}` | Bearer (`edit_*`) | Update (partial merge; optional layout) |
| `DELETE /api/pages/{id}`, `DELETE /api/posts/{id}` | Bearer (`delete_*`) | Soft-delete |
| `GET /api/system/general` | Bearer (admin) | Branding, SEO, reading, discussion, newsletter, permalinks |
| `GET /api/settings/ui` | Bearer | User UI prefs |

Postman: `docs/postman/Simple-CMS-API.postman_collection.json`.

---

## 10. Testing

### CLI smoke tests

```bash
php tests/cli/cms_wp_features_smoke_test.php
php tests/cli/cms_wp_extended_smoke_test.php
php tests/cli/cms_theme_style_pack_smoke_test.php
php tests/cli/cms_atom_import_smoke_test.php
php tests/cli/cms_pages_smoke_test.php
php tests/cli/cms_routes_smoke_test.php
php tests/cli/cms_rss_smoke_test.php
php tests/cli/cms_llm_discovery_smoke_test.php
php tests/cli/cms_media_responsive_smoke_test.php
php tests/cli/cms_revisions_redirects_search_smoke_test.php
php tests/cli/cms_builder_templates_write_api_smoke_test.php
```

Or: `npm run test:cms-wp-features`, `npm run test:cms-wp-extended`, `npm run test:cms-newsletter`, `npm run test:cms-rss`, `npm run test:cms-llm`, `npm run test:cms-media-responsive`, `npm run test:cms-inline-media`, `npm run test:cms-revisions-redirects-search`, `npm run test:cms-builder-templates-write-api`, `npm run test:cms-atom-import`.

### Playwright E2E

Copy `.env.playwright.example` → `.env.playwright` (gitignored). If that file is missing, Playwright also reads `env.playwright`.

```env
BASE_URL=http://cms.local
ADMIN_USER=admin
ADMIN_PASS=admin123
```

```bash
npm run test:e2e:cms          # headless (smoke + wp-extended + pulse UX + newsletter)
npm run test:e2e:cms:headed # watch mode
php cli/seed_filipino_men_site.php        # The Filipino Men chrome (pages/menu/theme; no essay import)
php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom   # Atom template (backdate + schedule; links use base_url)
npm run test:e2e:cms-pulse-ux             # Pulse widgets through password-protect (BASE_URL)
npm run test:e2e:cms-newsletter           # Subscribe form + admin list/confirm/delete (BASE_URL)
npm run test:e2e:cms-builder-tomeng       # sample post with all column layouts
npm run test:e2e:cms-builder-drag-column-size  # headed: drag reorder, width 1–12, edge resize, min-height
npm run test:e2e:cms-pages-layouts-menu   # sample pages (distinct layouts) + Primary Menu
npm run test:e2e:cms-team-pogi-site       # Team POGI site: 5 pages + 5 posts + free Picsum images + menu
npm run test:e2e:cms-tomeng-site          # Tomeng sample site (BASE_URL, skip migrate)
npm run test:e2e:cms-manhood-exclusive-rooms  # sample post with external image URL
```

---

## 11. Configuration

- **Database:** `config/database.php` from `config/database-sample.php`
- **Base URL:** `config/app.php` → `base_url` for subfolder installs (leave empty when the host is the site root, e.g. `http://cms.local`). The Atom importer rewrites internal links using this value so the same feed works on another host.
- **Document root:** The **project root** (this repo), not `public/`. Root `index.php` + `.htaccess` forward into `public/index.php` and block `config/`, `App/`, `Core/`, `storage/`, `logs/`, `cli/`, `database/`, `tests/`, `vendor/`. Static files stay at `/public/assets/…`.
- **Local XAMPP (`cms.local`):** Edit the **machine** Apache vhost (`C:/xampp/apache/conf/extra/httpd-vhosts.conf`) — `DocumentRoot` and `<Directory>` must be `C:/xampp/htdocs/cms` — plus Windows `hosts` (`127.0.0.1 cms.local`). Do **not** add a vhost file inside this repo. Restart Apache after changing the vhost. Playwright `BASE_URL` defaults to `http://cms.local`.
- **CORS:** `config/app.php` `cors.allowed_origins` includes `http://cms.local` for local API clients
- **The Filipino Men (local journal chrome):** `php cli/seed_filipino_men_site.php` or `npm run seed:filipino-men`. Applies the paper-journal pack, empty desks, and pages. Does **not** import `xyz/feed.atom` essays. Import from the template `docs/samples/cms-atom-import/sample.atom`, or a Blogger dump such as `xyz/feed.atom`, via `php cli/import_atom_feed.php` (or Posts → Import).

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
