# D-044: Documents Share One Table, Types Add Their Own Tables

Status: Proposed 2026-10-25

## Context

Billex issues and receives tax documents of several types. Issued: invoice, corrective tax document (credit or debit note), advance invoice. Received: invoice, simplified tax document or receipt, corrective document, advance invoice with the tax document for the payment. Users are VAT payers and non-payers, and the app is meant to grow into more types and VAT regimes (reverse charge, acquisition from the EU) without reshaping its tables (TECH_DEBT "Issued invoices can be changed and deleted", "Expenses are not modelled as received documents").

All types share most of their data: parties, number, dates, currency, totals, payment state, VAT lines by rate. Each type adds a few fields of its own: a corrective document refers to the document it corrects, an advance invoice is settled by a later tax document, a receipt has no customer.

## Decision

1. **One document table** per direction holds what every type has, with a `type` column backed by an enum. Lists, filters, sorting and statistics read this table only.
2. **A type-specific table** (class table inheritance: primary key = foreign key to the document) holds the fields only one type has. It exists only when a field qualifies by the criteria below.
3. **Shared VAT lines**: one table of VAT lines by rate (base, rate, VAT amount) for every document type, issued and received. A non-payer's document has no VAT lines, only its total.
4. **One Domain class per type** (`Invoice`, `CorrectiveDocument`, `ReceivedInvoice`, `Receipt`, …) with its own invariants, built by the mapper from the common row and its type-specific row. The storage layout stays an Infrastructure detail.
5. **The user decides every type-specific table and column.** Each one is proposed with its fields, the criteria it meets and a recommendation; none is added without that decision.

### Where a field goes

| Field goes to… | When |
|---|---|
| the type-specific table | it is required for that type (a `NOT NULL` in the common table cannot enforce it for one type); it is a foreign key only that type has; or it belongs to a group of fields that only make sense together |
| the common table | it is optional; another type has it or may have it; or lists filter or sort by it, so they need no join |

Examples: the reference of a corrective document to the document it corrects is required and a foreign key only that type has → type-specific table. The supplier's document number exists on every received type and is searched for → common table.

## Rationale

- **Lists stay one query.** Most screens list documents of all types; one table with indexed common columns needs no join or `UNION`.
- **Integrity per type.** Required fields and foreign keys of one type are enforced by the database in its own table, which a single wide table with nullable columns cannot do.
- **Growth without migrations of existing rows.** A new type adds an enum case and, if needed, a table; existing documents are untouched.
- **VAT is computed once.** Shared VAT lines give the VAT return and statistics one source for issued and received documents.
- **Domain types stay honest.** A class per type keeps type rules out of `if ($type === …)` branches, whatever the storage.

## Alternatives Considered

| Option | Reason rejected |
|---|---|
| One table for all types with nullable type columns (single table inheritance) | Required fields of one type cannot be enforced; the table grows with every type |
| A complete table per type (concrete table inheritance) | Lists and statistics over all documents need `UNION`s; common columns are duplicated and drift apart |
| Type-specific data in a JSON column | No foreign keys, no constraints, no indexes on the data that differs most |

## Consequences

- The issued-invoice work (TECH_DEBT step 8) introduces the document type enum, the VAT lines and the first type-specific table (corrective documents); the received-documents work reuses them.
- Repositories load the common row with its type-specific row and VAT lines in one method (`rules/persistence.md`); the mapper picks the Domain class by the type.
- Revisit when a type needs so many own fields that most of its data lives outside the common table.
