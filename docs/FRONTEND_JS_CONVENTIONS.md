# Frontend JavaScript Conventions

This document defines the standard approach for frontend JavaScript in this project.

**Architecture decision:** [ADR-0006 — External JavaScript only](adr/0006-external-javascript-only.md).

## Goals

- Keep PHP views focused on markup and server-rendered data.
- Keep JavaScript behavior in external files for maintainability.
- Avoid inline handlers and duplicated logic across modules.

## File placement and naming

- Put JavaScript in `public/assets/js/`.
- Mirror module/view structure where practical:
  - `App/Views/profile/form.php` -> `public/assets/js/profile/form.js`
  - `App/Views/profile/view.php` / `form.php` (Structure tab) -> `public/assets/js/profile/structure-tab.js` (with `window.profileStructureTabConfig` from the view)
  - `App/Views/grievance/dashboard.php` -> `public/assets/js/grievance/dashboard.js`
  - `App/Views/grievance/respondents.php` -> `public/assets/js/grievance/respondents.js` (autosubmit when sort, per-page, or filter `<select>` changes)
- Shared behaviors should live in shared files (for example `public/assets/js/layout/main.js` or `public/assets/js/partials/...`).

## What is allowed in views

- Allowed:
  - Script tags that load external JS files.
  - Minimal config bridge objects for runtime values from PHP:
    - `window.someModuleConfig = {...};`
- Not allowed:
  - Large inline `<script>` blocks containing behavior logic.
  - Inline event attributes such as `onclick`, `onsubmit`, `onchange`, `onerror`, etc.

## Config bridge pattern

When external JS needs server values:

1. Define a minimal config object in the view.
2. Load the corresponding external script.
3. Read the config in JS with a safe fallback.

Example (view):

```php
$scripts = '<script>window.profileFormConfig = ' . json_encode([
    'initialProjectId' => $initialProjectId,
], JSON_UNESCAPED_UNICODE) . ';</script>'
    . '<script src="/public/assets/js/profile/form.js"></script>';
```

Example (external JS):

```javascript
var cfg = window.profileFormConfig || {};
var initialProjectId = cfg.initialProjectId || null;
```

## Event handling standard

- Use delegated or direct listeners in external JS:
  - `$(document).on('submit', 'form[data-confirm]', ...)`
  - `$('.js-page-jump').on('change', ...)`
- Use semantic classes and `data-*` attributes as hooks.
- Prefer reusable shared handlers for common actions (confirm dialog, pagination jump, modal helpers).

## Refactor checklist (view with existing inline JS)

1. Move behavior logic to `public/assets/js/<module>/<view>.js`.
2. Replace inline handlers with classes or `data-*` attributes.
3. Keep only config bridge + script includes in the view.
4. Verify page behavior manually.
5. Run syntax/lint checks before finalizing.

## Notes for future contributors

- Keep new JavaScript ASCII unless a file already requires special Unicode.
- Avoid introducing module-level globals other than explicit `window.*Config`.
- If multiple pages share logic, extract to a shared file instead of copy-paste.
- **Grievance form (`public/assets/js/grievance/form.js`):** Non-PAPS respondent datalist/history/latest-details requests use a **minimum first-name length** (currently 3) before calling `/api/respondents/*`; keep this aligned with `App\Controllers\Api\ApiController` and `docs/API_CONTRACT.md`.
- **API envelope (`fetch` on `/api/*`):** Server responses use **`{ success, data, error }`**. Before using payload fields, unwrap **`data`** (see **`public/assets/js/grievance/dashboard.js`** → `unwrapApiPayload`, and **`public/assets/js/dashboard/index.js`** for the main **`GET /api/dashboard`** page). Integrators: `docs/API_CONTRACT.md`.
- **Presence heartbeat (layout):** Authenticated `layout/main.php` exposes `window.presenceHeartbeatConfig` and loads `public/assets/js/layout/presence-heartbeat.js` (POST `/api/presence/heartbeat` on load, interval, and tab visible). Keep heartbeat quiet (no global spinner). Admin poll UI: `public/assets/js/realtime_dashboard/index.js`.
