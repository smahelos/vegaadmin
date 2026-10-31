# Documentation policy

Status: Accepted 2026-10-30. Applies to human contributors, Codex, Claude and other agents.

## Locations and responsibilities

| Location | Purpose | Versioned |
|---|---|---|
| `docs/README.md` | Index of maintained documentation and document status | Yes |
| `docs/adr/D-*.md` | Significant decisions, alternatives, rationale and consequences | Yes |
| `docs/prd/PRD_*.md` | Approved product behaviour, scope and acceptance criteria | Yes |
| `docs/design/DESIGN_*.md` | Technical designs needed to understand nontrivial mechanisms | Yes |
| `docs/prd/VERIFICATION_*.md` | Acceptance-to-test maps where useful for a larger feature | Yes |
| `docs/TECH_DEBT.md` | Known implementation gaps and priorities | Yes |
| `AGENTS.md` | Canonical shared agent instructions, rule catalog, workflow routing and review rules | Yes |
| `.claude/CLAUDE.md` | Claude entry point importing `AGENTS.md`, without separate project instructions | Yes |
| `.claude/rules/` | Shared target code standard | Yes |
| `dev_docs/` | Working proposals, investigation notes and other local documents | No |
| `dev_docs/ai/sessions/` | Durable local handoffs and implementation journals | No |
| `dev_docs/ai/tmp/<task>/` | Agent-created temporary scripts, logs, reports, screenshots, browser profiles and scratch files | No |

Paths in this table are relative to the repository root. Keep existing ADR identifiers; a directory move does not create a new decision. Add maintained documents to the index. Local documents need a separate backup if they must survive loss of the checkout; Git does not back up `dev_docs/`.

## One shared set of AI instructions

`AGENTS.md` is the canonical entry for project guidance. Codex reads it directly; `.claude/CLAUDE.md` imports it using `@../AGENTS.md`. Keep the same project standard for both agents, without tool-specific copies of rules or workflow bodies. The `.claude/` directory name does not limit those files to Claude.

Both agents read the entire `.claude/rules/` catalog once per session before editing, then apply the rules relevant to the change. Keep the catalog in `AGENTS.md` current when adding or removing a rule. Read referenced examples and workflow `SKILL.md` files when their task applies. The shared workflow table makes the same procedures available even when an agent does not list repository skills in its native picker.

Shared review criteria live in `AGENTS.md` under Code Review Rules. GitHub review prompts reference those criteria and add only event context and output instructions. Personal tool settings and higher-priority user instructions may still differ; they do not create a second version of Billex's project standard.

## When to write a document

- Read the relevant accepted requirements, ADRs and code rules before implementing or reviewing a change.
- Create an ADR for a significant long-term decision about data ownership, context boundaries, transactions, persistence, public contracts or business invariants. Record the context, decision, alternatives and consequences; a short ADR is sufficient.
- Create a PRD for a larger feature with several interacting rules or an uncertain scope. State its goal, required behaviour, explicit exclusions and observable acceptance criteria.
- Create a DESIGN only when the technical relationships or tradeoffs need an explanation beyond the PRD and ADR. Avoid inventories of classes and methods that merely repeat the code.
- A small bug fix, refactoring within an accepted design or UI adjustment normally needs its change description and verification, without a new ADR or PRD. Update existing documentation when its stated behaviour or decision is affected.

## Status, authority and maintenance

- Give a document a date and explicit status, and link its related requirements, decisions or design. ADR states are Proposed, Accepted, Rejected and Superseded; an accepted replacement names the superseded ADR. Preserve the original rationale, and distinguish editorial corrections from a new decision.
- The project owner or designated reviewer accepts significant decisions and product scope. An agent may prepare a proposal; drafting a document does not make it accepted. Accepted requirements do not by themselves prove implementation.
- The PRD defines required product behaviour, ADRs record accepted architectural decisions, and `.claude/rules/` define the target code standard. Code and tests establish actual implementation. Clarify a material conflict between these before implementing a conflicting decision; never silently describe missing behaviour as delivered.
- Update affected maintained documents in the same PR as the implementation. Keep the scope of the update proportional to the change. A reconstructed decision states that it was recorded retrospectively; do not invent historical approvals.
- Record known gaps in `docs/TECH_DEBT.md`. A verification map links acceptance criteria to relevant tests and may explain source invariants; passing an old test run is not proof of a later change.
- Keep session permissions, local commit/PR preparation, machine-specific paths, raw test counts, logs and chronological implementation journals in `dev_docs/ai/`. Maintained documentation must remain understandable in a clean GitHub checkout without these files.
- Before finalizing a change, check documentation impact, local links and moved-file references. Describe relevant documentation changes, or briefly explain why none is needed. For documentation-only work, validate the documents and any edited workflow/configuration syntax; application tests are not required unless application behaviour also changes.

## Agent artifacts (Codex and Claude)

Both agents use `dev_docs/ai/tmp/<task>/` for temporary artifacts. Create the task directory before generating output and use a repository-root or absolute path when a command runs from another working directory. Point selectable report, screenshot, browser-profile and cache destinations there, including inside a container where the repository is mounted. Keep reusable local handoffs under `dev_docs/ai/sessions/` instead of temporary output.

Do not write agent scratch work to application `storage/`, the repository root or a general `/tmp` directory. Application-managed uploads, sessions, caches and diagnostic outputs with fixed destinations keep their own configured locations; do not alter application configuration just to relocate those files. Use a command's output-path option when it has one. Tool-managed internal temporary files whose destinations cannot be selected are outside this convention.

`dev_docs/` is ignored and is not required by CI or GitHub review. Do not include it in commits, upload its contents or erase another session's files as routine cleanup. Keep each task's artifacts together so they can be retained or removed deliberately.
