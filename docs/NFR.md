# PAPeR – Non-functional requirements

Targets and constraints. Adjust numbers when measured in production; mark TBD where not yet baselined.

**Related:** [REQUIREMENTS.md](REQUIREMENTS.md), [DEPLOYMENT.md](DEPLOYMENT.md), [RUNBOOK.md](RUNBOOK.md), [SECURITY.md](SECURITY.md).

---

## 1. Compatibility

| ID | Requirement |
|----|-------------|
| NFR-COMP-01 | PHP **8.0+**. |
| NFR-COMP-02 | MySQL **5.7+** and MariaDB **10.2+** (portable SQL; see ADR-0003). |
| NFR-COMP-03 | Apache `mod_rewrite` or nginx equivalent; document root preferably `public/`. |
| NFR-COMP-04 | Staff UI: modern evergreen browsers (Chrome/Edge/Firefox/Safari recent). No IE. |
| NFR-COMP-05 | Playwright `BASE_URL` configurable (ADR-0010). |

## 2. Performance & scale

| ID | Requirement | Target / note |
|----|-------------|----------------|
| NFR-PERF-01 | List pages paginated; avoid loading unbounded rows in UI. | Default page sizes; PDF list clamp **10–500** (default 250). |
| NFR-PERF-02 | Respondent name autocomplete requires ≥3 characters (first name). | Server-enforced. |
| NFR-PERF-03 | Sequential IDs (PAPSID, STRID, case numbers) use MySQL named locks. | Multi-app-server OK only against **one primary**. |
| NFR-PERF-04 | Notification emails are queued; worker sends async. | Cron every 1–5 min. |
| NFR-PERF-05 | Large backup/restore | `--large-mode` for GB-scale paths. |
| NFR-PERF-06 | Concurrent API writes | Idempotency keys supported for designated flows. |

Baselines for p95 latency under N concurrent users: **TBD** (measure on staging).

## 3. Availability & DR

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-AV-01 | Planned maintenance window communication | Ops process (see RUNBOOK). |
| NFR-AV-02 | **RPO** (max acceptable data loss) | **≤ 24 h** if daily backups; tighten if hourly backups adopted. |
| NFR-AV-03 | **RTO** (restore to usable) | **≤ 4 h** for typical DB size with CLI restore + tested drill; larger DBs TBD. |
| NFR-AV-04 | Restore is CLI-only with completion audit + auto-rollback on mismatch | ADR-0008. |
| NFR-AV-05 | Off-server backup copies | Required for production. |

## 4. Security (NFR slice)

| ID | Requirement |
|----|-------------|
| NFR-SEC-01 | Passwords hashed; optional history / expiry policies. |
| NFR-SEC-02 | CSRF on web mutating POSTs; API uses Bearer (no CSRF). |
| NFR-SEC-03 | API tokens stored hashed; absolute + idle expiry. |
| NFR-SEC-04 | Session fixation mitigated (`session_regenerate_id` on login). |
| NFR-SEC-05 | Login throttling / optional realtime auto-block. |
| NFR-SEC-06 | Security headers via `Core\SecurityHeaders` where enabled. |
| NFR-SEC-07 | Backup ZIPs treated as **secret** (full DB + uploads). |

Detail: [SECURITY.md](SECURITY.md).

## 5. Privacy & retention

| ID | Requirement | Note |
|----|-------------|------|
| NFR-PRI-01 | Soft delete retains rows until purged by policy/ops | No automatic hard-purge job documented yet. |
| NFR-PRI-02 | Traffic events prunable | `php cli/prune_live_traffic.php`. |
| NFR-PRI-03 | Audit log growth | Monitor size; retention policy **TBD** per org. |
| NFR-PRI-04 | Ask Help must not send profile/grievance row PII to LLM | Help text only (see DEVELOPMENTGUIDE). |

## 6. Usability & accessibility

| ID | Requirement |
|----|-------------|
| NFR-UX-01 | Responsive shell (drawer/offcanvas under ~992px). |
| NFR-UX-02 | External JS only; config bridges for PHP → JS. |
| NFR-UX-03 | In-app Help per major screen; Admin Guide for admins. |
| NFR-A11Y-01 | Aim for usable keyboard access on primary forms | Formal WCAG level **TBD**; fix regressions in E2E/manual QA. |

## 7. Maintainability

| ID | Requirement |
|----|-------------|
| NFR-MAINT-01 | Schema via numbered PHP migrations with `up`/`down`. |
| NFR-MAINT-02 | Docs pack kept current per [DOCUMENTATION.md](DOCUMENTATION.md) update rules. |
| NFR-MAINT-03 | ADRs for irreversible choices. |

## 8. Observability

| ID | Signal | Location |
|----|--------|----------|
| NFR-OBS-01 | PHP / DB / auth errors | `logs/` |
| NFR-OBS-02 | Email queue depth / failures | `email_queue`, Realtime Dashboard KPIs |
| NFR-OBS-03 | Last backup age | Realtime Dashboard / `backup_archives` |
| NFR-OBS-04 | Live traffic / blocks | System Live Traffic |
| NFR-OBS-05 | Failed logins | Security / realtime settings |

Alerting thresholds: org-specific; document in RUNBOOK when set.
