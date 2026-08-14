# PAPeR – Entity Relationship Diagram

Logical ERD of the MySQL / MariaDB schema as of migrations **000–091**. Column lists are **key fields only** (not every VARCHAR). Living field detail: [DEVELOPMENTGUIDE.md](DEVELOPMENTGUIDE.md) §4. Binding constraints: [ADR-0003](adr/0003-mysql-mariadb-compatibility.md), [ADR-0008](adr/0008-zip-backup-cli-restore.md). Structure and behaviour (not tables): **[UML.md](UML.md)** / [`/dev-help/#uml`](../dev-help/).

**How to read**

- Solid relationships are real FKs or well-established app joins.
- Dashed / notes mark **logical** links (JSON ID arrays, polymorphic `entity_type` + `entity_id`, or soft references).
- Many core entities carry soft-delete columns (`is_deleted`, `deleted_at`, `deleted_by`) from migration **038** — omitted from diagrams for clarity.
- Full DB dumps (all tables below) are included in ZIP backup/restore.

Render these diagrams in any Mermaid-capable viewer (GitHub, VS Code/Cursor Markdown preview, etc.).

**Live diagrams in the developer guide:** open [`/dev-help/#erd`](../dev-help/) (interactive Mermaid canvas; sources in `dev-help/assets/erd-diagrams.js` — keep that file in sync with this document).

---

## 1. Overview (core domain)

```mermaid
erDiagram
    roles ||--o{ users : "role_id"
    roles ||--o{ role_capabilities : "role_id"
    users ||--o{ user_projects : "user_id"
    projects ||--o{ user_projects : "project_id"
    users ||--o| projects : "coordinator_id"
    projects ||--o{ profiles : "project_id"
    projects ||--o{ structures : "project_id"
    projects ||--o{ grievances : "project_id"
    projects ||--o{ project_phases : "project_id"
    project_phases ||--o{ profiles : "phase_id"
    project_phases ||--o{ structures : "phase_id"
    project_phases ||--o{ grievances : "phase_id"
    profiles ||--o{ structures : "owner_id"
    profiles ||--o{ grievances : "profile_id"
    profiles ||--o{ grievance_respondents : "profile_id"
    grievance_respondents ||--o{ grievances : "respondent_id"
    municipalities ||--o{ barangays : "municipality_id"
    projects ||--o{ municipality_projects : "project_id"
    municipalities ||--o{ municipality_projects : "municipality_id"
```

---

## 2. Auth, roles, sessions, API

```mermaid
erDiagram
    roles {
        int id PK
        string name
    }
    role_capabilities {
        int id PK
        int role_id FK
        string capability
    }
    users {
        int id PK
        string username
        string email
        string password_hash
        datetime password_changed_at
        int role_id FK
        string display_name
    }
    user_password_history {
        int id PK
        int user_id FK
        string password_hash
        datetime changed_at
    }
    user_sessions {
        int id PK
        int user_id FK
        string session_id
        string ip_address
        datetime last_activity_at
        datetime revoked_at
        string page_key
    }
    api_tokens {
        int id PK
        int user_id FK
        string token_hash
        datetime expires_at
        datetime last_used_at
    }
    api_2fa_challenges {
        int id PK
        int user_id FK
        string challenge_token_hash
        datetime expires_at
    }
    api_idempotency_keys {
        int id PK
        int user_id FK
        string idempotency_key
        string request_hash
    }
    user_dashboard_config {
        int id PK
        int user_id FK
        string module
        json config
    }
    user_list_columns {
        int id PK
        int user_id FK
        string list_key
        json columns
    }
    user_profiles {
        int id PK
        int user_id FK
        int role_id FK
        string name
    }

    roles ||--o{ users : "role_id"
    roles ||--o{ role_capabilities : "role_id"
    roles ||--o{ user_profiles : "role_id"
    users ||--o{ user_password_history : "user_id"
    users ||--o{ user_sessions : "user_id"
    users ||--o{ api_tokens : "user_id"
    users ||--o{ api_2fa_challenges : "user_id"
    users ||--o{ api_idempotency_keys : "user_id"
    users ||--o{ user_dashboard_config : "user_id"
    users ||--o{ user_list_columns : "user_id"
    users ||--o| user_profiles : "user_id"
```

---

## 3. Projects, location, phases

```mermaid
erDiagram
    projects {
        int id PK
        string name
        text description
        text affected_barangays
        int coordinator_id FK
    }
    project_phases {
        int id PK
        int project_id FK
        string name
        text description
    }
    municipalities {
        int id PK
        string name
        string code
        text description
    }
    barangays {
        int id PK
        int municipality_id FK
        string name
        string code
        text description
    }
    municipality_projects {
        int municipality_id FK
        int project_id FK
    }
    project_affected_barangays {
        int project_id FK
        int municipality_id FK
        int barangay_id FK
    }
    holidays {
        int id PK
        date holiday_date
        string name
    }

    users ||--o| projects : "coordinator_id"
    projects ||--o{ project_phases : "project_id"
    projects ||--o{ municipality_projects : "project_id"
    municipalities ||--o{ municipality_projects : "municipality_id"
    municipalities ||--o{ barangays : "municipality_id"
    projects ||--o{ project_affected_barangays : "project_id"
    municipalities ||--o{ project_affected_barangays : "municipality_id"
    barangays ||--o{ project_affected_barangays : "barangay_id"
    users ||--o{ user_projects : "user_id"
    projects ||--o{ user_projects : "project_id"
    user_projects {
        int user_id FK
        int project_id FK
    }
```

**Notes:** Municipality/barangay **codes** are unique per linked project (via `municipality_projects`), not globally. `holidays` are org-wide operational calendar rows (no project FK).

---

## 4. Profiles, structures, contacts, attachments

```mermaid
erDiagram
    profiles {
        int id PK
        string papsid UK
        string control_number
        string first_name
        string middle_name
        string last_name
        int project_id FK
        int phase_id FK
        int custom_municipality_id FK
        int custom_barangay_id FK
        int field_personnel_id FK
        json contacts_json
        string entity_type
    }
    structures {
        int id PK
        string strid UK
        int owner_id FK
        int project_id FK
        int phase_id FK
        int tagged_by_profile_id FK
        int associated_primary_structure_id FK
        string structure_classification
        string tagging_status
        date date_first_visit
        string first_visit_witness_1_name
        date first_visit_witness_1_date
        int municipality_id FK
        int barangay_id FK
    }
    profile_structure_tags {
        int profile_id FK
        int structure_id FK
    }
    profile_attachments {
        int id PK
        int profile_id FK
        string title
        string file_path
        int sort_order
    }
    contacts {
        int id PK
        string entity_type
        int entity_id
        string person_label
        string number
        tinyint is_primary
    }

    projects ||--o{ profiles : "project_id"
    project_phases ||--o{ profiles : "phase_id"
    municipalities ||--o{ profiles : "custom_municipality_id"
    barangays ||--o{ profiles : "custom_barangay_id"
    users ||--o{ profiles : "field_personnel_id"
    profiles ||--o{ structures : "owner_id"
    profiles ||--o{ structures : "tagged_by_profile_id"
    structures ||--o{ structures : "associated_primary"
    projects ||--o{ structures : "project_id"
    project_phases ||--o{ structures : "phase_id"
    municipalities ||--o{ structures : "municipality_id"
    barangays ||--o{ structures : "barangay_id"
    profiles ||--o{ profile_structure_tags : "profile_id"
    structures ||--o{ profile_structure_tags : "structure_id"
    profiles ||--o{ profile_attachments : "profile_id"
```

**Contacts:** Polymorphic — `entity_type` is `profile` or `user`, `entity_id` points at `profiles.id` or `users.id` (no single FK). `profiles.contacts_json` / `contact_number` stay denormalized for search.

---

## 5. Grievances and options library

```mermaid
erDiagram
    grievances {
        int id PK
        string grievance_case_number
        int project_id FK
        int phase_id FK
        int profile_id FK
        int respondent_id FK
        int municipality_id FK
        int barangay_id FK
        int attendant_id FK
        string attendant_text
        json vulnerability_ids
        json respondent_type_ids
        json grm_channel_ids
        json preferred_language_ids
        json grievance_type_ids
        json grievance_category_ids
        datetime closed_at
    }
    grievance_respondents {
        int id PK
        string first_name
        string last_name
        int profile_id FK
        tinyint is_paps
    }
    grievance_status_log {
        int id PK
        int grievance_id FK
        int progress_level_id FK
        string status
        datetime effective_at
        datetime created_at
    }
    grievance_attachments {
        int id PK
        int grievance_id FK
        int status_log_id FK
        string file_path
        string title
    }
    grievance_progress_levels {
        int id PK
        int project_id FK
        string name
        int days_to_address
        int sort_order
    }
    grievance_grm_channels {
        int id PK
        int project_id FK
        string name
    }
    grievance_preferred_languages {
        int id PK
        int project_id FK
        string name
    }
    grievance_vulnerabilities {
        int id PK
        string name
    }
    grievance_respondent_types {
        int id PK
        string name
    }
    grievance_types {
        int id PK
        string name
    }
    grievance_categories {
        int id PK
        string name
    }

    projects ||--o{ grievances : "project_id"
    project_phases ||--o{ grievances : "phase_id"
    profiles ||--o{ grievances : "profile_id"
    grievance_respondents ||--o{ grievances : "respondent_id"
    profiles ||--o{ grievance_respondents : "profile_id"
    municipalities ||--o{ grievances : "municipality_id"
    barangays ||--o{ grievances : "barangay_id"
    users ||--o{ grievances : "attendant_id"
    grievances ||--o{ grievance_status_log : "grievance_id"
    grievance_progress_levels ||--o{ grievance_status_log : "progress_level_id"
    grievances ||--o{ grievance_attachments : "grievance_id"
    grievance_status_log ||--o{ grievance_attachments : "status_log_id"
    projects ||--o{ grievance_progress_levels : "project_id"
    projects ||--o{ grievance_grm_channels : "project_id"
    projects ||--o{ grievance_preferred_languages : "project_id"
```

**JSON multi-selects:** `vulnerability_ids`, `respondent_type_ids`, `grm_channel_ids`, `preferred_language_ids`, `grievance_type_ids`, `grievance_category_ids` store arrays of lookup IDs (logical M:N, no junction tables).  
**Project scope:** `grievance_progress_levels`, `grievance_grm_channels`, and `grievance_preferred_languages` use nullable `project_id` (`NULL` = global defaults).  
**Attendant:** either `attendant_id` → `users` or free-text `attendant_text` (mutually exclusive at app level).

---

## 6. Socio-economic (SES) import

ZIP import of SES CSVs into per-profile current sections and version history. **Separate from RAP** (next section). Feature UML: [UML_SES_IMPORT.md](UML_SES_IMPORT.md).

```mermaid
erDiagram
    socio_import_batches {
        int id PK
        int uploaded_by FK
        string original_filename
        string stored_path
        string status
        int file_count
        text csv_filenames
        longtext summary_json
        int profiles_affected
        int sections_created
        int sections_updated
        datetime created_at
    }
    socio_import_batch_projects {
        int batch_id FK
        int project_id FK
        string project_name
        int profiles_affected
        int sections_created
        int sections_updated
    }
    profile_socio_sections {
        int id PK
        int profile_id FK
        string section_key
        string source_filename
        longtext rows_json
        int batch_id
    }
    profile_socio_versions {
        int id PK
        int profile_id FK
        int batch_id FK
        int version_no
        longtext effect_summary_json
    }
    profile_socio_version_sections {
        int id PK
        int version_id FK
        string section_key
        string source_filename
        longtext rows_json
    }
    profiles {
        int id PK
        string control_number
        string papsid
        int project_id FK
    }

    users ||--o{ socio_import_batches : "uploaded_by"
    socio_import_batches ||--o{ socio_import_batch_projects : "batch_id"
    projects ||--o{ socio_import_batch_projects : "project_id"
    profiles ||--o{ profile_socio_sections : "profile_id"
    profiles ||--o{ profile_socio_versions : "profile_id"
    socio_import_batches ||--o{ profile_socio_versions : "batch_id"
    profile_socio_versions ||--o{ profile_socio_version_sections : "version_id"
```

**Notes**

- Import matches CSV CONTROL ID → `profiles.control_number` (not PAPSID; PAPSID is display-only).
- `profile_socio_version_sections` are JSON snapshots by `section_key` (no FK to `profile_socio_sections.id`).
- `source_filename` on current/version sections identifies the CSV inside the ZIP.
- Batch rows also track skip/unmatched counters and optional `error_message` (see migration **084**).
- Main `profiles` columns are never updated by SES import.

---

## 7. RAP mapping

RAP field catalog and multi-source column maps (migrations **089**–**090**). Reads SES / structure / grievance data for project summaries — **does not own** SES import tables.

```mermaid
erDiagram
    rap_field_definitions {
        int id PK
        string field_key UK
        string label
        string category
        string value_mode
        text mode_params_json
        int sort_order
        tinyint is_active
    }
    ses_rap_column_maps {
        int id PK
        int rap_field_id FK
        string source_entity
        string section_key
        string ses_column
        int sort_order
    }

    rap_field_definitions ||--o{ ses_rap_column_maps : "rap_field_id"
```

**Notes**

- Table name `ses_rap_column_maps` is historical; `source_entity` is `ses` | `structure` | `grievance` (logical map into those domains — not a physical FK to SES/structure/grievance rows).
- `value_mode` / `mode_params_json` drive ops such as first, list, sum, average, count, range variants.
- Capabilities: `view_rap_mapping` / `manage_rap_mapping`.

---

## 8. Notifications, audit, backup, traffic, settings

```mermaid
erDiagram
    notifications {
        int id PK
        int user_id FK
        string type
        string related_type
        int related_id
        int project_id FK
        text message
        datetime clicked_at
    }
    email_queue {
        int id PK
        string to_email
        string subject
        text body
        string status
        datetime sent_at
    }
    audit_log {
        int id PK
        string entity_type
        int entity_id
        string action
        json changes
        int created_by FK
        datetime created_at
    }
    backup_archives {
        int id PK
        string filename
        string path
        int created_by FK
        datetime created_at
    }
    traffic_events {
        int id PK
        string ip_address
        string method
        string path
        int user_id FK
        string classification
        datetime created_at
    }
    traffic_ip_blocks {
        int id PK
        string ip_address
        string reason
        datetime created_at
    }
    traffic_geo_cache {
        string ip_address PK
        string hostname
        string country
        json geo_json
    }
    app_settings {
        string setting_key PK
        text setting_value
    }
    migrations {
        int id PK
        string name
        datetime ran_at
    }

    users ||--o{ notifications : "user_id"
    projects ||--o{ notifications : "project_id"
    users ||--o{ audit_log : "created_by"
    users ||--o{ backup_archives : "created_by"
    users ||--o{ traffic_events : "user_id"
```

**Polymorphic history:** `audit_log.entity_type` + `entity_id` (and notification `related_*`) point at profiles, structures, grievances, etc., without enforced FKs.

---

## 9. Table inventory (by area)

| Area | Tables |
|------|--------|
| Auth / access | `roles`, `role_capabilities`, `users`, `user_password_history`, `user_sessions`, `user_profiles`, `user_projects`, `user_dashboard_config`, `user_list_columns` |
| API | `api_tokens`, `api_2fa_challenges`, `api_idempotency_keys` |
| Projects / geo | `projects`, `project_phases`, `municipalities`, `barangays`, `municipality_projects`, `project_affected_barangays`, `holidays` |
| Profiles / structures | `profiles`, `structures`, `profile_structure_tags`, `profile_attachments`, `contacts`, structure lookups (`structure_tagging_statuses`, `structure_actual_usages`) |
| Grievance | `grievances`, `grievance_respondents`, `grievance_status_log`, `grievance_attachments`, lookups (`grievance_vulnerabilities`, `grievance_respondent_types`, `grievance_grm_channels`, `grievance_preferred_languages`, `grievance_types`, `grievance_categories`, `grievance_progress_levels`) |
| SES import | `socio_import_batches`, `socio_import_batch_projects`, `profile_socio_sections`, `profile_socio_versions`, `profile_socio_version_sections` |
| RAP mapping | `rap_field_definitions`, `ses_rap_column_maps` |
| Platform | `notifications`, `email_queue`, `audit_log`, `backup_archives`, `traffic_events`, `traffic_ip_blocks`, `traffic_geo_cache`, `app_settings`, `migrations` |

---

## Maintenance

When a migration adds or drops tables/FKs:

1. Update the relevant Mermaid section and the inventory table.
2. Note the change in the current month’s [CHANGES](changes/) entry and optionally [DevelopmentHistory](DevelopmentHistory/).
3. Confirm backup/restore still covers new tables (standard mysqldump path) and MySQL/MariaDB portability.
