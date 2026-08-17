# Simple CMS – Glossary

| Term | Meaning |
|------|---------|
| **Simple CMS** | This application: public site + `/admin` for pages, posts, media, and site settings. |
| **Visual layout / layout builder** | Section → Row → Column → Module editor. Stored as `layout_json` on pages and posts. |
| **Block builder** | Alternate JSON `blocks_json` used when no visual layout is present. |
| **Content width** | Per-page/post layout width override (full / wide / normal / narrow). |
| **Style pack** | Zip (`cms-theme.json` + optional `extra.css`) imported in Appearance → Customize. |
| **Capability** | Named permission string checked via `Auth::can` (e.g. `edit_pages`). |
| **Role** | Named role (Administrator, custom) with a capability set. |
| **API envelope** | `{ success, data, error }` wrapper on `/api/*` responses. |
| **Bearer token** | Hashed API credential in `api_tokens` for non-session clients. |
| **CSRF** | Token required on admin POST actions. |
| **layout_json** | Serialized visual layout. Public CSS is **derived** at render time, not stored. |
