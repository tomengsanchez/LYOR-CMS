# PAPeR – Deployment architecture

How to host PAPeR safely. **Related:** [CONFIGURATION.md](CONFIGURATION.md), [RUNBOOK.md](RUNBOOK.md), [NFR.md](NFR.md), DEVELOPMENTGUIDE multi-app-server note.

---

## 1. Reference topology (single node)

```text
Internet → TLS terminator (nginx/Apache/Caddy)
         → PHP-FPM or Apache mod_php
         → public/index.php (front controller)
         → PDO → MySQL/MariaDB (same host or nearby)
Local disk: public/uploads/, storage/backups/, logs/
Cron on same host (or worker host with code + DB access)
```

**Preferred document root:** `public/`. If document root is project root, root `index.php` / `.htaccess` forward into `public/`.

---

## 2. Multi app-server

Sequential IDs (PAPSID, STRID, grievance case numbers) use **`GET_LOCK` / `RELEASE_LOCK`** via `Core\MySqlNamedLock`.

| OK | Not OK without extra design |
|----|-----------------------------|
| Multiple PHP nodes → **one MySQL primary** for writes | Galera multi-primary / sharded DB without external coordinator |
| Read replicas for reporting (app must not write to replicas) | Relying on locks across disconnected databases |

Session stickiness or shared session storage is required if using multiple web nodes with PHP file sessions (default file sessions are **per node**). Plan Redis/DB sessions or sticky sessions before scaling out.

Uploads (`public/uploads/`) and `storage/backups/` must be on **shared storage** or synchronized if multiple nodes serve files.

---

## 3. Deploy checklist

1. Provision PHP 8+, MySQL/MariaDB, TLS.  
2. Deploy code; `composer install --no-dev` (or with dev deps only on non-prod).  
3. Create `config/database.php` (and optional `config/app.php`).  
4. `php cli/migrate.php`.  
5. `php database/seeders/seed_grievance_options.php` on fresh DBs.  
6. Change default admin password; enable 2FA if appropriate.  
7. Configure email + cron.  
8. Restrict `dev-help/` from public internet (prefer `public/` docroot; see §4.1). Verify external `/dev-help/` → 403/404.  
9. Smoke: login, create profile, grievance list, `GET /api/meta/error-codes`.  
10. Configure backup job + offsite copy; run one restore **drill** on a clone.  

Production gate scripts (if present in repo) should fail when default `admin123` remains — see README.

---

## 4. Web server notes

- Enable rewrite so all non-file routes hit the front controller.  
- Deny direct access to `config/`, `database/`, `cli/`, `logs/`, `storage/` from the web.  
- Set upload size limits for attachments and SES ZIPs.  
- HTTPS only in production; secure cookies when TLS is on.  

### 4.1 `dev-help/` (developer guide)

The guide under `dev-help/` is **not** behind PAPeR login. Treat it like internal docs.

| Hosting | What happens |
|---------|----------------|
| **DocumentRoot = `public/`** (recommended) | `dev-help/` is outside the web root — not HTTP-reachable. Best for production. |
| **DocumentRoot = project root** | Repo defaults: root `.htaccess` returns **403** for `/dev-help` unless the client is `127.0.0.1` / `::1`; `dev-help/.htaccess` uses `Require local`. |

**nginx** (if document root is the project root), deny public access:

```nginx
location ^~ /dev-help/ {
    allow 127.0.0.1;
    allow ::1;
    deny all;
}
```

Or omit `dev-help/` from the production deploy package. After deploy, confirm from an external network that `https://your-host/dev-help/` is **403/404**, not the guide.

---

## 5. Integrations

| Integration | Config surface |
|-------------|----------------|
| SMTP / MailerSend | Email settings / `app_settings` |
| OpenAI-compatible Help chat | Help chat settings (optional) |
| Geo / Whois (Live Traffic) | Outbound HTTPS from app host |
| Mobile apps | Public HTTPS base URL + API tokens |

---

## 6. Environments

| Env | Data | Notes |
|-----|------|-------|
| Local | Disposable | XAMPP/Laragon OK; `dev-help` useful |
| Staging | Anonymized or subset prod | Test migrate + restore here |
| Production | Live PII | Backups offsite; no default passwords; no public `dev-help` |
