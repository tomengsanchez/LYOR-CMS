# ADR-0002: Custom PHP MVC (not a full framework)

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization of the existing stack

## Context

PAPeR needs a maintainable PHP app (profiles, structures, grievances, system tools, REST API) runnable on typical shared/XAMPP-style hosts with a small dependency footprint and full control over auth, routing, and migrations.

## Decision

Use a **custom PHP MVC**:

- Front controller: `public/index.php`
- Core: `Core/` (`Router`, `Controller`, `Auth`, `Database`, CSRF, migrations, mail, etc.)
- App: `App/Controllers`, `App/Models`, `App/Views`
- Server-rendered PHP views (Bootstrap/jQuery), not an SPA framework
- PDO models without a heavy ORM; `Core\Model` is legacy EAV and not used by current domain models

Documented in `docs/DEVELOPMENTGUIDE.md` and `docs/FrameworksGuide.txt`.

## Consequences

- Positive: Explicit control, few framework upgrades, clear request path for security reviews.
- Negative: No framework ecosystem “for free”; contributors must learn project conventions.
- Follow-on: Prefer extending `Core\` / `App\` patterns over introducing a second framework. Composer is used selectively (e.g. mPDF), not as a full stack.

## Alternatives considered

- **Laravel / Symfony** — faster CRUD scaffolding and ecosystem; heavier host requirements, upgrade cadence, and would rewrite auth/routing/migrations already in place.
- **CodeIgniter** — lighter than Laravel but still a framework lock-in and migration cost with little gain over the current Core.
