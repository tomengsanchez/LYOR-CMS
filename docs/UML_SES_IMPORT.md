# PAPeR – UML: PAPS Socio-Economic (SES) ZIP import

Feature-focused diagrams for **System → Socio Economic** import and the **Profile → Socio Economic** tab.

**Live diagrams:** [`/dev-help/#ses-uml`](../dev-help/) — sources in `dev-help/assets/ses-uml-diagrams.js`.  
**Related:** migration `084`, [ERD.md §6 SES import](ERD.md) (RAP is §7, separate), [GLOSSARY.md](GLOSSARY.md) (SES), Help `socio-economic`, [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md), E2E `tests/e2e/socio-economic-ses.spec.ts`.

---

## Critical rules (read first)

| Rule | Detail |
|------|--------|
| **Match key** | CSV **CONTROL ID** / Control Number / CONTROL_ID → `profiles.control_number` (exact), not soft-deleted |
| **PAPSID** | Display label in summaries only — **not** used for matching |
| **Main profile** | Import **never** creates profiles and **never** updates Main fields (name, contacts, location, …) |
| **Scope** | Only profiles in `UserProjects::allowedProjectIds()` for the importer |
| **ZIP** | Max **10 MB**; root-level `*.csv` only; nested folders → `NestedZip` |
| **Write path** | Web preview/import only (session + CSRF). Profile API is **read-only** for SES |

---

## 1. Context — where SES sits

```mermaid
flowchart LR
    subgraph Staff
        Sys[System Socio Economic UI]
        Tab[Profile Socio Economic tab]
    end

    subgraph ImportPath
        ZIP[SES ZIP of CSVs]
        Imp[SocioEconomicImporter]
    end

    subgraph Data
        P[(profiles)]
        Cur[(profile_socio_sections)]
        Ver[(profile_socio_versions)]
        Batch[(socio_import_batches)]
    end

    Sys -->|preview / import| ZIP
    ZIP --> Imp
    Imp -->|match control_number| P
    Imp -->|write| Cur
    Imp -->|snapshot| Ver
    Imp -->|audit row + store ZIP| Batch
    Tab -->|GET /api/profile/id/socio-economic| Cur
    Tab --> Ver
    P -.->|Main fields untouched| P
```

---

## 2. Sequence — preview then import

```mermaid
sequenceDiagram
    actor User
    participant JS as import.js
    participant Ctrl as SocioEconomicController
    participant Imp as SocioEconomicImporter
    participant DB as MySQL/MariaDB
    participant FS as uploads socio-economic

    User->>JS: Select ZIP and Preview
    JS->>Ctrl: POST system socio-economic preview
    Ctrl->>Ctrl: requireCapability import_socio_economic
    Ctrl->>Ctrl: uploadedZip max 10MB zip
    Ctrl->>Imp: preview tmpPath
    Imp->>Imp: parseZip root CSVs
    Imp->>DB: SELECT profiles by control_number
    Imp->>DB: SELECT profile_socio_sections for diff
    Imp-->>Ctrl: preview summary no writes
    Ctrl-->>JS: JSON preview

    User->>JS: Confirm Import
    JS->>Ctrl: POST system socio-economic import
    Ctrl->>Imp: import tmpPath filename userId
    Imp->>Imp: parseZip and resolveAgainstProfiles
    Imp->>DB: BEGIN
    Imp->>FS: storeZipCopy
    Imp->>DB: INSERT socio_import_batches
    Imp->>DB: INSERT socio_import_batch_projects
    loop each matched profile with changes
        Imp->>DB: applyProfileImport sections and version snapshot
    end
    Imp->>DB: COMMIT
    Imp->>DB: AuditLog socio_economic_import
    Imp-->>Ctrl: batch_id and summary
    Ctrl-->>JS: JSON success
```

---

## 3. Sequence — profile tab read

```mermaid
sequenceDiagram
    actor User
    participant Tab as socio-tab.js
    participant API as Api ProfileController
    participant Model as Models SocioEconomic
    participant DB as MySQL/MariaDB

    User->>Tab: Open Socio Economic tab
    Tab->>API: GET /api/profile/id/socio-economic
    API->>API: requireAuthApi + view_socio_economic
    API->>API: UserProjects scope check
    API->>Model: profileTabPayload id, versionId
    Model->>DB: versions + sections + diffs
    Model-->>API: versions, sections, effect_summary, highlights
    API-->>Tab: envelope data
    Note over Tab: EntryID order and previous_value on changed fields
```

---

## 4. Activity — validation, match, write

```mermaid
flowchart TD
    A[Upload ses_zip] --> B{size <= 10MB and .zip?}
    B -->|no| E1[FileTooLarge / InvalidFileType / NoFile]
    B -->|yes| C{ZipArchive open?}
    C -->|fail| E2[InvalidZip]
    C -->|ok| D{nested paths in archive?}
    D -->|yes| E3[NestedZip]
    D -->|no| F[Collect root CSV files]
    F --> G{any CSV?}
    G -->|no| E4[NoCsv]
    G -->|yes| H{each CSV has CONTROL column?}
    H -->|no| E5[MissingControlColumn]
    H -->|yes| I[Group rows by control id]
    I --> J[lookupProfilesByControlNumbers]
    J --> K{match control_number and project allowed?}
    K -->|no| L[unmatched skip - no profile create]
    K -->|yes| M[Map section_key = CSV basename]
    M --> N{preview?}
    N -->|yes| O[buildSummaryPayload only]
    N -->|no import| P{section rows changed vs current?}
    P -->|identical| Q[unchanged - skip write for section]
    P -->|new or changed| R[UPSERT profile_socio_sections]
    R --> S[If profile wrote: new version + section snapshots]
    S --> T[Batch summary + AuditLog]
```

---

## 5. Class sketch

```mermaid
classDiagram
    class SocioEconomicController {
        -MAX_ZIP_BYTES 10MB
        +index()
        +show(id)
        +download(id)
        +preview()
        +import()
        -uploadedZip()
    }
    class SocioEconomicImporter {
        +preview(zipPath)
        +import(zipPath, filename, uploadedBy)
        +sectionKeyFromFilename(filename)$
        -parseZip()
        -parseCsvStream()
        -resolveAgainstProfiles()
        -lookupProfilesByControlNumbers()
        -buildSummaryPayload()
        -applyProfileImport()
        -storeZipCopy()
    }
    class SocioEconomic {
        +currentSectionsMap(profileId)$
        +versionSectionsMap(versionId)$
        +versionsForProfile(profileId)$
        +profileTabPayload(profileId, versionId)$
        +diffSections(before, after)$
        +listBatches()
        +findBatch(id)$
    }
    class ProfileController {
        +getSocioEconomic(id)
        +saveSocioEconomicSection(id)
    }
    class Capabilities {
        +import_socio_economic
        +view_socio_economic_audit
        +view_socio_economic
    }
    class UploadPaths {
        +socioEconomic()
    }

    SocioEconomicController --> SocioEconomicImporter : preview and import
    SocioEconomicController --> SocioEconomic : batches
    SocioEconomicController ..> Capabilities
    SocioEconomicImporter --> SocioEconomic : current and diff
    SocioEconomicImporter --> UploadPaths : store ZIP
    ProfileController --> SocioEconomic : tab payload
    note for ProfileController "saveSocioEconomicSection is a no-op ZIP-import only"
```

---

## 6. Data model (as implemented)

Version sections are **JSON snapshots** keyed by `section_key` — there is **no** FK from `profile_socio_version_sections` to `profile_socio_sections.id`.

```mermaid
erDiagram
    users ||--o{ socio_import_batches : "uploaded_by"
    socio_import_batches ||--o{ socio_import_batch_projects : "batch_id"
    projects ||--o{ socio_import_batch_projects : "project_id"
    profiles ||--o{ profile_socio_sections : "profile_id"
    profiles ||--o{ profile_socio_versions : "profile_id"
    socio_import_batches ||--o{ profile_socio_versions : "batch_id"
    profile_socio_versions ||--o{ profile_socio_version_sections : "version_id"

    profiles {
        int id PK
        string control_number "MATCH KEY"
        string papsid "display only"
        int project_id
    }
    socio_import_batches {
        int id PK
        string original_filename
        string stored_path
        string status
        int file_count
        text csv_filenames
        longtext summary_json
        int profiles_affected
        int sections_created
        int sections_updated
    }
    profile_socio_sections {
        int id PK
        int profile_id FK
        string section_key UK
        string source_filename
        longtext rows_json
        int batch_id "last import index"
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
```

**Logical match (not a DB FK):**

```text
CSV CONTROL ID  ──equals──►  profiles.control_number
```

Batch / section columns above match migration **084** (additional skip/unmatched counters and `error_message` on batches are omitted from the diagram for clarity).
---

## 7. Capabilities

```mermaid
flowchart LR
    ImpCap[import_socio_economic] --> Preview[POST preview]
    ImpCap --> Import[POST import]
    AuditCap[view_socio_economic_audit] --> List[GET system list/detail/download]
    ImpCap --> List
    ViewCap[view_socio_economic] --> Tab[Profile tab + GET API]
```

Index/show/download: `view_socio_economic_audit` **or** `import_socio_economic`.  
Preview/import: **`import_socio_economic`** required.

---

## 8. Error codes (importer / controller)

| Code | Meaning |
|------|---------|
| `FileTooLarge` | Over 10 MB |
| `NoFile` | Missing upload |
| `InvalidFileType` | Not `.zip` / rejected magic |
| `InvalidZip` | Cannot open archive |
| `NestedZip` | Entries not at ZIP root |
| `NoCsv` | No root CSVs |
| `MissingControlColumn` | CSV lacks CONTROL column |
| `ImportFailed` | Transaction / unexpected failure |

Non-fatal: empty control cells skipped; unmatched controls listed; identical sections unchanged.

---

## Maintenance

When SES import behaviour changes, update this file **and** `dev-help/assets/ses-uml-diagrams.js`, plus Help / CHANGES as needed. Keep [ERD.md](ERD.md) SES inventory aligned (no false `section_id` FK).
