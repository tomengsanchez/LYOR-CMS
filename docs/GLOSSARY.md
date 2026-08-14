# PAPeR – Glossary

Business and technical terms used in UI, Help, API, and docs.

| Term | Meaning |
|------|---------|
| **PAPeR** | Project Affected Profiles and Redress — this application. |
| **PAPS** | Project Affected Person/s — a profile tied to a project; also a grievance flag / respondent linkage pattern. |
| **PAPSID** | Unique profile identifier assigned on create (lock-coordinated). |
| **Control number** | Secondary/profile control identifier (uniqueness rules via migrations). |
| **STRID** | Unique structure identifier. |
| **Structure** | Physical or tagged asset linked to a profile/project; may be **primary** or **secondary**. |
| **Primary / secondary structure** | Classification; secondary may reference a primary structure. |
| **Structure tag** | Tagging label on a structure; profiles may multi-select existing tags via junction table. |
| **Project / Library** | Organizational container for profiles, structures, grievances, phases, options. |
| **Phase** | Project-scoped phase (`project_phases`); default Unassigned. |
| **Municipality / Barangay** | Location masters; codes unique **per linked project**, not globally. |
| **Affected barangays** | Project area definition (normalized rows and/or denormalized text). |
| **Grievance** | Complaint/redress case with case number, respondent, status history. |
| **Respondent** | Person raising/associated with a grievance; may be normalized row and/or inline/PAPS profile names. |
| **GRM** | Grievance Redress Mechanism — channels and related options. |
| **Progress level** | Stage in grievance workflow; project-scoped or global default (`project_id` NULL). |
| **Escalation** | Case overdue relative to `days_to_address` for the current in-progress segment. |
| **Status history / status log** | `grievance_status_log` entries (status, level, effective_at, attachments). |
| **Activity History** | Field-level audit (`audit_log`) shown on entity views. Testing prerequisites: [TESTING_HISTORY_PREREQUISITES.md](TESTING_HISTORY_PREREQUISITES.md). |
| **SES** | Socio-Economic Survey/import — ZIP import into versioned profile SES sections (match by **control_number**, not PAPSID). UML: [UML_SES_IMPORT.md](UML_SES_IMPORT.md). |
| **RAP** | Resettlement / related assessment mapping — field definitions + column maps from SES/structure/grievance sources. |
| **Capability** | Named permission string (e.g. `view_grievance`) checked via `Auth::can`. |
| **Role** | Named role (Administrator, Standard User, Coordinator, custom) with capability set. |
| **User projects** | Junction limiting which projects a non-admin user may access. |
| **Soft delete** | `is_deleted` / `deleted_at` / `deleted_by`; restore clears flags. |
| **API envelope** | `{ success, data, error }` wrapper on `/api/*` responses. |
| **Bearer token** | Hashed API credential in `api_tokens` for non-session clients. |
| **CSRF** | Cross-site request forgery token required on web POSTs. |
| **DevClock** | Optional simulated “now” for development/testing. |
| **Ask Help** | Floating help chat using local help text and optional OpenAI-compatible API. |
| **Live Traffic** | HTTP request logging, classification, geo cache, IP blocks. |
| **Remap Audit** | Admin guide/tooling to remap grievance options after project initialization. |
| **Completion audit** | Restore step comparing dump INSERT counts to live `COUNT(*)`. |

Update this file when introducing user-facing domain terms.
