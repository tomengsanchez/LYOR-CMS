## Local hostname `cms.local` (XAMPP vhost, not in-repo) (2026-09-11)

- Local run target is **`http://cms.local`** with document root `public/`. The VirtualHost is configured on the machine Apache file (`C:/xampp/apache/conf/extra/httpd-vhosts.conf`) plus Windows `hosts` (`127.0.0.1 cms.local`). No vhost file is added to this repository so deploy hosts keep their own Apache/nginx config.
- CORS allow-list in `config/app.php` / `config/app-sample.php` includes `http://cms.local`. Playwright `BASE_URL` already defaults to that origin.
- Backup/schema unchanged. Help: no end-user change (ops/dev only).
