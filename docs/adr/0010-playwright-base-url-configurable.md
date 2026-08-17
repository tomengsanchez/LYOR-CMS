# ADR-0010: Playwright BASE_URL configurable

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization of E2E config practice

## Context

Developers and CI hit different hosts (e.g. `http://cms.local`, staging, CI containers). Hard-coded base URLs in specs break clones and pipelines.

## Decision

Playwright **`baseURL` must be configurable** via environment (typically `BASE_URL`), with a documented local default in `playwright.config.ts`.

- Local secrets/template: `.env.playwright.example` (and gitignored `.env.playwright`)
- Specs and npm scripts should not embed production or machine-specific origins
- Credentials for E2E likewise come from env (`ADMIN_USER` / `ADMIN_PASS`, etc.) as documented in test design docs

## Consequences

- Positive: Same suite runs on any CMS deployment; CI sets `BASE_URL` explicitly.
- Negative: Contributors must copy the example env once; mis-set `BASE_URL` fails opaquely until checked.
- Follow-on: New E2E specs use relative paths against `baseURL`. No impact on MySQL/MariaDB or backup/restore.

## Alternatives considered

- **Hard-coded host in config** — works for one developer; fails for everyone else.
- **Discover URL from PHP config at runtime** — couples Node tests to app bootstrap; env is simpler and standard for Playwright.
