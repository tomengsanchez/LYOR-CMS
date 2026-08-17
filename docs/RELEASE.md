# Simple CMS – Release process

Versioning is **calendar / CHANGES-driven**. If you tag git, use `YYYY.MM.DD` or SemVer and record it in CHANGES.

## Pre-release checklist

- [ ] Feature branch merged; agreed CMS smoke / Playwright green
- [ ] New migrations have `up` + `down`; migrate/rollback on a copy
- [ ] MySQL **and** MariaDB considered
- [ ] Backup/restore impact checked (`layout_json`, uploads, `app_settings`)
- [ ] [changes/YYYY-MM/CHANGES.md](changes/) + DevelopmentHistory if substantial
- [ ] ADR if durable decision
- [ ] `API_CONTRACT` + Postman if API changed
- [ ] Help updated if users need guidance
- [ ] No secrets committed

## Staging

1. Backup staging DB + uploads
2. Deploy; `composer install` if needed
3. `php cli/migrate.php`
4. Smoke: login, edit a page, visual builder save, public `/p/{slug}`, backup create
5. Restore drill on a **clone** if restore-related

## Production

Backup first. Deploy, migrate, smoke the public homepage and `/admin`. Keep the previous ZIP off-server.
