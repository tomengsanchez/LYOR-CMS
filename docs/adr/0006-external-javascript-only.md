# ADR-0006: External JavaScript only

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization; also enforced by workspace Cursor rule

## Context

PHP views mixed with large inline scripts and `onclick` handlers become hard to cache, review, test, and reuse across profile/structure/grievance modules.

## Decision

**Behavior lives in external files** under `public/assets/js/`, mirrored by module/view where practical.

Allowed in views:

- `<script src="...">` tags
- Minimal config bridges: `window.someModuleConfig = {...}`

Not allowed:

- Large inline behavior `<script>` blocks
- Inline event attributes (`onclick`, `onsubmit`, etc.)

Conventions: `docs/FRONTEND_JS_CONVENTIONS.md`.

## Consequences

- Positive: Clear separation of markup vs behavior; easier Playwright targeting and shared helpers.
- Negative: Small ceremony for config bridges; every interactive view needs a matching JS file when logic grows.
- Follow-on: New UI work must ship JS externally. Does not affect backup/restore or SQL.

## Alternatives considered

- **Inline scripts in views** — faster for one-offs; poor reuse and CSP-hostile over time.
- **Bundler/SPA (Vite + React/Vue)** — modern DX; conflicts with server-rendered MVC and current Bootstrap/jQuery stack (ADR-0002).
