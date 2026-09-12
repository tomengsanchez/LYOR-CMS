# Atom import sample

Template feed for **Posts → Import** and `php cli/import_atom_feed.php`. Copy `sample.atom`, edit it, import.

| File | Role |
|------|------|
| [sample.atom](sample.atom) | Four entries: backdated post, scheduled post, draft post, PAGE (skipped by default) |

```bash
php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom --dry-run --verbose
php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom
```

Admin: upload `sample.atom` at `/admin/posts/import`.

Internal links use `config/app.php` `base_url` at import time. Future `published` stays off the public site until due (no cron). PAGE entries need `--include-pages`. Existing slugs need `--update`.

## Fields

| Element | Meaning |
|---------|---------|
| `title` | Post/page title (required) |
| `blogger:filename` | Slug = filename basename without `.html` (e.g. `/2024/03/my-post.html` → `my-post`) |
| `published` | ISO-8601 (UTC `Z` is fine). Past = live backdated. Future + LIVE = scheduled |
| `blogger:type` | `POST` (default) or `PAGE` |
| `blogger:status` | `LIVE` (published) or `DRAFT` |
| `blogger:metaDescription` | Excerpt / meta description |
| `category term` | First matching CMS category; remaining terms become tags |
| `content type="html"` | Body. Prefer CDATA. Keep tags simple (`p`, `h2`–`h6`, lists, `a`, emphasis) |

Link rewrite matches another entry’s filename path on **any host** (`https://old-site.example/2024/03/sample-backdated-post.html`). Relative `/p/slug.html` works too.

Blogger/WordPress-style Atom exports that use the same elements also work (example dump: `xyz/feed.atom`).
