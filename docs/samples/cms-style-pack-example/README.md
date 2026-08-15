# CMS Style Pack — developer template

This folder is the **default theme template**. A ready-made zip ships as:

- `docs/samples/cms-style-pack-example.zip`
- `public/assets/theme-packs/cms-style-pack-example.zip` (download from Customize / General)

Rebuild the zip after edits:

```bash
php cli/build_sample_style_pack.php
```

## Archive layout (required)

Zip **file contents at the archive root** (not nested in a subfolder):

```
cms-theme.json   (required)
extra.css        (optional; must match "extra_css" in JSON)
README.txt       (optional; added by the build script)
```

## How to create your theme

1. Download **Sample template (.zip)** from Appearance → Customize, or copy this folder.
2. Edit `cms-theme.json`:
   - `name` — shown in admin after import
   - `settings` — only known `pub_theme_*` / `public_accent_color` keys (see JSON for a full starter set)
   - `extra_css` — filename of optional stylesheet inside the zip
3. Edit `extra.css` for small visual tweaks (optional).
4. Re-zip so those files sit at the zip root.
5. Import via **Customize → Style pack upload** or **System → General**.

## Allowed setting keys

Use the same field names as the Customizer form (examples in `cms-theme.json`).
Unknown keys are skipped. Values are normalized by `PublicTheme`.

Boolean-like flags: use `"1"` / `"0"` strings.

Preset tip: use `"pub_theme_preset": "custom"` when setting `pub_theme_custom_bg` / `surface` / `text`.

## WordPress theme.zip

You may upload a WP theme zip for **colors/metadata only** (Theme Name + `theme.json` palette). PHP templates are never executed.

## Backup

Pack CSS lives under `public/uploads/theme-packs/active/` (included in uploads backup).
Pack name/source/css path live in `app_settings`.
