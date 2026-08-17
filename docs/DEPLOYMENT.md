# Simple CMS – Deployment

**Related:** [CONFIGURATION.md](CONFIGURATION.md), [RUNBOOK.md](RUNBOOK.md), [NFR.md](NFR.md).

## Topology

Single app server + one MySQL/MariaDB primary is the supported default. Multiple app servers sharing one DB is fine for reads; named locks and sessions assume **one primary**.

1. Document root → `public/`
2. Copy `config/database-sample.php` → `config/database.php`
3. Optional `config/app.php` `base_url` for subfolder hosts
4. `php cli/migrate.php`
5. Default login after fresh migrate: see README (change immediately)
6. HTTPS, file permissions on `public/uploads`, `storage/`, `logs/`

## PHP

`upload_max_filesize` / `post_max_size` large enough for media. Raise `max_execution_time` for CLI restore.

## Playwright

`BASE_URL` from `.env.playwright` or the environment (example `http://cms.local`). Do not commit credentials.
