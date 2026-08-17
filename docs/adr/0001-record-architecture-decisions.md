# ADR-0001: Record architecture decisions

- Status: Accepted
- Date: 2026-08-09

## Context

The repo already documents **what** changed (`docs/changes/`, `DevelopmentHistory/`) and **how** the system works (`DEVELOPMENTGUIDE.md`, API docs). Binding “why we chose X” knowledge was scattered or only in chat history, which makes onboarding and revisiting trade-offs harder.

## Decision

Adopt Architectural Decision Records under `docs/adr/`:

- Numbered Markdown files (`NNNN-title.md`)
- Index and process in `docs/adr/README.md`
- Status lifecycle: Proposed → Accepted → Deprecated / Superseded
- New durable decisions get an ADR; day-to-day feature work stays in CHANGES / DevelopmentHistory

## Consequences

- Positive: Stable rationale next to code; easier reviews when a choice affects multiple modules.
- Negative: Small process overhead; ADRs must stay short or they rot.
- Follow-on: Link this folder from `DEVELOPMENTGUIDE.md`. Do not duplicate API field catalogs or monthly change logs into ADRs.

## Alternatives considered

- **Wiki / external Confluence only** — drifts from the repo and is harder for agents/CI clones.
- **Only expand DEVELOPMENTGUIDE** — mixes living how-to with irreversible decisions; guide stays large.
- **Only DevelopmentHistory JSON** — good for chronology, weak for “still binding?” status and supersession.
