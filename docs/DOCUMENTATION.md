# Simple CMS – Software engineering documentation map

Single index of **engineering** docs. End-user Help remains in-app (`/admin/help`).

| Layer | Document | Purpose |
|-------|----------|---------|
| **Product** | [REQUIREMENTS.md](REQUIREMENTS.md) | Goals, scope, functional requirements |
| | [NFR.md](NFR.md) | Non-functional targets |
| | [GLOSSARY.md](GLOSSARY.md) | CMS terms |
| **Architecture** | [DEVELOPMENTGUIDE.md](DEVELOPMENTGUIDE.md) | Living how-to, schema, conventions |
| | [FrameworksGuide.txt](FrameworksGuide.txt) | Compact stack summary |
| | [adr/](adr/README.md) | Architectural Decision Records |
| | [ERD.md](ERD.md) | Data model (Mermaid) |
| | [UML.md](UML.md) | Structure & behaviour (Mermaid) |
| | [DEPLOYMENT.md](DEPLOYMENT.md) | Hosting topology |
| **Access** | [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md) | Roles, capabilities, menus |
| **API** | [API_CONTRACT.md](API_CONTRACT.md), [API_AUTH.md](API_AUTH.md), [API_ERROR_CODES.md](API_ERROR_CODES.md) | REST contract |
| | [postman/](postman/README.md) | Postman collection |
| | [samples/](samples/README.md) | Theme style-pack fixtures |
| **Ops** | [RUNBOOK.md](RUNBOOK.md) | Day-2 ops, cron, backup/restore |
| | [CONFIGURATION.md](CONFIGURATION.md) | Config files, `app_settings`, env vars |
| **Security** | [SECURITY.md](SECURITY.md) | Policy, threat model sketch, disclosure |
| **Quality** | [TEST_STRATEGY.md](TEST_STRATEGY.md) | Test pyramid |
| | [test-results/](test-results/README.md) | Logged E2E runs (by month) |
| **Delivery** | [RELEASE.md](RELEASE.md) | Release checklist |
| | [CHANGES.md](CHANGES.md) + [changes/](changes/) | What shipped |
| | [DevelopmentHistory/](DevelopmentHistory/) | Day-by-day notes |
| **Frontend** | [FRONTEND_JS_CONVENTIONS.md](FRONTEND_JS_CONVENTIONS.md) | External JS rules |

**Root:** [README.md](../README.md) (setup), [SECURITY.md](../SECURITY.md) (points here).

### Update rules

- Feature changes → monthly `changes/YYYY-MM/CHANGES.md` + `DevelopmentHistory/` as needed
- Schema → `ERD.md` + DEVELOPMENTGUIDE
- Durable “why” → new ADR
- New capability → `Capabilities.php` + `CAPABILITY_MATRIX.md` + role seeds
- New setting/env → `CONFIGURATION.md`
- Ops-facing restore/cron → `RUNBOOK.md` + Help backup-restore if user-facing
- Significant E2E runs → [test-results/](test-results/README.md)
