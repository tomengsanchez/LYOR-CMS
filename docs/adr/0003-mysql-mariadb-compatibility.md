# ADR-0003: MySQL and MariaDB compatibility

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization of an existing constraint

## Context

Deployments may use **MySQL 5.7+** or **MariaDB 10.2+**. SQL, dumps, and restore paths must not assume one vendor’s dialect. Past restore work already had to sanitize MariaDB sandbox banners and generated-column dumps for older clients.

## Decision

Treat **MySQL and MariaDB compatibility** as a hard constraint:

- Prefer portable SQL (utf8mb4, standard PDO usage)
- Avoid MySQL-only features when they break MariaDB (and vice versa) unless gated with a tested fallback
- Backup/restore helpers must keep dumps importable on both (see `cli/backup_sql_helper.php`, restore completion audit)
- Note the constraint in `config/database-sample.php` and developer docs

## Consequences

- Positive: Same app and backups work across common hosting stacks.
- Negative: Cannot freely adopt newest vendor-specific JSON/index features without a fallback.
- Follow-on: When adding schema or raw SQL, verify both engines; include restore/backup impact in design notes. See also ADR-0008.

## Alternatives considered

- **MySQL-only** — simpler SQL surface; excludes common MariaDB hosts.
- **MariaDB-only** — same problem in reverse.
- **Abstract DB layer / dual dialects** — high cost for this codebase size; portable SQL is enough.
