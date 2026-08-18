# Pulse

CMS style pack for a **civic / community** look, built around extra widget areas.

- Teal accent, mist background, rounded cards
- Starter widgets (empty areas only on activate):
  - **Header** — search
  - **After header** — call-to-action
  - **Homepage** — featured posts
  - **After content** — CTA + pages list
- Does **not** fill Sidebar or Footer

## Zip

Built as `cms-style-pack-pulse.zip` in:

- `docs/samples/`
- `public/assets/theme-packs/`

Rebuild:

```bash
php cli/build_style_pack.php pulse
```

Or: `php cli/build_sample_style_pack.php` (builds all packs).

Install via Customize / General → **Pulse (header & homepage widgets)**, or download `?pack=pulse`.
