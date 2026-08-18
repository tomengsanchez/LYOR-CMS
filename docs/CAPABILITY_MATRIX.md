# Simple CMS – Capability matrix

Source of truth is `App\Capabilities`. **Administrator** bypasses capability checks.

**Related:** ADR-0007, `role_capabilities`, Roles UI.

| Role | Intent |
|------|--------|
| **Administrator** | Full access (bypass) |
| **Custom roles** | Grants configured under User Roles |

## Capabilities

| Module | Capability | Typical surface |
|--------|------------|-----------------|
| Pages | `view_pages`, `add_pages`, `edit_pages`, `delete_pages` | `/admin/pages`, visual builder, API pages |
| Posts | `view_posts`, `add_posts`, `edit_posts`, `delete_posts`, `moderate_comments` | `/admin/posts`, comments |
| Newsletter | `view_subscribers`, `manage_subscribers`, `export_subscribers` | `/admin/subscribers`, CSV export |
| Categories | `view_categories`, `manage_categories` | Categories + tags |
| Media | `view_media`, `upload_media`, `delete_media` | Media library, builder uploads |
| Settings | `view_settings`, `manage_settings` | General, widgets, menus, customize, backup |
| Email | `view_email_settings`, `manage_email_settings` | Email settings |
| Security | `view_security_settings`, `manage_security_settings` | Security settings |
| Users | `view_users`, `add_users`, `edit_users`, `delete_users`, `export_users` | Users |
| Roles | `view_roles`, `add_roles`, `edit_roles` | User Roles |

Menu keys (`dashboard`, `pages`, `posts`, `media`, …) map to these capabilities in `Capabilities::menuCapability()`.
