# Simple CMS – Entity relationship (Mermaid)

Active schema is migrations `000`–`018`. Details: [DEVELOPMENTGUIDE.md](DEVELOPMENTGUIDE.md) §5.

```mermaid
erDiagram
  users ||--o{ role_capabilities : has
  roles ||--o{ role_capabilities : grants
  cms_pages ||--o{ cms_pages : parent
  cms_posts }o--|| cms_categories : category
  cms_posts }o--o{ cms_tags : tagged
  cms_post_tags }o--|| cms_posts : post
  cms_post_tags }o--|| cms_tags : tag
  cms_posts ||--o{ cms_comments : comments
  cms_media ||--o{ cms_media_sizes : sizes
  cms_menus ||--o{ cms_menu_items : items
  cms_pages {
    int id
    string layout_json
    string blocks_json
  }
  cms_posts {
    int id
    string layout_json
    string blocks_json
  }
  cms_layout_templates {
    int id
    string layout_json
  }
  backup_archives {
    int id
    string filename
  }
```

Visual layouts are JSON on `cms_pages.layout_json` / `cms_posts.layout_json` (and templates). Compiled CSS is not a table.
