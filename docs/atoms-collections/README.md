# Atom collections

Ready-to-import content collections for Posts → Import or `cli/import_atom_feed.php`.

| File | Purpose |
|------|---------|
| `feed.atom` | Original LalakiPH Blogger collection already used for the production archive |
| `the-filipino-men-sept-21-30-2026.atom` | 50 long-form posts, five daily at 06:30, 10:30, 14:30, 18:30, and 22:00 Philippine time |
| `young-men-unused-to-hard-talk.atom` | One English editorial post (2,000+ words): young men unused to hard talk because older men avoided it |

The September 21–30 collection includes English/Taglish articles on Biblical manhood, family, civic accountability, sports, work, cost of living, disasters, and mental health. Current-affairs statements are bounded to information available on September 12, 2026; future Asian Games results are not predicted. The hard-talk editorial is English throughout and is dated 19 September 2026, 18:30 Philippine time.

Each entry has:

- simple allowlisted HTML with direct-answer opening, question-led sections, FAQ, and internal links;
- unique SEO title and meta description;
- `cms:llm_summary`, `cms:citation_snippet`, and three structured FAQ pairs;
- `cms:robots_noindex` set to `false`.

Run a dry run first. Keep the feed dates; do **not** add `--spread-year`.

```bash
php cli/import_atom_feed.php docs/atoms-collections/the-filipino-men-sept-21-30-2026.atom --dry-run --verbose
php cli/import_atom_feed.php docs/atoms-collections/the-filipino-men-sept-21-30-2026.atom --verbose
php cli/import_atom_feed.php docs/atoms-collections/young-men-unused-to-hard-talk.atom --dry-run --verbose
php cli/import_atom_feed.php docs/atoms-collections/young-men-unused-to-hard-talk.atom --verbose
```

The importer creates missing primary categories by default. Existing slugs are skipped; use `--update` only when intentionally replacing matching content and supplied SEO/AEO fields.

Production is `https://thefilipinomen.com`. Article links are intentionally root-relative so they resolve on that domain; production `config/app.php` must set `base_url` to `https://thefilipinomen.com`. Keep the local XAMPP value on development machines.
