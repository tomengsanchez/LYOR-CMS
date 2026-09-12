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
