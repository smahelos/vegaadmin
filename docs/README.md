# Billex documentation

[Documentation policy](DOCUMENTATION.md) defines when to write, accept and maintain a document. [AGENTS.md](../AGENTS.md) is the shared source of project instructions, rule/workflow routing and review criteria for Codex and Claude; [.claude/CLAUDE.md](../.claude/CLAUDE.md) imports it.

## How the system works

| Document | Purpose |
|---|---|
| [System overview](system/OVERVIEW.md) | Bounded contexts, entry points, the path of a request, events |
| [User context](system/USER.md) | Accounts, roles and permissions, entity limits, locale |
| [Party context](system/PARTY.md) | Businesses, contacts, bank accounts and the parties stored on documents |
| [Invoice context](system/INVOICE.md) | Invoices and their lines, numbering, the guest invoice, PDFs, payment reminders |
| [Payment context](system/PAYMENT.md) | Subscriptions and the GoPay callback, QR payments, banks and payment methods |
| [Product context](system/PRODUCT.md) | Products, product categories and how a product becomes an invoice line |
| [Expense context](system/EXPENSE.md) | Expenses (received documents) in the admin: parties, attachments, categories and statuses |
| [Analytics context](system/ANALYTICS.md) | The user's dashboard and its charts, the admin dashboard statistics, the dashboard cache |

## Requirements and technical designs

| Document | Status and purpose |
|---|---|
| [Businesses and contacts PRD](prd/PRD_BUSINESSES_AND_CONTACTS.md) | Approved product specification, version 1.2; scope and A01–A29 |
| [Businesses and contacts design](design/DESIGN_BUSINESSES_AND_CONTACTS.md) | Agreed step 7 technical design |
| [Businesses and contacts verification map](prd/VERIFICATION_BUSINESSES_AND_CONTACTS.md) | Implemented paths, test locations and source invariants; separate from requirements and individual test-run results |
| [Entity limits PRD](prd/PRD_ENTITY_LIMITS.md) | Approved product specification, version 1.0; scope and A01–A17 |
| [Permissions PRD](prd/PRD_PERMISSIONS.md) | Approved product specification, version 1.1; scope and A01–A24 |
| [Grants PRD](prd/PRD_GRANTS.md) | Approved product specification, version 1.0; permissions and limits read once per request, A01–A10 |
| [PHP syntax enforcement](design/DESIGN_PHP_STYLE.md) | Implementation design for the agreed PHP readability standard; rolled out, numbered names remain |

## Architectural decisions

| ADR | Status |
|---|---|
| [D-042: Context service providers](adr/D-042-context-service-providers.md) | Accepted |
| [D-043: Modular monolith](adr/D-043-modular-monolith.md) | Accepted |
| [D-044: Document tables](adr/D-044-document-tables.md) | Proposed; the future storage design requires its own acceptance |
| [D-045: Businesses and contacts](adr/D-045-businesses-and-contacts.md) | Accepted |
| [D-046: Money amounts](adr/D-046-money-amounts.md) | Accepted |
| [D-047: Entity limits](adr/D-047-entity-limits.md) | Accepted; point 8 amended by D-049 |
| [D-048: Permission checks](adr/D-048-permission-checks.md) | Accepted; point 4 amended by D-049 |
| [D-049: Grants](adr/D-049-grants.md) | Accepted |

An accepted specification or ADR is not a claim that every described path has been implemented. Use the verification map and code/tests for delivered behaviour, and [TECH_DEBT](TECH_DEBT.md) for known gaps and the order of work. The code standard lives in [.claude/rules/](../.claude/rules/).

## Local working material

`dev_docs/` is ignored by Git. Keep reusable local handoffs and implementation journals in `dev_docs/ai/sessions/`; all agent-created temporary scripts, reports, logs, screenshots and browser profiles go in `dev_docs/ai/tmp/<task>/`. These files are optional local context and are not dependencies of the maintained documentation or GitHub review.
