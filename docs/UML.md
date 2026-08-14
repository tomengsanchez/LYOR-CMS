# PAPeR – UML diagrams

Behavioral and structural views of the application (not the database). For tables and FKs see **[ERD.md](ERD.md)**. For *why* choices were made see **[adr/](adr/README.md)**. Living how-to: **[DEVELOPMENTGUIDE.md](DEVELOPMENTGUIDE.md)**.

**Live diagrams:** [`/dev-help/#uml`](../dev-help/) — keep `dev-help/assets/uml-diagrams.js` in sync with this file.  
**SES ZIP import (feature UML):** [UML_SES_IMPORT.md](UML_SES_IMPORT.md) · live [`/dev-help/#ses-uml`](../dev-help/).

Diagrams use Mermaid (`flowchart`, `classDiagram`, `sequenceDiagram`, `stateDiagram-v2`). They intentionally omit every controller/model; prefer durable architecture over a complete inventory. **RBAC** is covered in [§8](#8-rbac--roles-capabilities-project-scope).

---

## 1. Component / package view

```mermaid
flowchart TB
    subgraph Clients
        Browser[Staff browser]
        Mobile[Mobile / Postman]
    end

    subgraph WebRoot["public/"]
        FC[index.php front controller]
        Assets[assets/js external JS]
        Uploads[uploads]
    end

    subgraph CoreLayer["Core/"]
        Router[Router]
        CtrlBase[Controller]
        Auth[Auth]
        DB[Database PDO]
        Csrf[Csrf]
        Migrate[MigrationRunner]
    end

    subgraph AppLayer["App/"]
        Controllers[Controllers + Api]
        Models[Models]
        Views[Views]
        Caps[Capabilities]
        Services[Notification / Audit / Traffic / Pdf…]
    end

    subgraph Data["MySQL / MariaDB"]
        Schema[(Schema via migrations)]
    end

    subgraph Ops["cli/"]
        Backup[backup.php]
        Restore[restore.php]
        MigrateCli[migrate.php]
        MailQ[send_queued_emails.php]
    end

    Browser --> FC
    Mobile --> FC
    FC --> Router
    Router --> Controllers
    Controllers --> CtrlBase
    Controllers --> Models
    Controllers --> Views
    Controllers --> Caps
    Controllers --> Services
    CtrlBase --> Auth
    CtrlBase --> Csrf
    Models --> DB
    Auth --> DB
    DB --> Schema
    Browser --> Assets
    Controllers --> Uploads
    Backup --> Schema
    Backup --> Uploads
    Restore --> Schema
    Restore --> Uploads
    MigrateCli --> Migrate
    Migrate --> Schema
    MailQ --> Schema
```

---

## 2. Core class sketch

```mermaid
classDiagram
    class Router {
        +get(path, handler)
        +post(path, handler)
        +dispatch()
    }
    class Controller {
        #view(name, data)
        #redirect(url)
        #json(payload)
        #apiSuccess(data)
        #apiError(code, message)
        #requireAuth()
        #requireAuthApi() bool
        #requireCapability(name)
        #validateCsrf()
    }
    class Auth {
        +init()
        +check() bool
        +user()
        +can(capability) bool
        +login(userId)
        +logout()
    }
    class Database {
        +getInstance() PDO
    }
    class Csrf {
        +token() string
        +validate(token) bool
    }
    class ApiToken {
        +create(userId)
        +validate(token)
        +revoke(token)
    }
    class Capabilities {
        +definitions...
    }
    Controller <|-- WebControllers : App Controllers
    Controller <|-- ApiControllers : App Controllers Api
    Router --> Controller : invoke action
    Controller --> Auth
    Controller --> Csrf
    Auth --> ApiToken : Bearer when no session
    Auth --> Capabilities : can()
    Controller --> Database : via Models
```

---

## 3. Sequence — web page request

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant FC as public/index.php
    participant Router as Core Router
    participant Ctrl as App Controller
    participant Auth as Core Auth
    participant Model as App Model
    participant View as App Views

    User->>Browser: GET /profile/view/42
    Browser->>FC: HTTP request + session cookie
    FC->>Auth: init / idle check
    FC->>Router: dispatch
    Router->>Ctrl: ProfileController show(42)
    Ctrl->>Auth: requireAuth / requireCapability
    alt unauthorized
        Ctrl-->>Browser: redirect /login
    else allowed
        Ctrl->>Model: find(42)
        Model-->>Ctrl: row
        Ctrl->>View: view(profile/view)
        View-->>Browser: HTML + external JS
    end
```

---

## 4. Sequence — API Bearer auth

```mermaid
sequenceDiagram
    actor Client as Mobile / Postman
    participant API as /api/*
    participant Ctrl as Api Controller
    participant Auth as Core Auth
    participant Token as ApiToken
    participant Model as App Model

    Client->>API: POST /api/auth/login
    API->>Ctrl: AuthController login
    Ctrl->>Auth: verify credentials
    alt 2FA enabled
        Ctrl-->>Client: 403 TWO_FACTOR_REQUIRED
    else ok
        Ctrl->>Token: create(userId)
        Ctrl-->>Client: 200 envelope data.token
    end

    Client->>API: GET /api/profile/list Authorization Bearer
    API->>Ctrl: requireAuthApi
    Ctrl->>Auth: session?
    alt no session
        Ctrl->>Token: validate(Bearer)
    end
    alt invalid
        Ctrl-->>Client: 401 UNAUTHORIZED
    else ok
        Ctrl->>Ctrl: requireCapability
        Ctrl->>Model: listPaginated
        Ctrl-->>Client: 200 success data envelope
    end
```

---

## 5. Sequence — backup and CLI restore

```mermaid
sequenceDiagram
    actor Admin
    participant UI as System Backup UI
    participant Backup as cli/backup.php
    participant ZIP as backup ZIP
    participant Restore as cli/restore.php
    participant DB as MySQL/MariaDB
    participant Audit as completion audit

    Admin->>UI: Create / download backup
    UI->>Backup: run backup
    Backup->>DB: mysqldump (or PDO path)
    Backup->>ZIP: DB + uploads + manifest
    Backup-->>Admin: paper-backup-*.zip

    Admin->>Restore: php cli/restore.php --from=ZIP
    Restore->>ZIP: safety backup of current
    Restore->>DB: schema wipe (default)
    Restore->>Restore: sanitize SQL
    Restore->>DB: import
    Restore->>Audit: INSERT counts vs live COUNT
    alt audit fail
        Audit-->>Restore: fail
        Restore->>ZIP: auto-rollback from safety ZIP
    else ok
        Restore->>DB: migrate pending
        Restore-->>Admin: success
    end
```

See [ADR-0008](adr/0008-zip-backup-cli-restore.md).

---

## 6. State — grievance progress (simplified)

```mermaid
stateDiagram-v2
    [*] --> Open: create grievance
    Open --> InProgress: status / level update
    InProgress --> InProgress: note-only (timer does not reset)
    InProgress --> InProgress: real status or level change (segment restart)
    InProgress --> Escalated: days_to_address exceeded
    Escalated --> InProgress: staff advances level / status
    InProgress --> Closed: closed / resolved
    Escalated --> Closed: closed / resolved
    Closed --> [*]

    note right of InProgress
      Escalation uses start of current
      in-progress segment, not MAX(created_at).
      Project-scoped progress levels
      with global defaults (project_id NULL).
    end note
```

Manual QA: [QA_ESCALATION_REGRESSION.md](QA_ESCALATION_REGRESSION.md).

---

## 7. Activity — authorization check

```mermaid
flowchart TD
    A[Incoming request] --> B{Web or API?}
    B -->|Web| C[Session Auth]
    B -->|API| D{Session present?}
    D -->|yes| C
    D -->|no| E[Bearer ApiToken.validate]
    E -->|fail| F[401 JSON]
    E -->|ok| C
    C -->|not logged in web| G[Redirect /login]
    C -->|ok| H{Auth.can capability?}
    H -->|Admin bypass| I[Controller action]
    H -->|yes| I
    H -->|no| J[403 / forbidden]
    I --> K{Mutating web POST?}
    K -->|yes| L[validateCsrf]
    L -->|fail| M[403 CSRF]
    L -->|ok| N[Business logic / Model]
    K -->|no| N
    N --> O[View HTML or apiSuccess envelope]
```

---

## 8. RBAC — roles, capabilities, project scope

PAPeR uses **role → capability** grants (not role-only checks), with **Administrator bypass** and optional **project scoping** via `user_projects`. See [ADR-0007](adr/0007-role-capabilities.md) and [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md).

### 8.1 Class / structure

```mermaid
classDiagram
    class User {
        +int id
        +string username
        +int role_id
    }
    class Role {
        +int id
        +string name
    }
    class RoleCapability {
        +int id
        +int role_id
        +string capability
    }
    class Capabilities {
        <<registry>>
        +entities() map
        +all() map
        +forMenu(page) string
        +keys() list
    }
    class Auth {
        +check() bool
        +can(capability) bool
        +canAny(caps) bool
        +isAdmin() bool
        +capabilitiesForCurrentUser() list
    }
    class Controller {
        #requireAuth()
        #requireAuthApi() bool
        #requireCapability(name)
    }
    class UserProjects {
        +allowedProjectIds() listOrNull
    }
    class Menu {
        <<UI>>
        visibility via forMenu page_key
    }

    Role "1" --> "*" User : role_id
    Role "1" --> "*" RoleCapability : grants
    Capabilities ..> RoleCapability : capability keys defined in code
    User --> Auth : current session / Bearer user
    Auth --> Role : isAdmin hasRole
    Auth --> RoleCapability : load caps for role_id
    Auth --> Capabilities : admin returns all keys
    Controller --> Auth : requireCapability
    Menu --> Capabilities : forMenu
    Menu --> Auth : can view_* 
    User "*" --> "*" Project : user_projects
    UserProjects --> User : resolve allowed projects
    Controller --> UserProjects : filter list/write by project
```

### 8.2 Decision flow — `Auth::can` + project scope

```mermaid
flowchart TD
    A[Controller requireCapability name] --> B{Auth.check?}
    B -->|no| C[401 / redirect login]
    B -->|yes| D{Auth.isAdmin?}
    D -->|yes| E[Allow capability]
    D -->|no| F[Load role_capabilities for user.role_id]
    F --> G{capability in set?}
    G -->|no| H[403 Forbidden]
    G -->|yes| E
    E --> I{Data is project-scoped?}
    I -->|no| J[Run action]
    I -->|yes| K[UserProjects.allowedProjectIds]
    K --> L{null = all projects?}
    L -->|yes admin-style| J
    L -->|empty list| M[No project access]
    L -->|ID list| N{resource.project_id in list?}
    N -->|yes| J
    N -->|no| O[403 FORBIDDEN_PROJECT / hide row]
```

**Notes**

- Capability strings are defined in `App\Capabilities` and stored per role in `role_capabilities`.  
- Menus use the same keys (`Capabilities::forMenu`).  
- Web and API share `Auth::can`; API additionally uses Bearer when no session (ADR-0004).  
- Project scope is a **second gate** after capability (not a substitute for it).

---

## Maintenance

When routing, auth, restore, escalation, or **RBAC** semantics change:

1. Update the relevant Mermaid block here and in `dev-help/assets/uml-diagrams.js`.
2. Note it in the current month’s [changes](changes/) entry.
3. Prefer linking to ADRs / API contract / [CAPABILITY_MATRIX.md](CAPABILITY_MATRIX.md) over duplicating field catalogs into UML.
