# PAPeR – Software engineering documentation map

Single index of **engineering** docs. End-user Help remains in-app (`/help`, `/admin-guide`).

| Layer | Document | Purpose |
|-------|----------|---------|
| **Product** | [REQUIREMENTS.md](REQUIREMENTS.md) | Goals, scope, functional requirements |
| | [NFR.md](NFR.md) | Non-functional targets (perf, availability, a11y, DR) |
| | [GLOSSARY.md](GLOSSARY.md) | Domain terms (PAPSID, GRM, SES, RAP, …) |
| **Architecture** | [DEVELOPMENTGUIDE.md](DEVELOPMENTGUIDE.md) | Living how-to, schema, conventions |
| | [FrameworksGuide.txt](FrameworksGuide.txt) | Compact stack summary |
| | [adr/](adr/README.md) | Architectural Decision Records |
| | [ERD.md](ERD.md) | Data model (Mermaid) |
| | [UML.md](UML.md) | Structure & behaviour (Mermaid) |
| | [UML_SES_IMPORT.md](UML_SES_IMPORT.md) | SES ZIP import feature UML (PAPS socio-economic) |
| | [DEPLOYMENT.md](DEPLOYMENT.md) | Hosting topology, multi-server notes |
| **Access** | [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md) | Roles, capabilities, menus, API |
| **API / mobile** | [API_CONTRACT.md](API_CONTRACT.md), [API_AUTH.md](API_AUTH.md), [API_ERROR_CODES.md](API_ERROR_CODES.md) | REST contract |
| | [MOBILE_APP_INTEGRATION.md](MOBILE_APP_INTEGRATION.md), [MOBILE_2FA_GUIDE.md](MOBILE_2FA_GUIDE.md), [MOBILE_EXCHANGES.md](MOBILE_EXCHANGES.md) | Mobile onboarding |
| | [postman/](postman/README.md) | Postman collection |
| | [samples/](samples/README.md) | Sample CSV / import fixtures |
| **Ops** | [RUNBOOK.md](RUNBOOK.md) | Day-2 ops, cron, backup/restore drills, incidents |
| | [CONFIGURATION.md](CONFIGURATION.md) | Config files, `app_settings`, env vars |
| **Security** | [SECURITY.md](SECURITY.md) | Policy, threat model sketch, disclosure |
| | [SecurityAudit/](SecurityAudit/) | Point-in-time audit artifacts |
| **Quality** | [TEST_STRATEGY.md](TEST_STRATEGY.md) | Test pyramid, maps, gaps |
| | [TESTING_HISTORY_PREREQUISITES.md](TESTING_HISTORY_PREREQUISITES.md) | Prerequisites before testing Activity / Status / SES / notification history |
| | [test-results/](test-results/README.md) | Logged E2E / automated test run results (by month) |
| | [AUTOMATED_FUNCTIONAL_TEST_DESIGN.md](AUTOMATED_FUNCTIONAL_TEST_DESIGN.md) | Playwright design detail |
| | [QA_ESCALATION_REGRESSION.md](QA_ESCALATION_REGRESSION.md) | Escalation manual QA |
| **Delivery** | [RELEASE.md](RELEASE.md) | Release checklist & versioning |
| | [CHANGES.md](CHANGES.md) + [changes/](changes/) | What shipped |
| | [DevelopmentHistory/](DevelopmentHistory/) | Day-by-day notes |
| **Frontend** | [FRONTEND_JS_CONVENTIONS.md](FRONTEND_JS_CONVENTIONS.md) | External JS rules |
| **Process** | [../CONTRIBUTING.md](../CONTRIBUTING.md) | How to contribute |
| **Onboarding UI** | [../dev-help/](../dev-help/) | Live ERD/UML + architecture summary |

**Root:** [README.md](../README.md) (setup), [SECURITY.md](../SECURITY.md) (points here).

### Update rules

- Feature changes → monthly `changes/YYYY-MM/CHANGES.md` + `DevelopmentHistory/` as needed  
- Schema FKs → `ERD.md` + `dev-help/assets/erd-diagrams.js`  
- Request/auth/restore flows → `UML.md` + `uml-diagrams.js`  
- Durable “why” → new ADR  
- New capability → `Capabilities.php` + `CAPABILITY_MATRIX.md` + role seeds  
- New setting/env → `CONFIGURATION.md`  
- Ops-facing restore/cron → `RUNBOOK.md` + Help backup-restore if user-facing  
- Significant E2E / release-gate runs → [test-results/](test-results/README.md) (copy TEMPLATE into `YYYY-MM/`)  
