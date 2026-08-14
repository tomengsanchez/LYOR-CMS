# PAPeR – E2E / automated test results log

Living archive of **recorded test runs** (Playwright E2E, API smoke, backup/restore npm scripts, and related CLI suites). Use this so the team can see what was run, on which branch/host, and what failed — without pasting secrets.

**Related:** [TEST_STRATEGY.md](../TEST_STRATEGY.md), [TESTING_HISTORY_PREREQUISITES.md](../TESTING_HISTORY_PREREQUISITES.md), [AUTOMATED_FUNCTIONAL_TEST_DESIGN.md](../AUTOMATED_FUNCTIONAL_TEST_DESIGN.md), [RELEASE.md](../RELEASE.md).

---

## How to record a run

1. Copy [TEMPLATE.md](TEMPLATE.md) to:

   `docs/test-results/YYYY-MM/YYYY-MM-DD-short-slug.md`

   Example: `docs/test-results/2026-08/2026-08-09-e2e-full-dev1.md`

2. Fill in the template (environment, command, pass/fail counts, failures, notes).  
3. Add a row to the **index table** for that month (create `YYYY-MM/README.md` if missing — copy from [YYYY-MM-README-TEMPLATE.md](YYYY-MM-README-TEMPLATE.md)).  
4. Optionally mention the result file in the monthly [CHANGES](../changes/) entry or the PR when it gates a release.

### Rules

- **Do not** commit passwords, tokens, full `.env.playwright`, or production URLs with credentials.  
- Prefer host aliases (`eco.local`, `staging`) over secrets.  
- One file per significant run (full suite, release gate, or notable failure investigation).  
- Tiny local smoke while developing does **not** need a log entry unless you want a trail.  
- Release / staging gates **should** have an entry.

---

## Index (by month)

| Month | Folder |
|-------|--------|
| August 2026 | [2026-08/](2026-08/README.md) |

Add a new `YYYY-MM/` folder when the calendar month rolls over.

---

## Suggested commands to log

| Suite | Typical command |
|-------|-----------------|
| Full E2E | `npm run test:e2e:full` or `test:e2e:all` |
| API smoke | `npm run test:e2e:api` |
| History timezone | `npm run test:e2e:activity-history-timezone:fast` |
| SES | `npm run test:e2e:socio-economic:fast` |
| Escalation / grievance | Module scripts in `package.json` / AUTOMATED_FUNCTIONAL_TEST_DESIGN |
| Backup / restore | `npm run test:backup-restore`, `test:restore-completion-audit`, etc. |

Always note **`BASE_URL`** (value only, no credentials) and **git SHA / branch**.
