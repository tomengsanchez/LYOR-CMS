# ADR-0007: Role capabilities for authorization

- Status: Accepted
- Date: 2026-08-09
- Notes: Retroactive formalization of `App\Capabilities` + `role_capabilities`

## Context

PAPeR has multiple roles (e.g. Administrator, Standard User, Coordinator) and many modules (profiles, structures, grievances, system tools, live traffic). Coarse role checks alone do not express “can view list but not delete” or menu visibility per feature.

**Diagrams:** [UML.md §8](../UML.md) (RBAC class + `Auth::can` / project-scope flow); live `/dev-help/#uml`.

## Decision

Authorize with **named capabilities**:

- Registry: `App\Capabilities`
- Persistence: `role_capabilities` (role_id + capability)
- Checks: `Auth::can()` / `$this->requireCapability('…')` in controllers; menu visibility uses the same names
- Administrator bypasses capability checks where the product defines admin override

Project scoping (e.g. `user_projects`) layers on top of capabilities for multi-project data.

## Consequences

- Positive: Fine-grained, consistent web + API gates; menus and routes stay aligned.
- Negative: New features need new capability names + seeds; forgetting a check is a security bug.
- Follow-on: Document new capabilities in help/admin guidance when user-facing; include `role_capabilities` in backup/restore (standard schema). MySQL/MariaDB: simple relational table, portable.

## Alternatives considered

- **Role-only checks** (`if admin`) — too coarse for field vs coordinator vs admin tools.
- **Per-row ACLs** — flexible but heavy for current project-scoped model.
- **Policy classes per model (Laravel-style)** — workable later; current central registry matches the custom MVC.
