## Enterprise style pack + Team POGI theme (2026-08-18)

- New bundled style pack **Enterprise** (`docs/samples/cms-style-pack-enterprise/`): navy/slate palette (`#0f172a` / `#1e40af`), modern sans-serif, soft shadows, compact radius, sticky header, card blog layout. Optional starter widgets (search, CTA band, featured posts, pages, social) seed **empty** areas only. Install from **System → General → Style packs** or **Customize**; download `?pack=enterprise`. Rebuild: `php cli/build_style_pack.php enterprise`.
- Team POGI E2E (`npm run test:e2e:cms-team-pogi-site:fast`) activates Enterprise after site seed.

---

## Team POGI demo site + General settings form fix (2026-08-18)

- Playwright builder `tests/e2e/cms-team-pogi-site.spec.ts` seeds a full marketing site for [Team POGI](https://www.facebook.com/teampogi31/): five layout-builder pages (home, mission, projects, crew, join), five blog posts with Lorem Picsum images, Primary Menu (including Facebook link), homepage = Team POGI page, branding, and Facebook SEO URL. Run: `npm run test:e2e:cms-team-pogi-site` (headed) or `npm run test:e2e:cms-team-pogi-site:fast`.
- **Fix:** nested style-pack `<form>` elements inside System → General’s main save form broke HTML form boundaries so **Save** did not persist branding/Reading settings. Style packs moved to a separate card; main form has `id="generalSettingsForm"`. Tomeng E2E updated to match.

---

## Newsletter signups and subscribers (2026-08-17)

- Public list at `/subscribe` plus a **Newsletter signup** widget (CSRF, honeypot `website`, consent checkbox, per-IP/session rate limit). Default **double opt-in** emails a 7-day confirm link (`GET /subscribe/confirm/{token}`); unsubscribe is `GET` then `POST /unsubscribe/{token}`. Success copy does not reveal whether an address was already on the list. Migration **025** (`cms_newsletter_subscribers`). Settings: `newsletter_enabled`, `newsletter_double_opt_in`, `newsletter_rate_limit` (General). Admin: `/admin/subscribers` (Confirm / Unsubscribe / Delete / CSV). Caps: `view_subscribers`, `manage_subscribers`, `export_subscribers`. Confirm mail uses the existing Mailer (SMTP / MailerSend / log).
- Backup is the new table plus `newsletter_*` keys in `app_settings`. Help: Subscribers / Widgets / General / Email / Backup. Smoke: `cms_newsletter_smoke_test`. Playwright: `npm run test:e2e:cms-newsletter`. External JS: `widgets/form.js` field options only.

---

## Playwright coverage for Pulse through password UX (2026-08-17)

- Headless spec `tests/e2e/cms-pulse-content-ux.spec.ts` walks Pulse widget areas, skip-to-content, 404 search, Duplicate, bulk UI, sticky/reading/TOC/share, `/search` + year/author archives, scheduled preview vs 404, and password gate (visitor JSON stub + unlock). `BASE_URL` defaults to `http://cms.local`. Run: `npm run test:e2e:cms-pulse-ux`. Public skip-to-content link restored in `layout.php` (CSS was already present).
- `cms-smoke` homepage checks `#public-content` (does not require a page titled Welcome). `cms-wp-extended` asserts `/search` (`#siteSearchInput`); on-blog `#blogSearchInput` is skipped when Pulse hides it (`pub_theme_show_blog_search` off).
- Backup unchanged. Help: Pages / Posts. Added to `npm run test:e2e:cms`.

---

## Password-protected pages and posts (2026-08-17)

- Published pages and posts can require a visitor password (`cms_pages`/`cms_posts.password_hash`, migration 024). The public URL stays 200; body, layout, citations, comments, OG/JSON body, and LLM/search listings stay hidden until unlock (`POST /unlock/page|post/{id}`, CSRF, rate-limited). Editors with `edit_pages` / `edit_posts` skip the gate. Session plus a 10-day HttpOnly cookie (HMAC’d with `cms_content_pass_key` in `app_settings`; changing the password invalidates old cookies). Hashes are never returned on the REST API (`password_protected` flag instead). Optional write fields: `content_password`, `remove_content_password`.
- Backup is the hashed column plus the HMAC key in `app_settings`. Help: Pages / Posts / General / Backup. Smoke: `cms_pages_smoke_test`, `cms_wp_extended_smoke_test`, `cms_routes_smoke_test`. External JS: none (unlock is a plain form).

---

## Bulk actions on pages and posts (2026-08-17)

- List screens add a Bulk control: select rows on the current page (max 100) and Apply. Pages: publish / draft / delete. Posts: publish / draft / pin / unpin / delete. CSRF, capability per action (`edit_*` vs `delete_*`). Draft/delete skip the configured homepage and `welcome`/`home` fallback pages. Row checkboxes use the HTML `form` attribute so Duplicate/Delete row forms are not nested.
- Backup is unchanged (`deleted_at` / `status` / `is_sticky` in SQL). Slug uniqueness now counts soft-deleted pages/posts so Duplicate after delete cannot hit `uk_cms_*_slug`. Help: Pages / Posts. Smoke: `cms_pages_smoke_test`, `cms_wp_extended_smoke_test`, `cms_routes_smoke_test`. External JS: `bulk-list.js`.

---

## Reading UX, schedule, sticky, search & archives (2026-08-17)

- Public posts get previous/next, an on-page TOC (two or more h2/h3), last-updated, tracker-free Share (copy / email / native), print CSS, and a back-to-top control (`enhance.js`). Skip-to-content, reading time, related posts, and a 404 with site search stay in place.
- Editors preview drafts and scheduled posts with `?preview=1` (requires `edit_posts` / `edit_pages`; `noindex`). Future **Publish at** times stay off lists, RSS, sitemap, and public slug lookup until due. **Pin to top** (`cms_posts.is_sticky`, migration 023) lists first.
- New public routes: `/search` (pages + posts), `/blog/archive/{year}` and `/{month}`, `/blog/author/{username}`. Search widgets hit `/search`. New **Monthly archives** widget. Sitemap includes recent month archives.
- Backup: `is_sticky` and `published_at` in the SQL dump; widget areas unchanged. Help: Posts / Pages / Widgets / Backup. Smoke: `cms_wp_extended_smoke_test`, `cms_routes_smoke_test`. Postman: optional `is_sticky` / `published_at` on post write.

---

## Duplicate page/post + public reading basics (2026-08-17)

- Admin **Duplicate** (`POST /admin/pages|posts/duplicate/{id}`, CSRF, `add_pages` / `add_posts`) creates a draft “Copy of …” including `layout_json`, blocks, SEO, and post tags (sticky is not copied). Redirects to edit.
- Public layout has a skip link to `#public-content`. Posts show an estimated reading time and related posts (same category first). 404 pages offer search plus latest posts.

---

## Pulse style pack + extra widget areas (2026-08-17)

- Widget areas: **Header**, **After header**, **Homepage**, **Sidebar**, **After content**, **Footer**. Types include featured posts, CTA, pages, and social links. Pulse (`docs/samples/cms-style-pack-pulse/`, `?pack=pulse`) fills **empty** Header / After header / Homepage / After content only (`installStarterIfEmpty`); occupied areas stay as they are.
- Backup is `cms_widgets` plus theme pack CSS in uploads/`app_settings`. Help: Widgets / Customize / General / Backup. Smoke: `cms_wp_extended_smoke_test`, `cms_theme_style_pack_smoke_test`. Rebuild: `php cli/build_style_pack.php pulse`.

---

## Design field groups + small rich text (2026-08-17)

- Module catalog now lists Design **groups** (`align` / `type` / `color` / `space` / `chrome`). The inspector only shows those packs (Spacer is spacing; Image has no type; Heading still has type). Hover appears only when color, type, or chrome is present.
- Text, CTA, and Blurb bodies plus Accordion/Tabs item bodies use a small Bold / Italic / list / Link toolbar (`wysiwyg.js`). Heading stays plain. Unknown tags and `javascript:` links are stripped on save (`sanitizeRichText`); Custom HTML remains the escape hatch for embeds.
- Backup is still `layout_json`. Help: Pages / Posts / Backup. Smoke: `text` field type `rich`; spacer has no type group; `<strong>` kept, `<script>` gone, `javascript:` unwrapped; plain `"a\\nb"` still renders.

---

## Design text-align beats inner module CSS (2026-08-17)

- Compiled module `text-align` now also sets `.cms-el-* > *` so inner wrappers cannot keep a hardcoded align. Blurb was the only module that fought Design (`text-align:center` plus `margin:auto` on the image). Heading, text, CTA, button, testimonials, and icon list did not. Blurb images follow the same align; blurbs with no Design align stay centered.
- Backup is still `layout_json`. Help: Pages / Posts. Smoke: `.cms-el-m-bl>*{text-align:left}`.

---

## Section extras: shape dividers, video background, reverse columns (2026-08-17)

- Section Design adds allowlisted **shape dividers** (wave / tilt / curve / triangle; hardcoded SVG, `currentColor` fill), a **video background** URL (YouTube watch → constructed youtube-nocookie mute/loop iframe, Vimeo `background=1`, or HTTPS file; canvas never loads the iframe), and row Content **Reverse columns on mobile**. Per-device Bootstrap column widths are not in this pass. Overflow is clipped on the inner background layer so sticky children still work.
- Backup is still `layout_json`. Help: Pages / Posts / Backup. Smoke: shape SVG present; YouTube watch URL → nocookie background src; unknown host dropped; reverse class; no `javascript:` in CSS.

---

## Allowlisted sticky and z-index (2026-08-17)

- Visual builder Advanced adds **Position** (relative / sticky), **Z-index** (1–100), and **Sticky offset**. Values are stored as keys on design/settings bags (not `advanced`), compiled to hardcoded CSS. Absolute/fixed and z-index above 100 are dropped. Hover cannot store position. Copy style does not copy these keys.
- Backup is still `layout_json`. Help: Pages / Posts / Backup. Smoke: `position:sticky`, `top:1rem`, `z-index:10`; `fixed` / `9999` dropped.

---

## Typography pack (font, tracking, transform) (2026-08-17)

- Module Design adds allowlisted **Font** (system / sans / serif / mono), **Letter spacing** (tight–wider), and **Text transform**. Values are stored as keys in `layout_json` and compiled to hardcoded CSS — never interpolated user CSS. Live editor CSS patches the same way as font size/weight.
- Backup is still `layout_json`. Help: Pages / Posts / Backup. Smoke: serif stack, `letter-spacing:0.05em`, `text-transform:uppercase`; injection strings dropped.

---

## Inner row nested columns (2026-08-17)

- Visual builder adds **Inner row**: one nested row inside a column (stored as `mod.columns`, not gallery `data.columns`). Public markup is Bootstrap `row g-3` + `col-md-*`. A second inner row inside an inner column is dropped on save.
- Editor: nested inner columns/modules use `data-kind=inner_column|inner_module` and `cms-lb-inner-col` / `cms-lb-inner-mod` (no ⋮⋮ drag; ↑↓ only). Layers lists them but does not drag them. The module picker hides Inner row when already inside one.
- Backup is still `layout_json`. Help: Pages / Posts / Backup. Smoke: nested heading renders; nested inner_row dropped; `cms-mod-inner-row` / `col-md-6`; plainText walks inner columns.

---

## Gallery + Testimonials modules (2026-08-17)

- Visual builder adds **Gallery** (up to 12 images, 2–4 columns; stacks to one column on small screens; optional link per image) and **Testimonials** (quote, name, role, optional photo). Captions, quotes, and names are escaped; `javascript:` image links are dropped.
- Backup is still `layout_json` (plus Media uploads). Help: Pages / Posts / Backup. Smoke asserts column clamp, escaped captions, and escaped quotes.

---

## Tabs + Icon list modules (2026-08-17)

- Visual builder adds **Tabs** (up to 12 panels; public HTML is radio + CSS, no extra JS) and **Icon list** (emoji/symbol + text). Titles, bodies, and icons are escaped. The canvas shows labels/rows, not live radios.
- Backup is still `layout_json`. Help: Pages / Posts / Backup. Smoke asserts escaped tab titles and icon markup.

---

## Accordion + Video modules (2026-08-17)

- Visual builder adds **Accordion** (up to 12 items; public HTML is native `<details>`/`<summary>`, no extra JS) and **Video** (YouTube, Vimeo, or HTTPS `.mp4`/`.webm`/`.ogg`). Iframe `src` is constructed from an allowlisted ID/host — never an arbitrary URL. The canvas shows a placeholder (no iframe).
- CSP default `frame-src` adds `youtube-nocookie.com` and `player.vimeo.com`; `media-src` allows HTTPS files. Custom HTML modules still strip iframes.
- Backup is still `layout_json` (plus uploads). Help: Pages / Posts / Backup. Smoke: YouTube watch URL → nocookie embed; script in accordion titles escaped; unknown hosts dropped.

---

## LayoutBuilder split + CMS-only docs (2026-08-17)

- `App/LayoutBuilder.php` is a facade; parse/render/CSS live in `App/LayoutBuilder/` (`ModuleCatalog`, `Normalizer`, `Renderer`, `Sanitize`, `Css`). Call sites still use `LayoutBuilder::`.
- Docs under `docs/` are CMS-only: PAPeR / grievance / structure / SES / RAP / mobile-domain guides and history were removed. Backup still dumps `layout_json`; public CSS remains derived.
- Help: Pages / Posts / Backup. Smoke: `cms_layout_builder_smoke_test`. Postman: Simple CMS collection only.

---

## Visual builder module Content field catalog (2026-08-17)

- `LayoutBuilder::moduleCatalog()` now includes Content **fields**, **defaults**, and a carousel `custom` flag. The editor inspector and Add-module defaults are generated from that JSON (`data-modules`); carousel slides and media pickers stay custom.
- Public sanitize/render still use the type switches (URL, HTML, lengths). Backup is still `layout_json` (saved module `data`, not the PHP catalog).
- Help: Pages / Posts / Backup. Smoke asserts heading fields; Playwright checks heading Level on Content.

---

## Visual builder border/shadow, type, copy style (2026-08-17)

- Design: **border** (width / style / color, including theme tokens), **corner radius**, **shadow presets** (sm / md / lg), plus module **weight** and **line height**. Compiled to CSS; shadow values are an allowlist (not raw CSS).
- **Copy style** / **Paste style** (Design panel and right-click) copies appearance bags only — not text, media, or CSS class. Modules paste onto modules; section/row/column paste onto those.
- Backup: still `layout_json`. Help: Pages / Posts / Backup. Smoke + Playwright `cms-builder-drag-column-size` assert radius and shadow.

---

## Visual builder Layers drag, TRBL spacing, CSS patch, module catalog (2026-08-17)

- **Layers** rows are draggable; drop uses the same reorder as the canvas ⋮⋮ handle (modules can move into another column).
- Padding and margin are **top / right / bottom / left** (still stored as a CSS shorthand string in `layout_json`). Presets fill all sides.
- Design ticks replace that element’s rules in `#cmsBuilderLiveCss` instead of rewriting the whole stylesheet. Public CSS is still compiled in full.
- Module picker labels/hints come from PHP `LayoutBuilder::moduleCatalog()` (editor `data-modules`). Backup unchanged.
- Help: Pages / Posts / Backup. Smoke + Playwright `cms-builder-drag-column-size` cover Layers drag and four-sided padding.

---

## Visual builder module hover + content patch (2026-08-17)

- Module Design has **Normal / Hover**. Hover styles live in `design_hover` (same on every device) and compile to `:hover` rules that beat Bootstrap / public button CSS. Empty hover fields inherit Normal.
- Content inspector edits replace the selected module’s inner HTML instead of rebuilding the canvas (carousel still rebuilds). Design still updates `#cmsBuilderLiveCss`.
- Backup: still `layout_json`. Help: Pages / Posts / Backup. Smoke + Playwright `cms-builder-drag-column-size` assert `:hover`.

---

## Visual builder theme tokens + section backgrounds (2026-08-17)

- Design colors accept **theme tokens** (Accent, Soft, Text, Muted, Surface, Page) stored as `accent` / `text` / … in `layout_json` and compiled to `var(--pub-accent)` so they follow Customize. Hex still works.
- Section / row / column Design: **background image** (`bg_media_id` or URL) plus overlay color and strength (0–80%). Compiled into the live/public stylesheet (`linear-gradient` + `url("…")`). Overlay can differ per device; the image is shared. URLs are sanitized.
- Backup: still `layout_json` + media uploads. Help: Pages / Posts / Backup. Smoke + Playwright `cms-builder-drag-column-size` assert `var(--pub-accent)`.

---

## Visual builder per-device design CSS (2026-08-17)

- Desktop / Tablet / Mobile toolbar now also **edits** Design for that device. Defaults stay on `settings` / `design`; overrides live in `settings_tablet` / `design_tablet` / `*_mobile` inside `layout_json`.
- Public CSS uses `@media (max-width: 1023.98px)` and `767.98px`. The editor is not an iframe, so preview uses `.cms-builder-canvas-wrap[data-device]` selectors (viewport media queries would ignore the canvas width). Column 1–12 width remains shared.
- Backup unchanged. Help: Pages / Posts / Backup. Smoke + Playwright `cms-builder-drag-column-size` assert tablet min-height in the stylesheet.

---

## Visual builder live design CSS (2026-08-17)

- Design / settings (colors, spacing, min-height, align, font size) update a compiled stylesheet instead of rebuilding the canvas. Width, vertical align, and hide-on-device patch classes in place. Custom CSS class and section type still rebuild.
- Public pages/posts emit `<style class="cms-layout-css">` from `LayoutBuilder::compileStylesheet` (same safe color/spacing/height checks). Generated CSS is not stored; backup remains `layout_json`.
- Editor: `public/assets/js/builder/styles.js`, `#cmsBuilderLiveCss`. Help: Pages / Posts / Backup. Smoke + Playwright `cms-builder-drag-column-size` assert the stylesheet, not inline `style=""`.

---

## Visual builder JS split + shortcuts (2026-08-15)

- Frontend editor JS is split under `public/assets/js/builder/` (`ns`, `history`, `model`, `canvas`, `layers`, `dnd`, `actions`, `panel`, `save`, `ui`, boot `editor.js`) so each area stays readable. Still external files only; `CmsBuilderApi` unchanged for Playwright.
- **?** (toolbar) lists shortcuts. Right-click a canvas block for Copy / Paste / Dup / Delete. Layers has a filter box. Zoom, device, and Layers stay for this browser tab (`sessionStorage` — not part of backup).
- Backup still `layout_json`. Help: Pages / Posts. Playwright opens the shortcuts dialog in `cms-builder-drag-column-size`.

---

## Visual builder layers, copy/paste, inline, autosave (2026-08-15)

- **Layers** tree (toolbar) jumps to any section/row/column/module; hover highlights the matching canvas block; Ã— closes the tree. Click a heading or text module on the canvas to edit in place.
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

- **WordPress-style sizes:** On JPG/PNG/WebP upload, GD generates `thumbnail` (150Ã—150 crop), `medium` (300), `medium_large` (768), `large` (1024), `1536x1536`, `2048x2048` under `public/uploads/media/` (tracked in `cms_media_sizes`, migration **015**).
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
- **Admin:** Post create/edit — featured image picker from Media library; size hints for 1200Ã—630 social preview.
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

## Backup `--no-uploads` flag (2026-08-13)

- **Exclude uploads** from System → Backup/Restore now passes `--no-uploads` correctly (it previously became `----no-uploads` and was ignored).
- CLI backup prints `Uploads: skipped (--no-uploads)` and no longer claims the ZIP contains uploads when they were excluded.
- Restore CLI uses the same flag parser. Test: `npm run test:backup-no-uploads`. Help/README updated.

