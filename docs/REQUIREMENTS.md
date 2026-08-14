# PAPeR – Product / functional requirements

Living requirements baseline for **PAPeR** (Project Affected Profiles and Redress). Not a formal IEEE SRS; use this for scope, acceptance, and traceability into Help/API/tests.

**Related:** [NFR.md](NFR.md), [GLOSSARY.md](GLOSSARY.md), [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md), in-app `/help`.

---

## 1. Purpose

Provide a staff-facing system (and additive REST API for mobile/integrators) to:

1. Register and manage **project-affected persons/profiles** (PAPS) and related **structures**.
2. Record and track **grievances** through project-scoped progress levels with escalation timing.
3. Support **project (library)** setup, location masters, phases, SES imports, and RAP mapping.
4. Enforce **role/capability** access, audit history, notifications, and operational tooling (backup, traffic, security settings).

---

## 2. Stakeholders & personas

| Persona | Needs |
|---------|--------|
| **Administrator** | Full config, users/roles, backup, security, traffic, remap, system tools |
| **Coordinator** | Project-scoped casework oversight; typically broad module access without full System tools |
| **Standard / field user** | Create/edit profiles, structures, grievances within assigned projects |
| **Mobile integrator** | REST auth, lists, CRUD, multipart attachments (see mobile docs) |
| **Ops / host admin** | Deploy, cron, backup/restore, DB credentials (CLI + server) |

---

## 3. In scope

- Web MVC UI (Bootstrap/jQuery) for profiles, structures, grievances, library, users, settings, system tools  
- REST API under `/api/*` with JSON envelope and Bearer or session auth  
- Project scoping via `user_projects` (+ admin all-projects)  
- Soft delete + restore for core entities  
- Escalation based on progress-level `days_to_address` and business-day logic  
- Notifications (in-app + optional email queue)  
- ZIP backup (UI/CLI) and **CLI-only** restore with completion audit  
- SES ZIP import and RAP field/column mapping  
- Live traffic logging and IP blocks  
- Playwright E2E and CLI unit-style tests  

## 4. Out of scope (current product)

- Public citizen self-service portal  
- Full OAuth2/OIDC identity provider  
- Multi-tenant SaaS isolation beyond project scoping  
- Offline-first native sync engine (API is online REST)  
- In-browser database restore  

---

## 5. Functional requirements (summary)

IDs are stable for traceability (`FR-xxx`). Detail lives in Help and `API_CONTRACT.md`.

### 5.1 Identity & access — FR-AUTH

| ID | Requirement |
|----|-------------|
| FR-AUTH-01 | Users authenticate via username/password (web session). |
| FR-AUTH-02 | Optional email 2FA when enabled in Security Settings. |
| FR-AUTH-03 | API clients obtain Bearer tokens via `POST /api/auth/login` when 2FA policy allows. |
| FR-AUTH-04 | Authorization uses named capabilities; Administrator bypasses checks. |
| FR-AUTH-05 | Idle session timeout and API token absolute/idle expiry are configurable. |
| FR-AUTH-06 | Users can view/revoke own sessions; admins can revoke via Realtime Dashboard. |

### 5.2 Projects & location — FR-PROJ

| ID | Requirement |
|----|-------------|
| FR-PROJ-01 | Maintain projects (library) with optional coordinator and affected areas. |
| FR-PROJ-02 | Municipalities/barangays support codes unique per linked project. |
| FR-PROJ-03 | Project phases; profiles/structures/grievances may reference `phase_id`. |
| FR-PROJ-04 | Profile/structure location must validate against project area rules. |

### 5.3 Profiles — FR-PROF

| ID | Requirement |
|----|-------------|
| FR-PROF-01 | Create/list/view/edit/soft-delete/restore profiles with PAPSID / control number. |
| FR-PROF-02 | Support name parts, contacts, invitation/visits, entity type, structure tags. |
| FR-PROF-03 | Attachment cards (web + API multipart). |
| FR-PROF-04 | SES data viewable on profile when imported (does not overwrite Main fields). |
| FR-PROF-05 | Export CSV/PDF within capability and list limits. |

### 5.4 Structures — FR-STR

| ID | Requirement |
|----|-------------|
| FR-STR-01 | Create/list/view/edit/soft-delete/restore structures with STRID. |
| FR-STR-02 | Primary/secondary classification with optional link to primary. |
| FR-STR-03 | Tagging status history; GPS text + decimal fields. |
| FR-STR-04 | API list for mobile Structures tab (`GET /api/structure/list`). |
| FR-STR-05 | Tagging Status Options Library; Actual usage free-text with suggestion store (`GET /api/structure/options`). |

### 5.5 Grievances — FR-GRV

| ID | Requirement |
|----|-------------|
| FR-GRV-01 | Create/list/view/edit/soft-delete/restore grievances with case numbers. |
| FR-GRV-02 | Link optional profile and/or normalized respondent; PAPS rules as documented. |
| FR-GRV-03 | Status history with progress levels; attachments on registration and status. |
| FR-GRV-04 | Escalation timing does not reset on note-only updates. |
| FR-GRV-05 | Project-scoped options (levels, GRM, languages) with global defaults. |
| FR-GRV-06 | CSV import with published sample template. |
| FR-GRV-07 | API field-level activity history aligned with web edits. |

### 5.6 System & ops — FR-SYS

| ID | Requirement |
|----|-------------|
| FR-SYS-01 | Admin General / Operational / Security / Email settings. |
| FR-SYS-02 | Audit trail / activity history for key entities. |
| FR-SYS-03 | Backup ZIP create/download in UI; restore CLI-only with audit/rollback. |
| FR-SYS-04 | Live traffic + IP blocklist. |
| FR-SYS-05 | Contextual Help and optional Ask Help chat. |

### 5.7 Integrations — FR-INT

| ID | Requirement |
|----|-------------|
| FR-INT-01 | SMTP or MailerSend email delivery via queue worker. |
| FR-INT-02 | REST contract documented for third parties; Postman collection maintained. |

---

## 6. Acceptance criteria (definition of done for features)

A feature is done when:

1. Web behaviour works with correct capabilities and project scope.  
2. API (if exposed) matches `API_CONTRACT` / Postman; envelope preserved.  
3. MySQL **and** MariaDB compatibility considered.  
4. Backup/restore impact checked (new tables/files included).  
5. Help (and Admin Guide if admin-facing) updated when users need guidance.  
6. CHANGES + DevelopmentHistory (and ADR/ERD/UML when architecture changes) updated.  
7. Tests added or explicitly deferred in `TEST_STRATEGY.md`.  

---

## 7. Traceability (lightweight)

| Requirement area | Primary docs / code |
|------------------|---------------------|
| Auth | ADR-0004, API_AUTH, Security Settings |
| Data model | ERD, DEVELOPMENTGUIDE §4 |
| Behaviour | UML, Controllers/Models |
| Caps | CAPABILITY_MATRIX, `App\Capabilities` |
| Escalation | QA_ESCALATION_REGRESSION, grievance status log |
| Backup | RUNBOOK, ADR-0008, Help backup-restore |
| Mobile | MOBILE_* docs |
