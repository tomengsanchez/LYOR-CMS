# Simple CMS – Non-functional requirements

**Related:** [REQUIREMENTS.md](REQUIREMENTS.md), [DEPLOYMENT.md](DEPLOYMENT.md), [RUNBOOK.md](RUNBOOK.md), [SECURITY.md](SECURITY.md).

## Compatibility

| ID | Requirement |
|----|-------------|
| NFR-COMP-01 | PHP **8.0+** |
| NFR-COMP-02 | MySQL **5.7+** and MariaDB **10.2+** (ADR-0003) |
| NFR-COMP-03 | Apache `mod_rewrite` or nginx equivalent; document root preferably `public/` |
| NFR-COMP-04 | Evergreen browsers (no IE) |
| NFR-COMP-05 | Playwright `BASE_URL` configurable (ADR-0010) |

## Performance

| ID | Requirement |
|----|-------------|
| NFR-PERF-01 | Admin lists paginated |
| NFR-PERF-02 | Notification emails queued; worker via cron |
| NFR-PERF-03 | Large backup/restore: raise `max_execution_time`; optional `--large-mode` |

## Availability & DR

ZIP backup (web or CLI); **CLI-only restore**. Keep copies off-server. See [RUNBOOK.md](RUNBOOK.md).

## Accessibility

Public and admin UIs: semantic headings, form labels, keyboard access to the layout builder (shortcuts via **?**).

## Security

See [SECURITY.md](SECURITY.md). CSRF on admin POSTs. HTML modules and compiled CSS are sanitized.
