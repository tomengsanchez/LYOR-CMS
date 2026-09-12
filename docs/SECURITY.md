# Simple CMS – Security policy & threat model sketch

**Related:** ADR-0004, ADR-0007, [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md), [CONFIGURATION.md](CONFIGURATION.md), [NFR.md](NFR.md).

Root [`SECURITY.md`](../SECURITY.md) points here for vulnerability reporting.

## Principles

1. Least privilege via **capabilities**.
2. Session + CSRF (web), hashed Bearer tokens (API), throttling, optional email 2FA.
3. Secrets never in git; backup ZIPs are secrets.
4. Sanitize uploads and HTML modules; allowlisted CSS in the layout compiler.
5. Durable decisions as ADRs.

## Trust boundaries

```text
[Browser] --HTTPS--> [PHP]
                       |-- session / Bearer --> Auth
                       |-- PDO --> [Database]
                       |-- files --> [public/uploads]
[Operator CLI] --> backup / restore / migrate
```

Untrusted: all HTTP input, uploads, HTML module markup, email content.

Draft/scheduled **preview** (`?preview=1`) requires an authenticated session with `edit_pages` or `edit_posts`. It is not a shareable secret URL.

**Password-protected content** stores `password_hash` only (PHP `password_hash`). Public JSON, OG, RSS descriptions, search, and `llms.txt` must not include the body until the visitor unlocks (`ContentPassword`). Failed unlocks are rate-limited in session. Unlock cookies are HMAC’d with `cms_content_pass_key` in `app_settings` (created on first use). Changing the content password invalidates prior cookies. REST list/get omit `password_hash`.

**Newsletter** stores emails in `cms_newsletter_subscribers`. Public signup uses CSRF, a honeypot (`website`), a consent checkbox, and a per-IP/session rate limit. Double opt-in confirm tokens are 64 hex chars (7-day TTL). Unsubscribe is GET (prompt) then POST. Do not expose tokens on the REST API. Generic success copy must not reveal whether an address is already subscribed.

## Assets

| Asset | Sensitivity |
|-------|-------------|
| User accounts / sessions | High |
| Backup ZIPs (DB + uploads) | High |
| Media uploads | Medium |
| Public pages/posts | Low–medium |

## Layout builder

`layout_json` is normalized on save. Design CSS uses allowlists (`safeColor`, `safeSpacing`, box-shadow keys, font-family keys, letter-spacing keys, text-transform, sticky/relative position, z-index 1–100, shape divider keys). Custom HTML modules strip script/iframe/form tags on public render. Text / CTA / Blurb / Accordion / Tabs bodies use `sanitizeRichText` (allowlisted tags only; unknown tags unwrapped; `script`/`iframe` removed; `javascript:` hrefs dropped). Do not store raw user CSS. Absolute/fixed positioning is not accepted. Section shape dividers are hardcoded SVG only.

**Video module:** only YouTube, Vimeo, or HTTPS `.mp4`/`.webm`/`.ogg` URLs survive normalize. Public embeds use constructed `youtube-nocookie.com` / `player.vimeo.com` iframe `src` (or a native `<video>`). The default CSP `frame-src` / `media-src` in `Core\SecurityHeaders` matches that allowlist. Section video backgrounds use the same parser and construct mute/loop query params in PHP (never a user-supplied iframe `src`). The editor canvas never loads those iframes.

**Google tags (System → General):** store measurement / publisher / CSE IDs only. `GoogleSettings` rejects values that do not match `G-…` / `UA-…` / `AW-…` / `ca-pub-…` / CSE CX. The public site never accepts pasted script snippets. Default CSP `script-src` / `connect-src` / `frame-src` include official Google Analytics, Ads, AdSense, and Programmable Search hosts. Theme preview and the Customizer iframe do not inject tags.

**Accordion / Tabs / Icon list / Gallery / Testimonials:** titles, icons, captions, quotes, and names are HTML-escaped. Accordion and Tabs **bodies** are allowlisted rich text (same as Text/CTA/Blurb), not raw HTML. Gallery and carousel links use `safeUrl`. Tabs use constructed radio `name`/`id` from the module id. Public UI needs no extra JS except the existing carousel script.

**Inner row:** only one nested row is stored. A nested `inner_row` inside an inner column is dropped during `normalize`. Nested module markup still goes through the same type sanitizers.

## Reporting

Follow the root `SECURITY.md` contact process. Do not file public issues with exploit details.
