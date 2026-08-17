# Simple CMS – Product / functional requirements

Living requirements for **Simple CMS**. Use this for scope and acceptance. In-app Help: `/admin/help`.

**Related:** [NFR.md](NFR.md), [GLOSSARY.md](GLOSSARY.md), [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md).

---

## 1. Purpose

Provide a public website and staff admin (`/admin`) to:

1. Publish **pages** and **blog posts** (visual layout, blocks, or HTML body).
2. Manage **media**, **menus**, **comments**, **widgets**, and **theme** appearance.
3. Enforce **role/capability** access, audit history, notifications, and **backup/restore**.

---

## 2. Stakeholders

| Persona | Needs |
|---------|--------|
| **Administrator** | Full config, users/roles, backup, security |
| **Editor** | Create/edit pages, posts, media |
| **Visitor** | Public pages, blog, RSS, SEO/JSON-LD |
| **Ops** | Deploy, cron, backup/restore, DB credentials |

---

## 3. In scope

- Custom PHP MVC (Bootstrap) admin + public site
- REST API under `/api/*` (auth + CMS pages/posts/media/settings)
- Soft delete + restore where the UI provides it
- ZIP backup (UI/CLI) and **CLI-only** restore
- MySQL and MariaDB

---

## 4. Out of scope

- Project-affected persons, structures, grievances, SES, RAP, or GRM workflows
- Mobile-only domain APIs for those workflows
