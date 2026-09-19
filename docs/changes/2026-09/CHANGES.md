## Hard-talk editorial Atom post (2026-09-19)

- Added `docs/atoms-collections/young-men-unused-to-hard-talk.atom`: one English The Filipino Men essay (3,000+ words) on why many young men cannot receive hard manhood conversation because older men avoided it. Distinguishes truthful correction from humiliation; includes FAQ, SEO/AEO fields, and internal links. Import with feed date kept; do not use `--spread-year`. Existing slug needs `--update`. Local `config/app.php` remains `http://cms.local`. No migration, REST, or Postman change. Backup is the Atom file plus `cms_posts` after import.

---

## Backup schema snapshot without legacy profiles table (2026-09-19)

- `cli/backup_schema_helper.php` no longer fatals when `profiles` is missing (Simple CMS DBs). Invitation column probes run only if the table exists; CMS backups log `CMS schema (no legacy profiles table)`.
- Fixes production: `Table '….profiles' doesn't exist` during `paper_backup_schema_snapshot()` after mysqldump/PDO dump.

---

## Backup mysqldump option-file password quoting (2026-09-19)

- `mysqldump` / `mysql` client credentials are written with **double-quoted** option-file values (`paper_mysql_option_file_quote` / `paper_write_mysql_client_cnf` in `cli/backup_sql_helper.php`). Passwords containing `#`, spaces, quotes, or `;` no longer truncate in the temp `.cnf`, which caused **1045 Access denied** on production while the website and `php cli/migrate.php --status` (PDO) still worked.
- If `mysqldump` still fails, `cli/backup.php` **falls back** to the PHP PDO exporter instead of aborting (so UI backup and restore’s pre-restore safety dump can succeed). Restore’s `mysql` client path uses the same quoting helper.
- Smoke: `php tests/cli/backup_mysql_option_file_quote_test.php`.

---

## September 21–30 The Filipino Men editorial collection + Atom SEO/AEO (2026-09-12)

- Added `docs/atoms-collections/the-filipino-men-sept-21-30-2026.atom`: 50 unique 700–1,500-word English/Taglish articles, five per day at 06:30 / 10:30 / 14:30 / 18:30 / 22:00 Philippine time. Topics cover Biblical manhood, Christian family life, civic accountability, sports, cost of living, flood readiness, work/AI, relationships, and mental health. Current-affairs claims are bounded to facts available on September 12; no future sports outcomes are invented.
- Atom feeds can declare `xmlns:cms="https://simplecms.local/ns/atom-import/1"` and supply `meta_title`, `llm_summary`, `citation_snippet`, `faq_json`, and `robots_noindex`. Values use existing `PublicSeo` normalization. On `--update`, supplied fields overwrite and omitted fields preserve existing metadata. Dry runs no longer create missing categories. No migration or REST endpoint change.
- Every collection post includes direct-answer copy, body FAQ, structured FAQ JSON, citation snippet, meta description, internal links, and one primary category. New desks may be created automatically: Faith & Family, Civic Life & Accountability, and Sports & Strength.
- Smoke coverage, sample feed, import UI/help, API contract, development guide, and Atom collection README updated. Import with feed dates unchanged; do **not** use `--spread-year`.

---

## Google Analytics, Ads, and Search settings (2026-09-12)

- System → General has a **Google** block: GA4/UA measurement ID, Google Ads (`AW-`), AdSense (`ca-pub-`), Programmable Search CX, and Search Console verification (same `seo_google_site_verification` key, moved out of SEO). IDs only — junk/`javascript:` is rejected. Public layout loads official gtag/AdSense scripts via `google-tags.js` (data attributes). Tags skip theme preview and the Customizer iframe. Verification meta is on every public page. CSE results appear below built-in `/search` when CX is set.
- Default CSP allows Google tag/ads/CSE hosts. Backup is `app_settings` (no migration). Help: General / Search. API: `GET /api/system/general` includes `google`. Smoke: `php tests/cli/cms_google_settings_smoke_test.php`.

---

## Atom import spread-year (2026-09-12)

- Optional `--spread-year=YYYY` (admin: **Spread publish dates across year**) spaces LIVE post `published_at` evenly from 1 January through 31 December. Oldest feed date lands in January; pages and drafts keep their feed dates. Dates still in the future stay scheduled until due.
- `xyz/feed.atom` (LalakiPH essays) imported with `--spread-year=2026`. PAGE entries skipped. Help: Posts.

---

## Atom / Blogger post import (backdate + schedule, 2026-09-11)

- Admin **Posts → Import** (`/admin/posts/import`) and CLI `php cli/import_atom_feed.php [feed.atom]` ingest Atom/Blogger exports. Template: `docs/samples/cms-atom-import/sample.atom`. Feed `published` is stored as `published_at` (past = live backdated; future = hidden until due via existing `Post::liveSql`, no cron).
- Internal hrefs (Blogger filenames, `/p/…`, `/yyyy/mm/…`) are rewritten with `config/app.php` `base_url` (`App\SiteUrl`). Google search wrapper links are unwrapped to text. PAGE entries skipped unless `--include-pages`.
- `Post::create` / `Page::create` honor an explicit `slug` and optional `author_id`. Smoke: `php tests/cli/cms_atom_import_smoke_test.php`. Help: Posts.

---

## The Filipino Men site chrome (no essay import) (2026-09-11)

- New bundled style pack **The Filipino Men** (`docs/samples/cms-style-pack-filipino-men/`): warm paper / ink / terracotta, editorial chrome, magazine essays, kicker `FOR THE EVERYDAY FILIPINO MAN`. Rebuild: `php cli/build_style_pack.php filipino-men`. Install from Customize or `?pack=filipino-men`.
- CLI `php cli/seed_filipino_men_site.php` brands the local site, activates the pack, adds six empty desks (categories matching `xyz/feed.atom` labels), and publishes Home / About / Topics / FAQ plus Primary Menu. **Does not import posts.**
- Backup is theme CSS in uploads + `app_settings` / pages / categories / widgets. Help: Customize / General.

---

## Local hostname `cms.local` (project-root vhost, not in-repo) (2026-09-11)

- Local run target is **`http://cms.local`**. Document root is the **project root** (`C:/xampp/htdocs/cms`), not `public/`. Root `index.php` + `.htaccess` enter the app and block internals. The VirtualHost is on the machine Apache file (`C:/xampp/apache/conf/extra/httpd-vhosts.conf`) plus Windows `hosts` (`127.0.0.1 cms.local`). No vhost file is added to this repository.
- CORS allow-list in `config/app.php` / `config/app-sample.php` includes `http://cms.local`. Playwright `BASE_URL` already defaults to that origin.
- Backup/schema unchanged. Help: no end-user change (ops/dev only).
