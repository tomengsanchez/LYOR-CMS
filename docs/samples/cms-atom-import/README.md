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

Internal links use `config/app.php` `base_url` at import time. Future `published` stays off the public site until due (no cron). PAGE entries need `--include-pages`. Existing slugs need `--update`. Optional `--spread-year=2026` (or the admin year field) spaces LIVE posts from 1 January through 31 December.

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
| `cms:meta_title` | Optional SEO title (max 255 characters) |
| `cms:llm_summary` | Plain-language AI summary (max 2,000 characters) |
| `cms:citation_snippet` | Quotable direct answer / BLUF (max 500 characters) |
| `cms:faq_json` | JSON array of up to 20 `question` / `answer` pairs; CDATA recommended |
| `cms:robots_noindex` | `true`/`false`, `1`/`0`, `yes`/`no`, or `on`/`off` |

Link rewrite matches another entry’s filename path on **any host** (`https://old-site.example/2024/03/sample-backdated-post.html`). Relative `/p/slug.html` works too.

## Native CMS SEO / AEO namespace

Enriched feeds declare `xmlns:cms="https://simplecms.local/ns/atom-import/1"` on the feed. The `cms:*` fields are optional. On create, an omitted SEO title falls back to the post title and other omitted fields use normal defaults. With `--update`, supplied fields overwrite stored values while omitted fields preserve them. An explicitly empty element clears that field. Values still pass through `PublicSeo` normalization, including FAQ shape and length limits.

The Filipino Men scheduled editorial collection demonstrates all fields:

```bash
php cli/import_atom_feed.php docs/atoms-collections/the-filipino-men-sept-21-30-2026.atom --dry-run --verbose
php cli/import_atom_feed.php docs/atoms-collections/the-filipino-men-sept-21-30-2026.atom --verbose
php cli/import_atom_feed.php docs/atoms-collections/young-men-unused-to-hard-talk.atom --dry-run --verbose
php cli/import_atom_feed.php docs/atoms-collections/young-men-unused-to-hard-talk.atom --verbose
```

Blogger/WordPress-style Atom exports that use the same elements also work (example dump: `docs/atoms-collections/feed.atom`). A one-off English editorial is `docs/atoms-collections/young-men-unused-to-hard-talk.atom` (keep its feed date; do not `--spread-year`).
