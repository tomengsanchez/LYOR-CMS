# Simple CMS – Configuration catalog

What to configure for local, staging, and production. **Never commit secrets** (`config/database.php`, live SMTP keys, `.env.playwright`, API keys).

**Related:** [RUNBOOK.md](RUNBOOK.md), [DEPLOYMENT.md](DEPLOYMENT.md), [SECURITY.md](SECURITY.md).

---

## 1. Files on disk

| Path | Purpose | Committed? |
|------|---------|------------|
| `config/database-sample.php` | Template DB credentials | Yes |
| `config/database.php` | Live PDO DSN credentials | **No** (local/deploy) |
| `config/app-sample.php` | Optional `base_url` template | Yes |
| `config/app.php` | Optional `base_url` for subfolder installs | Usually no |
| `.env.playwright.example` | Playwright `BASE_URL`, admin creds template | Yes |
| `.env.playwright` | Local E2E secrets | **No** (gitignored) |
| `composer.json` / `vendor/` | PHP deps (mPDF) | lock yes; vendor via `composer install` |
| `storage/backups/` | Backup ZIPs | **No** — treat as secret |
| `logs/` | php_error, database_error, auth, help_chat_rate | No |

Copy samples:

```bash
cp config/database-sample.php config/database.php
cp config/app-sample.php config/app.php   # optional
cp .env.playwright.example .env.playwright
```

Env overrides used by CLI backup/restore:

| Variable | Purpose |
|----------|---------|
| `MYSQLDUMP_PATH` | Path to `mysqldump` if not on PATH |
| `MYSQL_PATH` | Path to `mysql` client for restore |
| `BASE_URL` | Playwright base URL |
| `ADMIN_USER` / `ADMIN_PASS` | Playwright login |
| `CI` | Headless Playwright in CI |
| `E2E_DB_SEED` | Optional DB reset+seed before E2E |

---

## 2. `app_settings` (database)

Key/value store (`App\Models\AppSettings`). Managed mainly via System / Settings UIs. Non-exhaustive groups:

### General (`App\GeneralSettings` + branding via `App\Models\AppSettings`)
- Org **timezone**, **region**
- Branding: `app_name`, `company_name`, `app_logo_path`
- **Public site theme** (`App\PublicTheme`): presets (incl. **editorial**/crimson, amber/indigo/coral/mint), fonts/size/line height, radius, widths (default **full** 1320px), color mode, header/footer, button/shadow/link/spacing, sticky header, show admin link, custom colors. Editorial magazine options: `pub_theme_chrome` (`default`|`editorial`), `pub_theme_blog_kicker`, `pub_theme_date_format`, `pub_theme_show_site_tagline`; checkbox `pub_theme_apply_editorial_pack` applies the reusable style pack. Blog list: `pub_theme_blog_list_style` (list/grid/cards/magazine/compact), columns, image ratio, excerpt/read-more/category/view-switcher. Live editor: `/admin/customize`. **Style pack upload** (`App\ThemeStylePack`): CMS zip (`cms-theme.json` + optional `extra.css`) or WordPress theme zip (colors/metadata only); bundled packs include **Manly** (`?pack=manly`); settings keys `pub_theme_pack_name` / `pub_theme_pack_source` / `pub_theme_pack_css` / library; requires PHP `zip` extension.
- **Per content:** `cms_pages.content_layout`, `cms_posts.content_layout`, `cms_pages.parent_id`
- **Reading:** `reading_show_on_front`, `reading_page_on_front`, `reading_posts_per_page`
- **Discussion:** `discussion_comments_enabled`, `discussion_moderation`, `discussion_require_name_email`, `discussion_show_sidebar`, `discussion_comment_rate_limit` (max comments per IP per hour; 0 = unlimited)
- **Permalinks:** `permalink_page_structure`, `permalink_post_structure`
- **Site SEO / LLM:** `seo_title_suffix`, `seo_default_description`, `seo_default_image_id`, `seo_twitter_handle`, `seo_google_site_verification`, `seo_locale`, `seo_site_keywords`, `llm_site_summary`, `seo_enable_json_export`, `seo_enable_sitemap`, `seo_enable_rss_feed`, `seo_enable_llms_txt`, `seo_allow_ai_crawlers`, `seo_facebook_url`, `seo_linkedin_url`, `seo_reddit_url`, `seo_youtube_url`, `seo_publisher_expertise`, `seo_preferred_citation`, `seo_citation_guidance`, `seo_pillar_topics`, `seo_enable_faq_schema`, `seo_enable_speakable`, `seo_show_ai_writing_tips`
- Ask Help keys remain in schema but widget is disabled in Simple CMS

### Security
- Login throttling / lockout related keys  
- `enable_email_2fa`, `2fa_expiration_minutes`  
- `user_logout_after_minutes` (idle web session; 0 = off)  
- Password policy keys (expiry, history count, etc.)  
- `api_token_expiry_days`, `api_token_idle_minutes`  
- Realtime security thresholds (failed login auto-block, malware check settings)

### Email
- `email_provider`: `smtp` | `mailersend` | `log` (dev)  
- SMTP / MailerSend credentials and “send email for project notifications” flags  

### Operational
- Default field role, holidays (table `holidays` + settings as applicable)  
- Project escalation count-start related settings  

### Live traffic (migration 087+)
- Retention / enable flags as seeded in migration  

### UI / per-user (not all in `app_settings`)
- `user_dashboard_config` modules: `ui`, notification preferences, list columns, grievance dashboard widgets  

When adding a setting: document it here, prefer UI over raw SQL, and note backup impact (`app_settings` is in SQL dumps).

---

## 3. Runtime PHP / server

| Setting | Guidance |
|---------|----------|
| `date.timezone` | Prefer org timezone via app (`UserTime`); keep php.ini sensible |
| `upload_max_filesize` / `post_max_size` | Must allow SES ZIP (docs mention **10 MB** UI cap) and grievance/profile attachments |
| `max_execution_time` | Raise for large CLI restore/backup |
| Document root | Point to `public/` when possible; root `.htaccess` can forward |

---

## 4. Cron / scheduled jobs

| Job | Command | Frequency |
|-----|---------|-----------|
| Email queue | `php cli/send_queued_emails.php` | Every 1–5 minutes if email notifications used |
| Traffic prune | `php cli/prune_live_traffic.php` | Per retention policy (e.g. daily/weekly) |
| Backup | `php cli/backup.php` | At least daily in production; copy off-server |

Working directory must be **project root**.

---

## 5. Environment checklist

| Item | Local | Staging | Production |
|------|-------|---------|------------|
| `config/database.php` | Dev DB | Staging DB | Prod DB |
| Default `admin` / `admin123` | OK | Change | **Must change** (deploy checks) |
| Email provider | `log` or test SMTP | Real test inbox | Production provider |
| 2FA | Optional | Recommended | Recommended |
| `dev-help/` exposed | Localhost OK (`Require local`) | Restrict | **Not public** (prefer `public/` docroot; see DEPLOYMENT §4.1) |
| Backup offsite | Optional | Yes | **Required** |
| HTTPS | Optional | Yes | **Required** |
