# Billex documentation

[Documentation policy](DOCUMENTATION.md) defines when to write, accept and maintain a document. [AGENTS.md](../AGENTS.md) is the shared source of project instructions, rule/workflow routing and review criteria for Codex and Claude; [.claude/CLAUDE.md](../.claude/CLAUDE.md) imports it.

## Requirements and technical designs

| Document | Status and purpose |
|---|---|
| [Businesses and contacts PRD](prd/PRD_BUSINESSES_AND_CONTACTS.md) | Approved product specification, version 1.2; scope and A01–A29 |
| [Businesses and contacts design](design/DESIGN_BUSINESSES_AND_CONTACTS.md) | Agreed step 7 technical design |
| [Businesses and contacts verification map](prd/VERIFICATION_BUSINESSES_AND_CONTACTS.md) | Implemented paths, test locations and source invariants; separate from requirements and individual test-run results |

## Architectural decisions

| ADR | Status |
|---|---|
| [D-042: Context service providers](adr/D-042-context-service-providers.md) | Accepted |
| [D-043: Modular monolith](adr/D-043-modular-monolith.md) | Accepted |
| [D-044: Document tables](adr/D-044-document-tables.md) | Proposed; the future storage design requires its own acceptance |
| [D-045: Businesses and contacts](adr/D-045-businesses-and-contacts.md) | Accepted |

An accepted specification or ADR is not a claim that every described path has been implemented. Use the verification map and code/tests for delivered behaviour, and [TECH_DEBT](TECH_DEBT.md) for known gaps and priorities. The code standard lives in [.claude/rules/](../.claude/rules/).

## Local working material

`dev_docs/` is ignored by Git. Keep reusable local handoffs and implementation journals in `dev_docs/ai/sessions/`; all agent-created temporary scripts, reports, logs, screenshots and browser profiles go in `dev_docs/ai/tmp/<task>/`. These files are optional local context and are not dependencies of the maintained documentation or GitHub review.
