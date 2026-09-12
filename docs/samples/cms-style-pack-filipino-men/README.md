# The Filipino Men

CMS style pack for a **warm paper journal** — everyday Filipino manhood, not alpha-male chrome.

- Paper cream + ink + terracotta accent
- Serif type, editorial chrome, magazine essays
- Blog kicker: `FOR THE EVERYDAY FILIPINO MAN`

## Zip

Built as `cms-style-pack-filipino-men.zip` in:

- `docs/samples/`
- `public/assets/theme-packs/`

Rebuild:

```bash
php cli/build_style_pack.php filipino-men
```

Install from **Customize / General**, or download `?pack=filipino-men`.

Seed the designed site (pages, menu, empty desks — **no post import**):

```bash
php cli/seed_filipino_men_site.php
```

Import from the Atom template (or a Blogger dump such as `docs/atoms-collections/feed.atom`). Feed dates = backdate or schedule; links use `config/app.php` `base_url`:

```bash
php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom
php cli/import_atom_feed.php docs/atoms-collections/feed.atom
```
