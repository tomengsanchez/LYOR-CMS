# Contributing to PAPeR

Thanks for helping improve PAPeR. This guide keeps changes consistent with project conventions.

**Docs map:** [docs/DOCUMENTATION.md](docs/DOCUMENTATION.md)  
**Architecture how-to:** [docs/DEVELOPMENTGUIDE.md](docs/DEVELOPMENTGUIDE.md)  
**ADRs:** [docs/adr/README.md](docs/adr/README.md)

---

## 1. Development setup

1. Follow [README.md](README.md) (PHP 8+, MySQL/MariaDB, `composer install`, migrations, seed grievance options).  
2. Optional: copy `.env.playwright.example` → `.env.playwright` for E2E.  
3. Open `/dev-help/` locally for ERD/UML and architecture summary (do not expose publicly in prod).

---

## 2. Branching & PRs

- Prefer focused branches (`feature/…`, `fix/…`).  
- Keep PRs reviewable; split large work (see team norms).  
- Describe **why**, risk (auth, migrate, restore), and test evidence.  
- Do not commit secrets (`config/database.php`, `.env.playwright`, backup ZIPs, API keys).

---

## 3. Coding conventions

| Area | Rule |
|------|------|
| JS | **External files only** under `public/assets/js/` — [FRONTEND_JS_CONVENTIONS.md](docs/FRONTEND_JS_CONVENTIONS.md), ADR-0006 |
| AuthZ | `requireCapability` / `requireAuthApi`; update [CAPABILITY_MATRIX.md](docs/CAPABILITY_MATRIX.md) |
| CSRF | Web POSTs: `validateCsrf()` |
| API | JSON envelope; update contract + Postman |
| SQL | MySQL **and** MariaDB portable (ADR-0003) |
| Migrations | `up` + `down`; idempotent DDL guards where needed |
| Backup | New tables/files must remain backup/restore compatible |

---

## 4. Documentation updates (required when applicable)

Use [DOCUMENTATION.md](docs/DOCUMENTATION.md) update rules:

- Monthly **CHANGES** + **DevelopmentHistory** for shipped work  
- **ADR** for binding “why”  
- **ERD** / `erd-diagrams.js` for schema relationships  
- **UML** / `uml-diagrams.js` for auth/request/restore behaviour changes  
- **Help** / Admin Guide when users need guidance  
- **CONFIGURATION** for new settings/env  
- **RUNBOOK** for ops-facing cron/restore changes  

---

## 5. Tests

- Prefer adding/extending Playwright or `tests/cli` coverage — [TEST_STRATEGY.md](docs/TEST_STRATEGY.md).  
- `BASE_URL` must stay configurable.  
- For restore/SQL: run relevant `npm run test:backup-*` / completion-audit scripts.  
- For release gates or significant full-suite runs, log results under [docs/test-results/](docs/test-results/README.md) (no secrets).  

---

## 6. Review checklist (authors & reviewers)

- [ ] Capabilities and project scope correct  
- [ ] No inline JS behaviour in PHP views  
- [ ] API envelope / error codes stable  
- [ ] Migrations safe; MariaDB considered  
- [ ] Docs + Help updated  
- [ ] Tests or explicit deferral noted  

---

## 7. Security

See [docs/SECURITY.md](docs/SECURITY.md). Report vulnerabilities privately per that doc — do not open public exploit issues.
