# Frontend JavaScript conventions

**Architecture decision:** [ADR-0006 — External JavaScript only](adr/0006-external-javascript-only.md).

## Goals

- PHP views: markup and server-rendered data only.
- Behavior lives in external files under `public/assets/js/`.

## Placement

- Mirror the module: `App/Views/builder/editor.php` → `public/assets/js/builder/*.js`
- Pages/posts/media similarly under `public/assets/js/`
- Shared: `public/assets/js/layout/`, `public/assets/js/partials/`

## Allowed in views

- `<script src="...">` tags (cache-bust with `?v=` filemtime).
- Small config bridges, e.g. `data-*` on `#cmsBuilderConfig` or `window.CmsMediaConfig`.

## Not allowed

- Inline behavior `<script>` blocks
- `onclick=` / other inline handlers

Visual builder: no iframe; live CSS in `#cmsBuilderLiveCss`.
