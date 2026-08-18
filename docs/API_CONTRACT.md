# Simple CMS – REST API contract

JSON envelope on `/api/*`. Import [postman/Simple-CMS-API.postman_collection.json](postman/Simple-CMS-API.postman_collection.json). Set `baseUrl` (example `http://cms.local`). Auth: [API_AUTH.md](API_AUTH.md). Errors: [API_ERROR_CODES.md](API_ERROR_CODES.md).

## Envelope

Success: `{ "success": true, "data": { ... }, "error": null }`  
Failure: `{ "success": false, "data": null, "error": { "code": "...", "message": "..." } }`

## Authenticated CMS (`Authorization: Bearer`)

| Method | Path | Capability | Notes |
|--------|------|------------|--------|
| GET | `/api/pages` | `view_pages` | List |
| GET | `/api/pages/{id}` | `view_pages` | One page (includes `layout_json` when set) |
| POST | `/api/pages` | `add_pages` | Create; optional `layout_json` |
| PATCH | `/api/pages/{id}` | `edit_pages` | Update |
| DELETE | `/api/pages/{id}` | `delete_pages` | Delete |
| GET | `/api/posts` | `view_posts` | List |
| GET | `/api/posts/{id}` | `view_posts` | One post |
| POST | `/api/posts` | `add_posts` | Create |
| PATCH | `/api/posts/{id}` | `edit_posts` | Update |
| DELETE | `/api/posts/{id}` | `delete_posts` | Delete |
| GET | `/api/media` | `view_media` | Library |
| GET | `/api/notifications` | session/API user | In-app notifications |
| GET | `/api/settings/ui` | `view_settings` | UI prefs |
| GET | `/api/system/general` | admin / `view_settings` | General settings (subset; includes newsletter flags) |
| GET | `/api/meta/error-codes` | public | Error code registry |

Exact verbs and paths: see Postman **CMS (authenticated)** and `public/index.php`. Visual layout save from the **frontend editor** is session + CSRF (`POST /admin/builder/.../save`), not Bearer.

## Public (no auth)

`/site.json`, `/blog.json`, `/blog/{slug}.json`, `/p/{slug}.json`, `/sitemap.xml`, `/robots.txt`, `/feed.xml`, `/llms.txt`, `/llms-full.txt`, `/share/media/{id}`, `/search`, `/blog/archive/{year}`, `/blog/archive/{year}/{month}`, `/blog/author/{username}`, `/subscribe`.

## Layout JSON

Page/post create/update may send `layout_json` (string or object). The server runs `LayoutBuilder::normalizeJson`. Invalid or empty layouts store `null`. Compiled CSS is **not** an API field; it is derived on public render. Post write also accepts `is_sticky` (bool) and `published_at` (SQL datetime; future values stay off the public site until due).
