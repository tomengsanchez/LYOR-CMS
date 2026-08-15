# PAPeR – Operations runbook

Day-2 operations: health, cron, backup/restore drills, incidents.  
**Related:** [CONFIGURATION.md](CONFIGURATION.md), [DEPLOYMENT.md](DEPLOYMENT.md), [NFR.md](NFR.md), ADR-0008, README §10, Help → Backup/Restore.

---

## 1. Service map

| Component | Role |
|-----------|------|
| PHP app (Apache/nginx + PHP) | Web UI + `/api/*` |
| MySQL or MariaDB | Primary datastore |
| `public/uploads/` | Attachments / images |
| Cron host | Email queue, optional prune/backup |
| `storage/backups/` | Local ZIP backups (also copy off-server) |
| `logs/` | Application logs |

---

## 2. Routine checks

| Check | How |
|-------|-----|
| App responds | Hit `/login` over HTTPS |
| DB connectivity | Login works; or `php cli/migrate.php --status` |
| Email queue | System Realtime Dashboard / `email_queue` pending/failed |
| Last backup age | Realtime Dashboard KPI / `storage/backups` / `backup_archives` |
| Disk space | Especially `storage/backups` and uploads |
| Failed logins / blocks | Security + Live Traffic / Blocked IPs |
| PHP errors | `logs/php_error.log`, `logs/database_error.log` |

---

## 3. Scheduled jobs

From **project root**:

```bash
# Email (every 1–5 min if notifications email enabled)
php cli/send_queued_emails.php

# Prune live traffic (schedule per retention)
php cli/prune_live_traffic.php

# Daily backup example
php cli/backup.php
# then copy ZIP off-server
```

Windows Task Scheduler / cron must set working directory to the project root.

---

## 4. Backup

```bash
php cli/backup.php
# optional: --output=... --mysqldump=... --no-uploads --large-mode
```

- UI: **System → Backup/Restore** (create/download only).  
- ZIPs contain DB + uploads + manifest — **treat as secret**.  
- Keep copies **off the app server**.  
- Prefer `mysqldump` on PATH or `MYSQLDUMP_PATH`.

---

## 5. Restore drill (disaster recovery)

**Always practice on a copy of production first.**

```bash
php cli/restore.php --from=path/to/paper-backup-YYYYMMDD-HHMMSS.zip
# automation: add --yes
```

Default behaviour (see README for full flags):

1. Safety backup of current state (`paper-before-restore-*`) unless `--skip-safety-backup`  
2. Schema wipe (unless `--keep-extra-tables`)  
3. Sanitize SQL (MariaDB sandbox banners, generated-column INSERTs, etc.)  
4. Import via `mysql` client or PDO  
5. **Completion audit** (INSERT counts vs live); on fail → auto-rollback unless disabled  
6. Run pending migrations unless `--no-migrate`  

| Flag | When |
|------|------|
| `--force` | Manifest app/dbname mismatch (conscious override) |
| `--no-completion-audit` / `--no-auto-rollback` | Rare debugging only |
| `--large-mode` | Very large archives |

**RPO/RTO targets:** [NFR.md](NFR.md) §3. Record drill date/results in ops notes.

---

## 6. Migrations

```bash
php cli/migrate.php
php cli/migrate.php --status
php cli/migrate.php --rollback          # last one
php cli/migrate.php --rollback --steps=N
```

- Take a backup before production migrate.  
- DDL auto-commits on MySQL — follow DEVELOPMENTGUIDE migration safety notes.  

---

## 7. Common incidents

### 7.1 Users cannot log in
- Check idle timeout redirect (`?timeout=1`)  
- 2FA email delivery / queue worker  
- Login throttle / IP block (Live Traffic / Blocked IPs)  
- Password expiry policy  

### 7.2 API 401 / 403
- Token expired or idle-revoked  
- Missing capability or project scope  
- Email 2FA blocking API login  

### 7.3 Empty lists after restore
- Suspect sanitize/import loss → completion audit should have failed; check restore logs  
- Confirm correct database in `config/database.php`  
- Re-run from known-good offsite ZIP  

### 7.4 Email not sending
- `email_provider` and credentials  
- Cron running `send_queued_emails.php`  
- Rows stuck `failed` in `email_queue`  

### 7.5 Disk full
- Prune `traffic_events`, old backups (keep offsite copies), old logs  
- SES/attachment growth under `public/uploads`  

### 7.6 Escalation “wrong”
- See [QA_ESCALATION_REGRESSION.md](QA_ESCALATION_REGRESSION.md)  
- Holidays / business days / note-only vs status change  

### 7.7 `/dev-help/` reachable from the internet
- Prefer DocumentRoot = `public/` (folder not under web root).  
- If project-root docroot: confirm root `.htaccess` + `dev-help/.htaccess` (`Require local`) are deployed; nginx needs an explicit deny (DEPLOYMENT §4.1).  
- From an external IP, `https://host/dev-help/` must be **403/404**.  

---

## 8. Destructive local reset (never production)

```bash
php cli/truncate_fresh_install.php   # prompts YES
# or non-interactive:
php cli/truncate_fresh_install.php --yes
```

Clears Simple CMS content + ops tables; keeps migrations, roles, and `admin`. By default re-seeds Welcome / Hello World / Primary Menu. Optional: `--no-reseed`, `--keep-uploads`. See DEVELOPMENTGUIDE (Fresh-install truncate).

---

## 9. Contacts / escalation

| Topic | Owner (fill in for your org) |
|-------|------------------------------|
| App deploy | TBD |
| Database | TBD |
| DNS / TLS | TBD |
| Security incident | TBD |

Update this table for your deployment.
