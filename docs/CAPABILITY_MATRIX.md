# PAPeR – Capability matrix

Source of truth for capability **keys** is `App\Capabilities`. This document maps them to menus and typical API needs. **Administrator** bypasses capability checks.

**Related:** ADR-0007, `role_capabilities` table, Roles UI (`view_roles` / `edit_roles`). **UML:** [UML.md §8 RBAC](UML.md) and live `/dev-help/#uml` tabs **RBAC model** / **RBAC flow**.

---

## 1. Built-in roles (seeded)

Exact seeds may vary by install; typical:

| Role | Intent |
|------|--------|
| **Administrator** | Full access (bypass) + System tools |
| **Coordinator** | Broad operational access; often without destructive System ops |
| **Standard User** | Day-to-day profile/structure/grievance work within assigned projects |

Always verify live grants in **User Roles & Capabilities**. Project scope: `App\UserProjects::allowedProjectIds()` — `null` = all (admin), `[]` = none, else ID list.

---

## 2. Capabilities by module

| Module | Capability | Label (Roles UI) | Typical menu / surface |
|--------|------------|------------------|-------------------------|
| Profile | `view_profiles` | View List | Profile list/view |
| | `add_profiles` | Add | Create; also unlocks System → CSV Templates (with other add_*); Profile CSV import |
| | `edit_profiles` | Edit | Edit / API update; Profile CSV update rows |
| | `delete_profiles` | Delete | Soft delete |
| | `export_profiles` | Export | CSV/PDF |
| Socio Economic | `view_socio_economic` | View on profile | Profile SES tab |
| | `import_socio_economic` | Import ZIP | System → Socio Economic |
| | `view_socio_economic_audit` | View System audit log | SES system page |
| RAP mapping | `view_rap_mapping` | View mapping & project RAP summary | System → RAP / library RAP |
| | `manage_rap_mapping` | Manage RAP fields, ops & column maps | RAP admin |
| Structure | `view_structure` | View List | Structure list + API list |
| | `add_structure` | Add | Create; Structure CSV import; System CSV Templates (with other add_*) |
| | `edit_structure` | Edit | Edit; Structure CSV update rows |
| | `delete_structure` | Delete | Soft delete |
| | `export_structure` | Export | CSV/PDF |
| Structure options | `manage_structure_options` | Manage Options Library | Tagging Status + Actual Usage suggestion store |
| Grievance | `view_grievance` | View List | List, dashboard, view |
| | `add_grievance` | Add | Create; Grievance CSV import; System CSV Templates (with other add_*) |
| | `edit_grievance` | Edit | Edit / API update; Grievance CSV update rows |
| | `delete_grievance` | Delete | Soft delete |
| | `change_grievance_status` | Change Status | Status updates |
| | `export_grievance` | Export | CSV/PDF |
| Grievance options | `manage_grievance_options` | Manage Options Library | Options Library submenu |
| Remap Audit | `view_remap_audit` | View Remap Audit (System guide) | System → Remap Audit |
| | `run_remap_audit` | Run Remap from Remap Audit | Remap actions |
| Live Traffic | `view_live_traffic` | View Live Traffic | Live Traffic / Blocked IPs view |
| | `manage_live_traffic` | Block / unblock IPs | Block actions |
| Library – Project | `view_projects` | View List | Library |
| | `add_projects` | Add | Create project |
| | `edit_projects` | Edit | Edit project |
| | `delete_projects` | Delete | Soft delete |
| | `export_projects` | Export | Export |
| Settings | `view_settings` / `manage_settings` | View / Manage | UI settings |
| Email | `view_email_settings` / `manage_email_settings` | View / Manage | Email settings |
| Security | `view_security_settings` / `manage_security_settings` | View / Manage | Security + Realtime Security menu |
| API Clients | `view_api_clients` / `manage_api_clients` | View / Manage | System → API Clients (Dashboard, Clients, Security logs, Usage analytics) |
| Operational | `view_operational_settings` / `manage_operational_settings` | View / Manage | Operational / holidays |
| Users | `view_users` … `export_users` | CRUD + export | Users module |
| Contacts | `view_contacts` | View List (read-only directory) | Contacts directory |
| Roles | `view_roles` / `add_roles` / `edit_roles` | View / Add / Edit | User Roles |

Menu → view-capability map: `Capabilities::$menuCapability` (e.g. `grievance-dashboard` → `view_grievance`).

---

## 3. API alignment (summary)

| API area | Minimum capability (typical) |
|----------|------------------------------|
| Profile list/CRUD | `view_profiles` / `add_` / `edit_` / `delete_profiles` |
| Structure list/CRUD | `view_structure` (+ mutate caps) |
| Structure Options Library | `manage_structure_options` |
| Grievance list/CRUD/status | `view_grievance` (+ mutate / `change_grievance_status`) |
| Project pickers | Authenticated + project scope |
| System read APIs | Matching `view_*` / admin |
| Help chat | Signed-in user; rate-limited |

Exact per-route checks live in `App\Controllers\Api\*`. When adding a route, update this table and mobile docs.

---

## 4. Checklist for new capabilities

1. Add keys to `App\Capabilities::$entities` (and menu map if needed).  
2. Seed `role_capabilities` for intended roles (migration or seeder).  
3. Gate controller actions with `requireCapability`.  
4. Update this matrix + Help if user-visible.  
5. Note in monthly CHANGES.
