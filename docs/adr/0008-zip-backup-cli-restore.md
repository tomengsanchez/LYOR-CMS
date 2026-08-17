# ADR-0008: ZIP backup and CLI-only restore

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization; includes 2026-08 restore hardening

## Context

Operators need full application recovery (database + `public/uploads`) without exposing destructive restore through the web UI. Restores must survive MySQL/MariaDB dump quirks and detect silent data loss after import.

## Decision

- **Backup:** ZIP via admin UI and/or `php cli/backup.php` (DB dump + uploads + manifest/`schema` snapshot).
- **Restore:** **CLI-only** — `php cli/restore.php --from=path/to/backup.zip` (web UI is backup/download only).
- Prefer `mysqldump` / `mysql` clients when available; sanitize dumps for portable import (MariaDB sandbox banners, generated-column INSERTs, etc.).
- Hardening expectations: schema wipe before import (optional keep-extra-tables), completion audit of INSERT vs live counts, auto-rollback from safety ZIP on audit failure, migration runner that does not blindly `down()` on “already exists” after restore.

See README backup section, Help backup-restore page, and `docs/DEVELOPMENTGUIDE.md`.

## Consequences

- Positive: Destructive restore stays out of the browser; audits catch empty-looking restores; MySQL/MariaDB paths share sanitization.
- Negative: Operators need shell access for restore; more flags to document (`--force`, `--no-completion-audit`, etc.).
- Follow-on: Feature work that adds tables/files must remain backup-compatible; update Help/Admin Guide when restore semantics change. Aligns with ADR-0003.

## Alternatives considered

- **In-browser restore upload** — convenient; high risk of accidental wipe and timeout/size limits.
- **DB-only backups** — misses media files under uploads.
- **Vendor snapshots only (hosting panel)** — outside app control; no app-level completion audit or uploads pairing.
