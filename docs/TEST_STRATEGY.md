# PAPeR – Test strategy

How we verify quality. Detail for Playwright scaffolding: [AUTOMATED_FUNCTIONAL_TEST_DESIGN.md](AUTOMATED_FUNCTIONAL_TEST_DESIGN.md). Escalation manual QA: [QA_ESCALATION_REGRESSION.md](QA_ESCALATION_REGRESSION.md).  
**Before testing Activity / Status / SES / notification history:** [TESTING_HISTORY_PREREQUISITES.md](TESTING_HISTORY_PREREQUISITES.md).  
**Record significant E2E / gate runs:** [test-results/README.md](test-results/README.md) (monthly folders + TEMPLATE).

---

## 1. Test pyramid

| Layer | What | Location / command |
|-------|------|--------------------|
| **CLI / unit-style** | Pure PHP checks (escalation math, SQL helpers, locks, API helpers) | `tests/cli/*.php` via npm scripts (e.g. `test:restore-completion-audit`) |
| **API smoke** | Envelope, auth, key list routes | Playwright / API smoke scripts (`npm run test:e2e:api`) |
| **E2E UI** | Login + critical paths across modules | `tests/e2e/` — `npm run test:e2e:full` / module scripts |
| **Manual / UAT** | Escalation edge cases, role matrix, restore drill | QA doc + RUNBOOK §5 |
| **Backup/restore** | Round-trip + sanitize + completion audit | `npm run test:backup-restore`, related CLI tests |

---

## 2. Environments

| Env | Use |
|-----|-----|
| Local | Dev + Playwright against `BASE_URL` (see `.env.playwright.example`) |
| CI | Headless Playwright; set `BASE_URL`, credentials, `CI=1` |
| Staging | UAT + restore drills on non-prod data |

Never point destructive tests (`truncate_fresh_install`, restore overwrite) at production.

---

## 3. Coverage map (modules)

| Area | Automated | Manual / notes |
|------|-----------|----------------|
| Auth / login / sessions | E2E auth helpers; security hardening specs | 2FA email paths |
| Profiles | E2E + API smoke | SES tab, attachments |
| Structures | E2E + API list smoke | Primary/secondary nest |
| Grievances | E2E + many CLI escalation/JSON tests | Full escalation matrix → QA_ESCALATION |
| History (activity / status / SES / notifications) | Timezone + status-log + notifications E2E | Prerequisites → TESTING_HISTORY_PREREQUISITES |
| Library / projects / geo | Partial E2E | Code uniqueness per project |
| Users / roles | Partial E2E | Capability matrix spot-check |
| Backup / restore | Dedicated npm/CLI tests | Ops restore drill |
| Live traffic / blocks | Partial | |
| RAP / SES import | Partial CLI/E2E | Large ZIP edge cases |
| Mobile API | Contract + Postman + smoke | Device UAT |

Update this table when adding suites.

---

## 4. Data policy

- Prefer **Philippine-context** fixtures (names, mobile formats, LGU-style text) — see automated test design.  
- Optional `E2E_DB_SEED` for reset+seed before runs.  
- Keep Playwright credentials out of git.

---

## 5. Definition of done (testing)

For a change that can break behaviour:

1. Relevant CLI or E2E updated or a new case added.  
2. If deferred, note gap here or in the PR/CHANGES entry.  
3. API changes → Postman + `API_CONTRACT` (+ mobile docs if client-facing).  
4. Restore/SQL changes → backup/restore tests green.  
5. For **release gates**, staging verification, or notable full-suite runs: log the result under [test-results/](test-results/README.md).

---

## 6. Known gaps (living)

- Formal WCAG automated audit not standardized.  
- Full capability × role × route matrix automation incomplete (spot-check via Roles UI + API 403s).  
- Load/performance suite not established (see NFR TBD).  
- Mobile device farm UAT is manual.

Remove items as coverage lands.
