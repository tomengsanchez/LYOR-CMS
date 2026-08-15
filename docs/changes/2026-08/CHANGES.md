# Simple CMS – Changes (2026-08)

Part of the [changes index](../CHANGES.md). Newest entries first within this month.

---

## Visual builder JS split + shortcuts (2026-08-15)

- Frontend editor JS is split under `public/assets/js/builder/` (`ns`, `history`, `model`, `canvas`, `layers`, `dnd`, `actions`, `panel`, `save`, `ui`, boot `editor.js`) so each area stays readable. Still external files only; `CmsBuilderApi` unchanged for Playwright.
- **?** (toolbar) lists shortcuts. Right-click a canvas block for Copy / Paste / Dup / Delete. Layers has a filter box. Zoom, device, and Layers stay for this browser tab (`sessionStorage` — not part of backup).
- Backup still `layout_json`. Help: Pages / Posts. Playwright opens the shortcuts dialog in `cms-builder-drag-column-size`.

---

## Visual builder layers, copy/paste, inline, autosave (2026-08-15)

- **Layers** tree (toolbar) jumps to any section/row/column/module; hover highlights the matching canvas block; × closes the tree. Click a heading or text module on the canvas to edit in place.
- **Copy** / **Paste** toolbar plus Ctrl/Cmd+C / X / V (in-memory only — not the OS clipboard). **+** after a module inserts into that column; filter the module picker. Alt+arrows nudge order. Canvas zoom 85/100/115%. Autosave ~12s when dirty (same Save POST); deferred while typing and does not rebuild the canvas.
- In-memory clipboard only. No schema change; backup still `layout_json`. Help: Pages / Posts. Playwright layers + Copy/Paste enable in `cms-builder-drag-column-size`.

---

## Visual builder editor UX (2026-08-15)

- Undo / Redo in the frontend editor toolbar (Ctrl/Cmd+Z, Ctrl/Cmd+Y, Ctrl/Cmd+Shift+Z). History is layout JSON only — not a schema change; backup still dumps `layout_json`.
- Inspector **Section / Row / Col** crumbs, always-visible dashed outlines, chrome padding on hover (does not cover headings), duplicate row/column, unsaved **Save •**, keyboard: Ctrl/Cmd+S, Ctrl/Cmd+D, Escape, Delete.
- Help: Pages / Posts. Playwright `cms-builder-drag-column-size` covers undo/redo of the width slider.

---

## Visual builder drag reorder + column size (2026-08-15)

- Drag the **⋮⋮** handle to reorder sections, rows, columns, and modules (including moving a module into another column). Arrow buttons remain.
- Column width is the full Bootstrap **1–12** grid (slider in the inspector). Drag the edge between two columns to split their widths without changing the pair total.
- Column **min height** (`px` / `rem` / `%` / `vh`) and vertical align (top / middle / bottom). Stored in existing `layout_json` settings — included in backup SQL.
- Smoke: `tests/cli/cms_layout_builder_smoke_test.php` (width 5, min-height, valign). Playwright headed demo: `npm run test:e2e:cms-builder-drag-column-size` (`tests/e2e/cms-builder-drag-column-size.spec.ts`). Help: Pages / Posts.

---

## Visual builder module designers (2026-08-15)

- Layout builder inspector improved: module picker descriptions, Content/Design/Advanced hints, `#hex` color pickers, spacing/font presets, media thumbnails, live Custom HTML preview, unique field IDs, checkbox labels, tab ARIA.
- Canvas: links do not navigate away, chrome buttons have accessible names, confirm before delete, unsaved-change warning, typing does not rebuild the canvas on every key, Device preview dims hide-on-mobile / hide-on-desktop modules.
- Button and CTA: shared styles (primary / secondary / outline) and optional **Open in new tab** (`target=_blank` + `rel=noopener`). Existing CTA modules without `style` stay primary. Blurb canvas preview now shows the library image like the public site. Divider uses `currentColor` so Design → Text color tints the line.
- Backup/restore: still `layout_json` in the SQL dump; extra keys are optional JSON. Smoke: `tests/cli/cms_layout_builder_smoke_test.php`. Help: Pages / Posts.

---

## Manly bundled style pack (2026-08-15)

- New bundled CMS style pack **Manly** (dark oak / leather, copper accent, serif, magazine chrome).
- Install from Customize / General: **Manly (oak, iron, leather)**. Download: `/admin/customize/sample-style-pack?pack=manly`.
- Source: `docs/samples/cms-style-pack-manly/`. Zip: `cms-style-pack-manly.zip` in `docs/samples/` and `public/assets/theme-packs/`. Rebuild: `php cli/build_style_pack.php manly`.
- Backup/restore: installed CSS under `public/uploads/theme-packs/library/`; bundled starter zip is committed with assets (not the live library copy).

---

## Theme style-pack upload (WP-inspired) (2026-08-15)

- Admins can upload a **CMS style pack** zip (`cms-theme.json` + optional `extra.css`) or a WordPress theme zip (colors/metadata only; PHP ignored).
- **Pack library:** uploads are stored under `public/uploads/theme-packs/library/{id}/`; select & **Activate** from Customize / General. Bundled packs install into the library. Clear active keeps installed packs.
- Settings map into existing `pub_theme_*` / accent via `PublicTheme::saveConfig`; active CSS linked from the public layout.
- UI: Appearance → Customize and System → General; export current pack; clear active / remove from library.
- Backup/restore: CSS via uploads tree; metadata via `app_settings` (`pub_theme_pack_library`, active id). **Bundled zips:** `cms-style-pack-example.zip`, `cms-style-pack-play-build-sound.zip`, and `cms-style-pack-manly.zip`. Rebuild: `php cli/build_style_pack.php`. Smoke: `tests/cli/cms_theme_style_pack_smoke_test.php`.

---

## Media external URL + caption; Manhood Exclusive Rooms post (2026-08-15)

- Migration **022**: `cms_media.source_url` and `cms_media.caption` for free/external images without re-upload.
- Media library: **Register URL** form; `/serve/media` and `/share/media` redirect external items to the source URL.
- Layout Builder image modules already support `url` + `caption` (figcaption); public CSS for `.cms-mod-image-caption`.
- CSP default `img-src` includes `https:` so hotlinked free images (e.g. Unsplash) are not blocked.
- Playwright: `tests/e2e/cms-manhood-exclusive-rooms.spec.ts` publishes a short Recovering Biblical Manhood post using an Unsplash URL (no download).
- Backup/restore: new columns included in SQL dump of `cms_media`. Help updated.

---

## Editorial magazine options (no brand lock-in) (2026-08-15)

- Removed Good Men Project branding from preset/pack labels. Reusable options kept: **Editorial (crimson)** preset, editorial chrome, blog kicker, readable dates, company tagline, and **Apply editorial style pack**.
- Legacy stored preset `goodmen` maps to `editorial`. Legacy POST key `pub_theme_apply_goodmen_pack` still accepted.

---

## Good Men Project–style editorial theme (2026-08-15)

- *(Superseded)* Originally introduced editorial magazine features under Good Men naming; see entry above for the generic rename.

---

## AI search & citation settings (2026-08-15)

- **General → SEO:** New **AI search & citation** block — publisher expertise (E-E-A-T), preferred citation, citation guidance, topic clusters/pillar subjects, Reddit/YouTube `sameAs` URLs, FAQPage/speakable toggles, and editor writing tips.
- **Pages & posts:** `citation_snippet` (BLUF) and `faq_json` (migration **021**); public lead paragraph `.cms-ai-answer`; `meta name="citation"`; Schema.org speakable/abstract + optional FAQPage; JSON exports and llms.txt prefer citation snippets.
- **Backup/restore:** Covered via `app_settings` + new CMS columns in SQL dump. Help, CONFIGURATION, DEVELOPMENTGUIDE, Postman site.json notes, smoke test updated.

---

## Fix `/blog/{slug}.json` routing (2026-08-15)

- Registered `.json` page/post routes **before** `/p/{slug}` and `/blog/{slug}` so they are not swallowed as HTML slugs.
- Router `pathToRegex` now `preg_quote`s static path segments (literal `.json`).

---

## Blog list grid / list + customization (2026-08-15)

- Blog listing layouts: **list**, **grid**, **cards**, **magazine**, **compact** (legacy `stacked` maps to list).
- Extra options: grid columns (2/3/4), image ratio, show excerpt / read more / category, optional **visitor view switcher** (localStorage).
- Controls in Appearance → Customize and System → General; public CSS classes + `blog-view.js`.

---

## Public edit bar for editors (2026-08-15)

- Logged-in users with `edit_pages` / `edit_posts` see a public edit bar: **Edit via Frontend editor** + **Edit in admin**.

---

## No duplicate title/image on layout posts (2026-08-15)

- Public post view skips automatic `<h1>` and featured image when a visual `layout_json` is present (builder content owns title/media). Featured image still used on blog list, SEO, and social cards.

---

## Carousel layout-builder module (2026-08-15)

- New visual builder module **Carousel**: up to 12 slides (media library or URL), captions, optional links, autoplay / interval / arrows / dots.
- Public: `carousel.js` + `.cms-carousel*` styles; respects `prefers-reduced-motion`.
- Editor: multi-slide side panel with upload per slide. Smoke test covers normalize/render/plain text.

---

## Hide public Admin button by default (2026-08-15)

- Public header/footer **Admin** login links are off by default (`pub_theme_show_admin_link`). Migration **020** turns the setting off for existing sites. Can still be enabled under Appearance → Customize or System → General.

---

## Page title not in content + full width default (2026-08-15)

- Public pages no longer render an automatic `<h1>` for the page title in the body (title remains in browser tab, SEO, menus, breadcrumbs). Headings come from the visual builder.
- Added **Full width (1320px)** content width; site default is now **full** (was narrow). Migration **019** upgrades stored `pub_theme_width` from empty/narrow to `full`.

---

## Team POGI site Playwright seed (2026-08-15)

- Added `tests/e2e/cms-team-pogi-site.spec.ts`: downloads free Lorem Picsum photos, uploads to Media, publishes **5 pages** + **5 posts** with visual layouts/featured images, rebuilds Primary Menu, sets site name and homepage to Team POGI.
- Run: `npm run test:e2e:cms-team-pogi-site`.

---

## Fresh-install truncator for Simple CMS (2026-08-15)

- `cli/truncate_fresh_install.php` now clears all `cms_*` content tables (plus ops tables), optional media files under `public/uploads/media/`, and still skips missing legacy PAPeR tables.
- Default reseed: Welcome page, General category, Hello World post, Primary Menu (Home + Blog). Flags: `--no-reseed`, `--keep-uploads`.
- Docs: DEVELOPMENTGUIDE, RUNBOOK, FrameworksGuide, dev-help, backup-restore help.

---

## Playwright sample pages + Primary Menu (2026-08-15)

- Added `tests/e2e/cms-pages-layouts-menu.spec.ts`: publishes About / Services / Features / Team / Contact with distinct visual builder layouts and content widths, then rebuilds the Primary Menu (Home, Blog, those pages).
- Run: `npm run test:e2e:cms-pages-layouts-menu` (headed; uses `.env.playwright` `BASE_URL`).
- **Bugfix:** Page create/edit form closed the Content card before SEO/LLM/Save, so browsers ended the `<form>` early and **Save did nothing**. Outer card now wraps the full form (same pattern as posts).

---

## More Customizer options (round 3) (2026-08-15)

- Presets: Coral, Mint.
- Button size, sidebar style, footer alignment, prose align, logo size, motion, focus ring.
- Blog toggles: search box, post dates, list featured images.

---

## More Customizer options (round 2) (2026-08-15)

- Presets: Teal, Charcoal.
- Nav style (inline/pills/underline), brand weight, heading scale, blog list (stacked/cards/compact).
- Featured image style, card borders, header height.
- Toggles: site title beside logo, breadcrumbs, uppercase nav.

---

## More Customizer / theme options (2026-08-15)

- Added Amber & Indigo presets; Readable & Display fonts; square & XL radius.
- New controls: font size, line height, footer style, button style, card shadow, content spacing, link style, sticky header, show/hide Admin login links.
- Available in Appearance → Customize and System → General; CSS classes on public `<html>`.

---

## Theme Customizer (live preview) (2026-08-15)

- **Appearance → Customize** (`/admin/customize`): WordPress-style sidebar + public iframe.
- Live updates via `postMessage` (`theme-preview-bridge.js`) and session sync (`POST /admin/customize/preview`).
- **Publish** writes `PublicTheme` settings; **Close** clears unsaved preview.
- General still has the same fields plus an **Open Customizer** entry point.

---

## Public color mode admin-only (2026-08-15)

- **Removed** the public header light/dark toggle and browser `localStorage` override.
- **Color mode** (light / dark / system) is set only under **System → General → Public site theme**.
- Updated help, E2E smoke, and `theme.js`.

---

## Builder device preview, layout templates, write API (2026-08-15)

- **Device preview:** Visual builder toolbar Desktop / Tablet / Mobile canvas widths.
- **Layout templates:** Migration **018** `cms_layout_templates`; save/load/delete from builder Templates menu (`App\Models\LayoutTemplate`).
- **Write REST API:** `POST` / `PATCH` / `DELETE` on `/api/pages` and `/api/posts` (Bearer + add/edit/delete capabilities); optional `layout` / `layout_json`.
- **Test:** `tests/cli/cms_builder_templates_write_api_smoke_test.php` (`npm run test:cms-builder-templates-write-api`).

---

## Revisions, redirects, content library search (2026-08-15)

- **Versioning:** `cms_content_revisions` snapshots on page/post update and layout save (max 50); restore from page/post view.
- **Redirects:** Admin CRUD at `/admin/redirects` (301/302); applied on public 404 / missing page/post slugs.
- **Library search:** `/admin/search` + header box across pages, posts, and media.
- **Migration 017**; smoke `tests/cli/cms_revisions_redirects_search_smoke_test.php`.

---

## Divi-style frontend visual builder (2026-08-15)

- **layout_json:** Migration **016** adds `layout_json` on `cms_pages` / `cms_posts` (keeps existing `blocks_json` block builder).
- **Editor:** `/admin/builder/page|{post}/{id}` with Section → Row → Column → Module canvas; Phase 1 modules (heading, text, image, button, CTA, spacer, divider, HTML, blurb) + Content/Design/Advanced panel.
- **Column picker:** Row Content panel (and row chrome **Columns**) offers 1–4 equal and common split layouts (e.g. 8/4, 3/6/3); preserves modules when resizing.
- **Module picker:** Side-panel type buttons (no browser `prompt`); empty columns show **+ Add module**; modules support ↑ ↓ Dup; sections/rows move ↑ ↓; Ctrl/Cmd+S saves.
- **Save:** `POST /admin/builder/.../save` (AJAX + CSRF); entry button **Edit via Frontend editor** on page/post forms.
- **Public:** Prefer visual layout, else blocks, else body (`ContentBlocks::renderEntity` / `LayoutBuilder`).
- **Test:** `tests/cli/cms_layout_builder_smoke_test.php` (`npm run test:cms-layout-builder`).

---

## Inline media on page/post forms (2026-08-15)

- **Upload in place:** Featured image and block-builder image blocks have **Upload image** (AJAX `POST /admin/media/upload-json`) so editors need not visit Media first.
- **Posts:** Tag chips to add existing tags; **Add category** quick-create (`POST /admin/categories/quick-store`).
- **Permissions:** Content editors with add/edit pages or posts may upload while editing (plus `upload_media`).
- **JS:** `public/assets/js/content/media-picker.js` + updated `blocks.js`; CSS `admin/content-editor.css`.
- **Test:** `tests/cli/cms_inline_media_picker_smoke_test.php`.

---

## Responsive media sizes (2026-08-15)

- **WordPress-style sizes:** On JPG/PNG/WebP upload, GD generates `thumbnail` (150×150 crop), `medium` (300), `medium_large` (768), `large` (1024), `1536x1536`, `2048x2048` under `public/uploads/media/` (tracked in `cms_media_sizes`, migration **015**).
- **Serving:** `/share/media/{id}/{size}` (public) and `/serve/media/{id}/{size}` (admin); falls back to full original.
- **Public HTML:** Featured images and block images emit `srcset`/`sizes` via `Media::responsiveImg` / `MediaImageSizes`.
- **CLI backfill:** `php cli/regenerate_media_sizes.php` (optional `--id=N`).
- **Test:** `tests/cli/cms_media_responsive_smoke_test.php`.

---

## LLM discovery, post SEO, AI crawlers (2026-08-14)

- **llms.txt:** `/llms.txt` and `/llms-full.txt` (llmstxt.org) list pages, posts, categories, and machine-readable endpoints. Toggle in General → SEO.
- **AI crawlers:** `robots.txt` allows or blocks GPTBot, ClaudeBot, Perplexity, and related bots; Disallow `/admin` and `/api`.
- **Post SEO:** Per-post SEO title, meta description, LLM summary, and `noindex` (migration **014**). BlogPosting JSON-LD, article Open Graph tags, author/section/tags.
- **Discoverability:** `/blog.json` catalog; sitemap includes categories/tags plus `changefreq`/`priority`; SearchAction on WebSite schema; breadcrumb JSON-LD; `html lang` from locale; Facebook/LinkedIn `sameAs`.
- **Help / docs / Postman / test:** `tests/cli/cms_llm_discovery_smoke_test.php`.

---

## CMS guide, comment rate limit, widget drag-drop (2026-08-14)

- **DEVELOPMENTGUIDE.md:** Rewritten for Simple CMS only (modules, migrations 000–013, settings, testing).
- **Comments:** Per-IP rate limit (`CommentRateLimit`, General → Discussion); configurable max/hour (0 = unlimited).
- **Widgets:** Drag-and-drop reorder in admin (`widgets/form.js`, `admin/widgets.css`).
- **Help:** Updated comments and widgets help text.

---

## RSS feed + E2E/dashboard polish (2026-08-14)

- **RSS:** `/feed.xml` and `/feed` — latest published posts; toggle in General → SEO; `<link rel="alternate">` on public pages.
- **Dashboard:** Recent draft pages/posts; pending comments preview; quick links to menus/widgets/tags.
- **E2E:** `global-setup.ts` CMS-only (no PAPeR seeders); `test:e2e` runs CMS specs; updated `.env.playwright.example`.
- **Postman:** Expanded `Simple-CMS-API.postman_collection.json` (system general, public JSON/RSS/sitemap).
- **Test:** `tests/cli/cms_rss_smoke_test.php`.

---

## WordPress-like extended features (2026-08-14)

- **Migration 013:** `cms_comments`, `cms_widgets`, `blocks_json` on `cms_pages` / `cms_posts`.
- **Comments:** Public form on posts; moderation queue at `/admin/comments`; settings in General → Discussion.
- **Widgets:** Sidebar and footer areas at `/admin/widgets`; public sidebar when enabled in Discussion settings.
- **Permalinks:** Page/post URL patterns in General → Permalinks; catch-all resolver + `App\Permalink` for menus, blog links, sitemap.
- **Block builder:** Admin UI on page/post forms (`content/blocks.js`); public render via `App\ContentBlocks`.
- **Test:** `tests/cli/cms_wp_extended_smoke_test.php`.

---

## WordPress extended polish (2026-08-14)

- **Blog search:** Widget and `/blog?q=` filter posts by title, excerpt, and body.
- **SEO URLs:** Open Graph / JSON-LD / sitemap use `Permalink` (not hardcoded `/p/` or `/blog/`).
- **Comment replies:** Threaded replies on public posts; honeypot spam field; dashboard pending count.
- **JSON exports:** Block plain text + structured `blocks` in page/post JSON; `/blog/{slug}.json` route.
- **Slug safety:** Cross-table page/post slug conflict validation on save.
- **API:** `/api/system/general` includes reading, discussion, and permalink settings.
- **E2E:** `tests/e2e/cms-wp-extended.spec.ts`; npm script `test:e2e:cms`.
- **Docs:** `docs/DevelopmentHistory/8.14.2026-wp-extended-features.json`.

---

## Theme design (2026-08-14)

- **Admin theme:** External `public/assets/css/layout/admin.css` — sidebar accent, 5 color variants, dashboard stat cards, mobile drawer.
- **Public theme:** Shared `public/layout.php` + `public/assets/css/public/site.css` — sticky header, article typography, blog cards, footer.
- **Auth pages:** `public/assets/css/layout/auth.css` for login/2FA.
- **JS:** `admin-sidebar.js` for mobile menu toggle.
- **Help modal:** Restored Bootstrap modal in admin layout; `/admin/help` links open in-app via `help-modal.js`.
- **Branding:** Logo on admin sidebar, login, and public header; favicon from uploaded logo.
- **Public theme customizer:** System → General — presets, accent, typography, layout, default color mode (`App\PublicTheme`, `themes.css`).
- **Homepage:** Published `welcome`/`home` pages use hero + card layout on the public site.
- **Auth polish:** Shared auth head partial; login/2FA use branding logo, favicon, and public accent on buttons.
- **Public blog/post:** Blog hero header, card layout, post meta (date/category); primary buttons use accent color.
- **Test:** `tests/cli/cms_branding_smoke_test.php` for accent normalization.
- **Public dark mode:** Header toggle + `public/assets/js/public/theme.js` (localStorage + system preference); E2E in `cms-smoke.spec.ts`.
- **Admin lists:** Table header styling, row hover, compact action buttons.
- **Admin dark mode:** Per-user light/dark/system under Settings → UI (`ui-color-*` classes in `admin.css`).
- **Help cleanup:** Legacy PAPeR help partials moved to `App/Views/help/pages_legacy/`; CMS help stubs added.

---

## Post featured images & social sharing (2026-08-14)

- **Migration 009:** `cms_posts.featured_image_id` (FK → `cms_media`); `cms_media.width` / `height` on upload.
- **Admin:** Post create/edit — featured image picker from Media library; size hints for 1200×630 social preview.
- **Public:** Featured image on blog list and post page; `/share/media/{id}` serves images publicly for crawlers.
- **Social meta:** Open Graph + Twitter Card tags on blog/post pages (`App\SocialShare`, `public/partials/social_meta.php`).
- **Test:** `tests/cli/cms_posts_featured_smoke_test.php`.

---

## Page SEO & LLM-friendly output (2026-08-14)

- **Migration 010:** `featured_image_id`, `llm_summary`, `robots_noindex` on `cms_pages`.
- **Admin:** Pages form — SEO title/description (char hints), featured image, noindex, LLM summary.
- **Public:** Open Graph + Twitter + canonical + robots + `meta abstract`; Schema.org JSON-LD; `link rel=alternate` JSON.
- **Machine-readable:** `GET /p/{slug}.json`, `GET /index.json` (homepage); `GET /sitemap.xml`, `GET /robots.txt`.
- **Helper:** `App\PublicSeo` — share meta, JSON-LD, LLM JSON document.
- **Test:** `tests/cli/cms_pages_seo_smoke_test.php`.

---

## WordPress-like CMS features (2026-08-14)

- **Reading settings** (System → General): homepage = static page or latest posts; posts per page; paginated `/blog`.
- **Navigation menus** (Content → Menus): primary header menu — pages, posts, categories, custom URLs.
- **Post tags:** comma-separated on post form; admin **Content → Tags**; archives at `/blog/tag/{slug}`.
- **Category archives:** `/blog/category/{slug}` with pagination.
- **Page hierarchy:** parent page on page form; breadcrumbs on public child pages.
- **Migration 012:** `cms_menus`, `cms_menu_items`, `cms_tags`, `cms_post_tags`, `cms_pages.parent_id`.
- **Test:** `tests/cli/cms_wp_features_smoke_test.php`.

---

- **`App\PublicTheme`** — presets (Default, Ocean, Forest, Sunset, Violet, Slate, Rose, Custom), accent, font, radius, content width, default color mode, header style, show/hide theme toggle.
- **System → General:** Theme section with preset swatches, live preview (`theme-customizer.js`), and save via existing General form.
- **Public site:** `public/assets/css/public/themes.css` + classes on `<html>`; `theme.js` respects admin default color mode and toggle visibility.
- **Live site preview:** **Preview on live site** stores unsaved theme in session; visit `/?theme_preview=1` while logged in as admin (banner + exit control).
- **Blog listing width:** Separate from site content width (`pub_theme_blog_width`: inherit / narrow / normal / wide).
- **Per-page/post width:** `content_layout` on `cms_pages` and `cms_posts` (migration 011).
- **Auth pages:** Login and 2FA use the same theme presets, fonts, radius, accent, and default color mode (`themes.css` + `auth.css`).
- **API:** `public_theme` object on `GET /api/system/general`.
- **Test:** `tests/cli/cms_public_theme_smoke_test.php`.

---

## Site-wide SEO & LLM (General settings) (2026-08-14)

- **System → General:** Title suffix, default meta description, default OG image, Twitter handle, Google verification, locale, keywords, LLM site summary, JSON export + sitemap toggles.
- **`App\Models\AppSettings::getSiteSeoConfig()`** / **`saveSiteSeoConfig()`** — stored in `app_settings`.
- **Public:** Fallbacks via `SocialShare` / `PublicSeo`; **`GET /site.json`** for site-level LLM document.
- **Test:** `tests/cli/cms_site_seo_smoke_test.php`.

---

## Admin under `/admin`; public site at `/` (2026-08-14)

- **Public homepage:** `GET /` renders the published page with slug `welcome` (or `home`), with fallback landing template.
- **Admin panel:** All authenticated UI moved under `/admin` (dashboard, login, CMS modules, settings, system, users).
- **Public routes unchanged:** `/p/{slug}`, `/blog`, `/blog/{slug}`.
- **Helper:** `App\AdminPath` and global `admin_url()` for consistent admin links.
- **Tests:** E2E auth and smoke tests updated for `/admin/login`.
- **Legacy URLs:** `GET /login`, `/pages`, `/help`, etc. **301 redirect** to `/admin/...` (`LegacyRedirectController`).
- **Help modal JS:** `help-modal.js` fetches `/admin/help/fragment` (still intercepts old `/help` links).
- **Bugfix:** Repaired broken `admin_url('path/<?= … ?>')` in list/action views from bulk patch; login page rebranded to Simple CMS.
- **Polish:** API notifications return `/admin/notifications/click/{id}` URLs; layout header Notifications link; CMS admin guide rewrite; PAPeR branding cleanup in settings/emails.
- **Backup manifest:** New ZIPs use app id `SimpleCMS`; restore accepts legacy `PAPeR` archives too.

---

## Simple CMS reset (2026-08-14)

- **Major reset:** PAPeR domain modules (Profile, Structure, Grievance, Library, SES, RAP, etc.) removed.
- **New CMS modules:** Pages, Posts, Categories, Media library with admin CRUD and public routes (`/p/{slug}`, `/blog`, `/blog/{slug}`).
- **Fresh migrations:** 100 legacy migrations archived to `database/migrations_legacy/`; new migrations `000`–`007` for core admin + CMS tables.
- **Retained:** Auth (2FA), users/roles, settings, backup/restore, audit trail, notifications, REST API (auth + CMS read endpoints).
- **Removed:** Live traffic, API clients gate, project scoping, domain CSV imports, mobile domain API surface.
- **Setup:** Use a new empty database and run `php cli/migrate.php`. Default login: `admin` / `admin123`.
- **Follow-up:** Added `migration_008_user_dashboard_config`; simplified notifications, audit trail, and PDF export for CMS.

---

## Per-project CSV export ZIP (2026-08-14)

- **System → Project CSV Export** (admin) creates a ZIP under `storage/project-csv-exports` for one Library project: profiles, structures, grievances (+ status log, attachments, respondents, tags), **latest SES sections** per profile (`profile_socio_sections.csv`), `audit_log` for those entities, project notifications, linked users (no password hashes), sessions, and API token metadata (no secrets).
- Optional **Redact personal information** (UI checkbox / CLI `--redact`): replaces **person names and birthdays only** with `[REDACTED]` (SES/audit JSON uses the same rules; includes household member `Name` / `Birthday` columns; excludes Project Name, crop/business names, GPS, contact, address, email); `manifest.json` records `pii_redacted`.
- Adds **`municipalities.csv`** / **`barangays.csv`** lookup tables for IDs in exported rows.
- CLI: `php cli/export_project_csv.php --project-id=N` (optional `--out=`, `--redact`). Core: `App\ProjectCsvExporter`.
- Fix: `profile_structure_tags` export orders by `profile_id, sort_order, structure_tag` (table has no `structure_id`).
- Default export ZIP filename uses project name slug (not numeric id): `paper-project-{name}[-redacted]-timestamp.zip`.
- No schema change; backup/restore unchanged. Help + smoke test: `npm run test:export-project-csv`.

---

## System CSV Templates + strict import preview (2026-08-14)

- **System → CSV Templates** (`/system/csv-templates`) lists Profile / Structure / Grievance sample CSVs with **Required** / **Needs clarification** / **Optional** column guides (`App\CsvImportTemplates`). Nav under System → Data; access: admin or `add_profiles` | `add_structure` | `add_grievance` (download needs matching add cap).
- Shared contract: `App\CsvImportSupport` (header normalize, MIME checks, statuses `ready_new` / `ready_update` / `failed` / `needs_clarification`, max **2000** rows). Match prefer numeric `id`, else natural keys.
- **Profile:** `ProfileCsvImporter` + `POST /profile/import/preview`; list modal Preview then Import (`public/assets/js/profile/import.js`). Template/importer map invitation card (RSVP, **1st/2nd invitation visit**, distribution status), legacy `visit_1`–`visit_3`, address, ownership, civil status, and representative fields.
- **Structure:** new import (`StructureCsvImporter`, sample/preview/import routes, list modal + `structure/import.js`).
- **Grievance:** existing case # / `id` → **update** (no longer skip-only); preview labels New / Update / Failed / Needs clarification.
- Backup/restore unchanged (no schema change). Help, DEVELOPMENTGUIDE, samples README, E2E: `npm run test:e2e:csv-templates-import` / `:fast` (+ grievance import GI-04 update path). Postman N/A (web-only).

---

## Grievance CSV import update path (2026-08-14)

- Grievance CSV import no longer skips existing case numbers: match prefers `id`, then existing `grievance_case_number` → **update**; otherwise **create**.
- Row statuses align with Profile/Structure (`ready_new` / `ready_update` / `needs_clarification` / `failed`); unknown name lookups need clarification; hard validation stays failed. Cap 2000 rows; `CsvImportSupport::buildResult` + column guide.
- Commit: insert needs `add_grievance`; update needs `edit_grievance` (endpoint still gated by `add_grievance`). Backup/restore unchanged (no schema change). Help, sample README, and E2E updated.

---

## Structure CSV import (2026-08-14)

- Structure list **Import** (requires `add_structure`) mirrors Profile CSV preview/commit: match by `id` → `strid` → project + `structure_tag`; unknown/ambiguous names → needs clarification (no auto-create of municipalities/options).
- Endpoints: `GET /structure/import/sample`, `POST /structure/import/preview`, `POST /structure/import` (`structures_file`). Updates need `edit_structure`; project must be in UserProjects scope.
- Sample at `public/samples/structure-import-sample.csv` (also via System → CSV Templates). Backup/restore unchanged (no schema change). Help updated.

---

## Structure visitation witnesses (2026-08-14)

- Each visit card (1st / 2nd / 3rd) on Structure create/edit/view/PDF now has **two witnesses** (name, position and organization, date of visitation) plus **remarks**.
- Migration **097** adds the witness columns. Legacy `date_first_visit` / `date_second_visit` / `date_third_visit` are **kept** and synced from that visit’s **witness 1 date** on save (list date filters / CSV / older clients).
- Secondary structures: manual **Copy visitation from primary** (no auto-copy); remarks get an *Adapted/inherited from primary* indicator and remain editable.
- Select Columns / list / export / `GET|POST` structure API include the new fields. Backup/restore unchanged (additive columns). Help and Postman updated.
- E2E: `npm run test:e2e:structure-visitation-witnesses` (headed slow) / `:fast` — create primary with witnesses, secondary copy-from-primary, list smoke (`BASE_URL` configurable).

---

## Profile list: Address, Representative, related PN numbers (2026-08-14)

- Profile **Select Columns** / list / CSV / PDF include **Address**, **Representative information** (name + contact numbers), and **Related Structure PN numbers** (multi-value, comma-separated from linked structures).
- Derived via `Profile::enrichListDerivedFields()` after `listPaginated`; searchable and **sortable** via column headers. No schema change; backup/restore unaffected.
- Help and API contract updated (`GET /api/profile/list` returns the same derived fields).

---

## Backup `--no-uploads` flag (2026-08-13)

- **Exclude uploads** from System → Backup/Restore now passes `--no-uploads` correctly (it previously became `----no-uploads` and was ignored).
- CLI backup prints `Uploads: skipped (--no-uploads)` and no longer claims the ZIP contains uploads when they were excluded.
- Restore CLI uses the same flag parser. Test: `npm run test:backup-no-uploads`. Help/README updated.

---

## Structure list GPS columns (2026-08-12)

- Structure **Select Columns** / list / CSV / PDF include **GPS latitude** and **GPS longitude**.
- List cells are clickable and open the same satellite map dialog as Structure view (via `gps-map-dialog.js`).
- Help updated. Backup/restore unaffected (existing columns only).

---

## Users & Account access dashboard (2026-08-11)

- **Users** landing shows a **Currently online** dashboard (web + API KPIs and who is online).
- **Users → View** adds **Access & activity**: web sessions, API/mobile tokens, recent audit actions. Managers with `edit_users` can end a session or revoke a token (scoped to that user; audited as `revoked_by_manager`).
- **Account → Access & activity** (formerly Active sessions) adds the same panels for yourself, including **Revoke token**. My Profile shows a short access summary + link.
- Shared builder `App\UserAccessDashboard`; helpers `UserSession::listForUser` / `ApiToken::listForUser` / ownership revoke. Activity cards open a paginated dialog (`GET /api/users/{id}/access/activity`); web sessions and API tokens tables are paginated. No schema change; backup/restore unchanged.

---

## Realtime Dashboard API / mobile presence (2026-08-11)

- **System → Realtime Dashboard** shows **API / mobile** presence separately from web sessions: KPIs `api_active_users` / `api_active_tokens`, table of active Bearer tokens (`api_tokens.last_used_at` within the same ~10 minute window).
- Optional enrichment from latest `api_client_events` (client id, device model/OS, path, IP) when logging/migrations are present.
- Admin **Revoke token**: `POST /api/system/realtime-dashboard/tokens/{id}/revoke` (CSRF); audit `api_token` / `revoked_by_admin`. Web **End session** unchanged.
- Help, API contract, Development guide, Postman updated. Backup/restore unchanged (uses existing `api_tokens` / events tables).

---

## API Clients dashboard, security logs & usage (2026-08-11)

- **System → API Clients** landing page is the **Dashboard** (KPIs, requests-by-day, top clients/users/projects, recent auth fails).
- **Clients** at `/system/api-clients/clients` — gate, credentials, and logging on/off + retention days.
- **Security logs** — `auth_fail` events (`UNAUTHORIZED_CLIENT`); filter by client, **user**, **project**, days, path/IP/message; Prev/Next + page jump + per-page.
- **Usage analytics** — `request` events with breakdowns per client / user / project; dropdown filters show **name (#id)**; request log shows separate User ID / Project ID columns; same pagination controls.
- Listings **exclude local API data by default** (web UI `web_session` traffic and loopback IPs). Admins can check **Include local API data** on Dashboard / Security logs / Usage when needed.
- Event logs store **device model** + **OS version** (migration **096**): prefer `X-Device-Model` / `X-Device-OS` (or `X-OS-Version`); otherwise parse `User-Agent`. Shown on Dashboard / Security logs / Usage — not a Clients CRUD field.
- Migration **095** creates `api_client_events`; logging defaults on. Opportunistic prune by retention. Backup/restore includes the table via full DB dump.

---

## API client gate for mobile/frontend (2026-08-11)

- Optional **API clients** under **System → API Clients** (Dashboard / Clients / Security logs / Usage analytics). Create named clients, copy secret once, enable/disable/regenerate/delete per client.
- When the gate is on, non-session `/api/*` requests require `X-App-Id` + `X-App-Secret` (`App\ApiClientGate`). Web UI session exempt; `GET /api/meta/error-codes` public.
- Secrets hashed in `app_settings` (`api_clients_json`). Error code **`UNAUTHORIZED_CLIENT`**. CORS sample allows the new headers. Default gate **off** (backward compatible).
- Capabilities `view_api_clients` / `manage_api_clients` (migration **094**).
- Backup/restore includes `app_settings` (treat backups as sensitive). Fresh-install truncate clears clients — recreate after reset.

---

## Structure PN number + Other details under tagging (2026-08-10)

- Optional **`pn_number`** text field after **Location of the structure** (migration **093**, web form/view/PDF, list export, API).
- **Other details** moved into Structure tagging information immediately after **Description**; removed the empty **Detailed Measurements** tab.
- Backup/restore unchanged (full-table dump; migrate after restore adds the column).

---

## Structure GPS map links open in satellite view (2026-08-10)

- Clicking GPS coordinates on Structure view (and the form map preview) opens a **dialog** with an embedded satellite map; **View full on new page** opens Google Maps (`t=k`, zoom 18) in a new tab.
- Shared partial `_gps_map_modal.php` + external `gps-map-dialog.js`. Helpers: `Structure::googleMapsSatelliteUrl()` / `googleMapsSatelliteEmbedUrl()`.
- Default CSP (`Core\SecurityHeaders`) adds `frame-src` for `maps.google.com` and `www.google.com` so the embed is not blocked (`ERR_BLOCKED_BY_CSP`).

---

## List Back remembers page and filters (2026-08-10)

- Visiting Profile / Structure / Grievance lists stores the current list URI (page, search, filters) in the session (`App\ListReturn`).
- **Back** on view/create/edit returns to that list context so sequential encoding stays on the same page.
- Allowlisted relative paths only (no open redirects). Playwright `tests/e2e/list-return.spec.ts` + `npm run test:e2e:list-return:fast`.
- Also include `entity_type` in Profile list pagination query extras.

---

## Fix Structure Options Flash::set 500 (2026-08-10)

- Structure Options Library store/update/delete called undefined `Flash::set()` → HTTP 500 after successful insert.
- Switched to `Flash::success()` / `Flash::error()` like the rest of the app.
- Playwright `tests/e2e/structure-options-library.spec.ts` + npm `test:e2e:structure-options-library:fast` (BASE_URL configurable).

---

## Structure Actual Usage — free-text with suggestion store (2026-08-10)

- **Actual usage** is free text again (not a closed dropdown). The browser suggests prior values via `<datalist>` from `structure_actual_usages`.
- Saving a structure create/update (web or API) auto-stores new non-blank usages in that table (`StructureActualUsage::remember`, case-insensitive).
- Options Library → Actual Usage remains for reviewing/editing/deleting suggestion rows. Tagging Status stays a proper lookup CRUD.
- Docs/help/API: `actual_usages` on `GET /api/structure/options` are suggestions only; `actual_usage` on store/update stays free text.

---

## Structure Options Library — Actual Usage & Tagging Status (2026-08-10)

- Global lookup CRUDs under **Structure → Options Library** for **Tagging Status** and **Actual Usage** (capability `manage_structure_options`, granted to Administrator on migrate/seed).
- Migration **092** creates `structure_tagging_statuses` / `structure_actual_usages`; seeds the former hardcoded tagging statuses; imports distinct existing `actual_usage` values when the usage table is empty.
- Structure create/edit: Actual Usage is free text with datalist suggestions; Status options + other/refusal flags come from the library (`form.js` uses `data-requires-other` / `data-requires-refusal`).
- **Additive API:** `GET /api/structure/options` — `tagging_statuses`, `actual_usages`, `classifications` (Bearer; view/add/edit structure).
- Seeder `database/seeders/seed_structure_options.php`; truncate + E2E global-setup wired. Help, CAPABILITY_MATRIX, Postman, API/mobile docs updated.
- Backup/restore: new tables included in normal DB dump/restore (no special handling).

---

## Structure list — visitation, Actual Usage, Description columns (2026-08-10)

- Structure list / Select Columns / CSV export now include **Actual Usage**, full **visitation** fields (1st–3rd date + remarks), and keep **Description**.
- List SQL selects the new columns; table cells truncate long remarks/usage like description.
- Help (Structure overview) updated; Playwright `structure-list-visitation-columns.spec.ts` + npm `test:e2e:structure-list-visitation-columns:fast`.
- Users with saved column preferences keep their previous set until they re-select columns. Backup/restore unchanged.

---

## Docs consistency pass — API 2FA, routes, E2E gaps (2026-08-09)

- **`API_CONTRACT.md`:** Documented API email 2FA (`pending_2fa` → `/api/auth/2fa/verify` / `resend`); expanded HTTP route index; noted validation-section no-op; trimmed obsolete E2E npm notes.
- **`adr/0004`:** Consequences updated for API 2FA challenge flow (no longer “use web login only”).
- **E2E cleanup:** Removed orphan `package.json` runners whose Playwright specs are absent (escalation badges/scenario/multi-project, dashboard-by-category, effective-date, backup-restore-ui). `AUTOMATED_FUNCTIONAL_TEST_DESIGN.md` Known gaps lists former scripts for restore; history prereqs use living activity-history timezone suite.
- **SES ERD/UML + live diagrams:** Added migration-084 columns (`source_filename`, batch counters, etc.) in `ERD.md`, `UML_SES_IMPORT.md`, `erd-diagrams.js`, `ses-uml-diagrams.js`.
- **Index / labels:** `DOCUMENTATION.md` → `samples/`; `CAPABILITY_MATRIX.md` Roles UI labels aligned with `Capabilities.php`.

---

## E2E test results log space (2026-08-09)

- Added **`docs/test-results/`**: README (how to log), `TEMPLATE.md`, monthly folder `2026-08/`, and `YYYY-MM-README-TEMPLATE.md`.
- Wired into DOCUMENTATION update rules, TEST_STRATEGY DoD, RELEASE checklist, CONTRIBUTING, AUTOMATED_FUNCTIONAL_TEST_DESIGN, README, DEVELOPMENTGUIDE, `dev-help`. No secrets; documentation process only.

---

## Testing History prerequisites guide (2026-08-09)

- Added **`docs/TESTING_HISTORY_PREREQUISITES.md`**: environment/data/capability prerequisites, action→history matrix, timezone notes, verify steps (UI + `/api/history` + status-log), false failures, Mermaid flows.
- Linked from DOCUMENTATION, TEST_STRATEGY, AUTOMATED_FUNCTIONAL_TEST_DESIGN, README, GLOSSARY, DEVELOPMENTGUIDE tree. Documentation only.

---

## ERD — split SES import vs RAP mapping (2026-08-09)

- **`docs/ERD.md`:** former combined §6 split into **§6 SES import** and **§7 RAP mapping** (platform renumbered). Inventory lists SES and RAP separately.
- Live `/dev-help/#erd` tabs: **SES import** and **RAP mapping** (was “SES & RAP”). Documentation only.

---

## SES ZIP import feature UML (2026-08-09)

- Added **`docs/UML_SES_IMPORT.md`**: context, preview/import sequences, profile-tab read, validation activity, classes, data model, capabilities (match by `control_number`; Main fields never written).
- Live **`/dev-help/#ses-uml`** (`ses-uml-diagrams.js` / `ses-uml.js`). Corrected ERD SES note (no `section_id` FK on version sections).
- Linked from DOCUMENTATION, UML.md, GLOSSARY, README. Documentation only.
- **Mermaid fix:** GitHub-safe sequence/flowchart syntax (no `;` in `Note` lines, avoid `{id}` / `≤` / `<br/>` in diagrams).

---

## Restrict public access to `/dev-help/` (2026-08-09)

- Root `.htaccess`: **403** for `/dev-help` unless client is `127.0.0.1` / `::1`.
- `dev-help/.htaccess`: Apache 2.4 `Require local` (local XAMPP still works).
- Docs: DEPLOYMENT §4.1 (incl. nginx snippet), SECURITY hardening, CONFIGURATION, RUNBOOK §7.7. Prefer production DocumentRoot = `public/`.
- Backup/restore unchanged (Apache config / docs only).

---

## UML — RBAC roles & capabilities (2026-08-09)

- Added **UML §8**: RBAC class diagram (User / Role / RoleCapability / Capabilities / Auth / UserProjects) and decision flow for `Auth::can` + project scope.
- Live `/dev-help/#uml` tabs **RBAC model** and **RBAC flow** (`uml-diagrams.js`). Linked from CAPABILITY_MATRIX and ADR-0007.
- Documentation only — no auth runtime changes.

---

## Software engineering documentation pack (2026-08-09)

- Added **`docs/DOCUMENTATION.md`** master index plus: `REQUIREMENTS.md`, `NFR.md`, `GLOSSARY.md`, `CAPABILITY_MATRIX.md`, `RUNBOOK.md`, `CONFIGURATION.md`, `DEPLOYMENT.md`, `SECURITY.md`, `TEST_STRATEGY.md`, `RELEASE.md`.
- Repo root: **`CONTRIBUTING.md`**, **`SECURITY.md`** (points to docs). Linked from README, DEVELOPMENTGUIDE, FrameworksGuide, adr README, `dev-help`.
- Documentation only — no schema, API, or backup/restore behavior changes. Ops contacts / some NFR baselines left TBD for each org.

---

## UML diagrams + live /dev-help/#uml (2026-08-09)

- Added **`docs/UML.md`**: Mermaid component, class, sequence (web / API / restore), grievance state, and authorization-flow diagrams.
- **`/dev-help/#uml`**: interactive canvas with tabs; external JS `uml-diagrams.js` + `uml.js` (Mermaid CDN shared with ERD). Linked from README, DEVELOPMENTGUIDE, FrameworksGuide, adr README.
- Documentation / guide UI only — no schema, API, or backup/restore behavior changes.

---

## Dev-help live ERD diagrams (2026-08-09)

- **`/dev-help/#erd`**: interactive Mermaid ER canvas with domain tabs (Overview, Auth & API, Projects & geo, Profiles & structures, Grievances, SES & RAP, Platform).
- External JS: `dev-help/assets/erd-diagrams.js` (sources), `erd.js` (render/tabs/theme); Mermaid 11 via CDN; styles in `style.css`. Keep sources aligned with `docs/ERD.md`.
- Documentation / guide UI only — no schema or backup/restore behavior changes.

---

## Database ERD documentation (2026-08-09)

- Added **`docs/ERD.md`**: Mermaid entity-relationship diagrams by domain (auth, projects/geo, profiles/structures, grievances, SES/RAP, platform) plus table inventory (migrations 000–091).
- Linked from `DEVELOPMENTGUIDE.md` §4, root `README.md`, `FrameworksGuide.txt`, and `dev-help`. Documentation only — no schema or backup/restore behavior changes.

---

## Architectural Decision Records (2026-08-09)

- Added **`docs/adr/`**: README (process + index) and Accepted ADRs **0001–0010** (ADR process, custom PHP MVC, MySQL/MariaDB, session + Bearer auth, API envelope, external JS, capabilities, ZIP backup / CLI restore, additive mobile REST, configurable Playwright `BASE_URL`).
- Linked from `docs/DEVELOPMENTGUIDE.md` and root `README.md` docs table. No schema, API, or backup/restore behavior changes (documentation only).

---

## Profile + grievance attachment cards on API (2026-08-05)

- **Additive:** `GET /api/profile/{id}` and `GET /api/grievance/{id}` return `attachments[]` (id, title, description, file_path, url, sort_order).
- **Profile:** multipart attachment cards on **`POST /api/profile/store`** (was update-only) and update; omit fields on update to leave cards unchanged.
- **Grievance:** multipart registration cards on **`POST /api/grievance/store`** and **`update/{id}`** (same fields as web); status-history `status_attachments[]` unchanged.
- Serve URLs reuse `/serve/profile` and `/serve/grievance-card-attachment` (Bearer or session). Docs, Postman, MOBILE_EXCHANGES, help updated. Backup/restore unchanged (same tables/files).

---

## Structure list API for mobile (2026-08-05)

- **Additive:** `GET /api/structure/list` — paginated list (`page`, `per_page`, `q`, `project_id`, plus the same optional filters as the web Structure list). Envelope matches profile/grievance list (`items`, `total`, …). Requires `view_structure`; project-scoped via `user_projects`.
- Enables a mobile **Structures** tab without opening structures only from Profiles.
- Docs: `MOBILE_APP_INTEGRATION.md`, `API_CONTRACT.md`, `MOBILE_EXCHANGES.md`, Postman, help (Structure overview), DevelopmentHistory. Backup/restore unchanged (read-only API).

---

## Mobile + tablet responsiveness baseline (2026-08-04)

- Responsive shell now works by default on mobile/tablet: sidebar becomes a drawer under ~992px with overlay and close behavior.
- Top navigation gains a mobile hamburger/offcanvas menu under ~992px to keep modules reachable on narrow screens.
- List toolbar, grievance filter/form/view actions, and history panels were tightened for small screens.
- `UserUiSettings::MOBILE_FRIENDLY_DEFAULT` is now `true` for new users; the setting remains available for enhanced stacking mode.
- Added Playwright responsive smoke coverage and npm scripts.

---

## Grievance API — field-level Activity History (2026-08-04)

- **`POST /api/grievance/update/{id}`** now records the same field-level **from → to** diffs in Activity History as the web edit form (via shared `App\GrievanceFieldChanges`), and still tags the entry with `source: api`.
- Notifications for non-status API updates include the changed-field summary when present.
- Partial API updates preserve **`phase_id`** when omitted (no longer cleared to null on every update).
- Help (grievance view), API contract, Postman description, DEVELOPMENTGUIDE, and DevelopmentHistory updated. Backup/restore unchanged (same `audit_log.changes` JSON shape).

---

## Help / Admin Guide — backup restore hardening (2026-08-03)

- Contextual Help page: `App/Views/help/pages/backup-restore.php` (`/help?from=backup-restore`).
- Administrator Guide §4 Maintenance updated with PAPeR ZIP backup, CLI restore, wipe/`--force`, completion audit, and rollback guidance.
- Backup/Restore UI tip links to Help and Admin Guide.

## Restore hardening — force, wipe leftovers, safer migrate (2026-08-03)

- **Hard-fail** on missing/foreign manifest `app` or dbname mismatch unless `--force`.
- **Schema wipe** before import (drop all tables/views) so leftovers cannot survive; opt out with `--keep-extra-tables`.
- **MigrationRunner:** duplicate column/table/key “already exists” conflicts are recorded as applied **without** running `down()` (avoids dropping restored columns).
- **Tests:** `npm run test:restore-completion-audit`, `npm run test:migration-idempotent-schema`.

## Restore — data completion audit + auto-rollback (2026-08-03)

- **Feature:** After SQL import, restore compares **pre-sanitize** `database.sql` INSERT row counts to live `COUNT(*)` per table (`cli/restore_completion_audit.php`).
- **On failure:** Auto-rolls back from the pre-restore safety ZIP when present, otherwise the newest `paper-backup-*.zip` / `paper-before-restore-*.zip` in `storage/backups` (excludes the failed source). Nested rollback uses `--skip-safety-backup --no-completion-audit --no-auto-rollback --no-migrate`.
- **Flags:** `--no-completion-audit`, `--no-auto-rollback`.
- **Test:** `npm run test:restore-completion-audit`.

## Restore — keep INSERT rows with parentheses in text (2026-08-03)

- **Bug:** `paper_sql_parse_value_tuples()` treated `(` / `)` inside quoted VALUES as tuple delimiters. Sanitizing legacy generated-column dumps (e.g. `grievances.description_complaint`) dropped most rows while mysql still exited 0 — list pages looked “empty” after restore.
- **Fix:** `cli/backup_sql_helper.php` parses tuples with string/escape awareness (including SQL `''` doubled quotes).
- **Test:** `tests/cli/backup_sql_generated_columns_test.php` covers parentheses and doubled quotes in VALUES.

## Stop tracking `.env.playwright` (2026-08-03)

- Remove `.env.playwright` from the git index (file was already listed in `.gitignore`). Local copies stay; use `.env.playwright.example` as the template for `BASE_URL` and credentials.
