# Simple CMS – Operations runbook

**Related:** [CONFIGURATION.md](CONFIGURATION.md), [DEPLOYMENT.md](DEPLOYMENT.md), ADR-0008, Help → Backup/Restore.

## Service map

| Component | Role |
|-----------|------|
| PHP (Apache/nginx) | Public site + `/admin` + `/api/*` |
| MySQL or MariaDB | Primary datastore |
| `public/uploads/` | Media |
| Cron | Email queue; optional backup |
| `storage/backups/` | ZIP backups (copy off-server) |
| `logs/` | php_error, database_error, auth |

## Routine checks

| Check | How |
|-------|-----|
| App | HTTPS homepage and `/admin/login` |
| DB | `php cli/migrate.php --status` |
| Email queue | `email_queue` pending/failed |
| Last backup | `storage/backups` / `backup_archives` |
| Disk | backups + uploads |
| PHP errors | `logs/php_error.log` |

## Cron (from project root)

```bash
php cli/send_queued_emails.php
# optional
php cli/backup.php
```

## Backup / restore

- **Create:** System → Backup & Restore, or `php cli/backup.php`. Optional `--no-uploads`.
- Credentials for `mysqldump`/`mysql` are passed via a quoted temp option file (same `config/database.php` as PDO). If `mysqldump` fails, backup falls back to the PHP PDO exporter.
- **Restore:** CLI only: `php cli/restore.php` (not in the web UI).
- Visual layouts restore with SQL (`layout_json`). Theme files in uploads restore with media.
- New ZIP app id is `SimpleCMS`; restore still accepts older `PAPeR` manifests if you have them.

Drill restore on a **copy** of production, never the live DB first.

## Incidents

1. Capture `logs/` and last backup name.
2. Do not restore over production without a new backup of the broken state.
3. After restore, run `php cli/migrate.php --status`.
