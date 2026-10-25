# D-045: The User's Businesses and Their Contacts Are Separate Entities

Status: Proposed 2026-10-25

## Context

The Party context stores two kinds of records under the names client and supplier:

- **`suppliers`** is the user's own identity, the business that issues invoices. Registration creates a default one from the user's data (`RegisterController::register`). It carries the logo and the bank account printed on the invoice and used for the QR payment. A user may have several.
- **`clients`** are the people and companies the user invoices.
- **`expenses.supplier_id`** points to `suppliers` as well, though on a received document the supplier is the company the user pays, not the user. Received documents are planned (`docs/adr/D-044-document-tables.md`, TECH_DEBT "Expenses are not modelled as received documents") and need that company as a record of its own.

Supplier and customer are roles on a document, not kinds of companies. On an issued invoice the user's business is the supplier and the other company is the customer; on a received invoice it is the other way round. The same company is often both: the user buys printing from it and also invoices it for design work.

The code duplicates the two kinds: 55 files come in Client / Supplier pairs (models, repositories, mappers, ports, DTOs, write data, events, observers, controllers, requests, form fields, Livewire components), which `rules/architecture.md` calls a defect.

## Decision

1. **`Business`** (table `businesses`, UI "Moje firmy") is the user's own identity: name, address, company and VAT number, logo, bank accounts, and later the VAT payer flag. It replaces `suppliers`. Registration creates the default business.
2. **`Contact`** (table `contacts`, UI "Kontakty") is anyone the user does business with: the customers of issued documents and the suppliers of received documents. It replaces `clients`. A company has one contact record whatever role it plays, unique by `(user_id, company_number)`.
3. **Documents name the role in their foreign keys**: an issued document has `business_id` and `contact_id` (its customer), a received document has `business_id` and `contact_id` (its supplier). Supplier and customer stay words of the document and its print, not entity names.
4. **One class per entity** in the Party context. Behaviour that both entities share (address, company number lookup, archiving) is written once and used by both, not copied into a pair.
5. **Done together with step 7 of the order of work** (renaming IČO and DIČ to `company_number` and `vat_number`, `PartyPrintData`), in one branch. Both rewrite the same files and the PDF snapshots, so they are rewritten once.

## Rationale

- **The model matches the documents.** A company that is both customer and supplier is one record with one company number, one lookup and one archive state.
- **Received documents get a real counterparty.** Today an expense could only point to the user's own businesses.
- **Steps 7 and 8 are done once.** The party snapshot taken at issue and the archiving with restore by company number cover two entities, not three (clients, suppliers, suppliers of expenses).
- **The business can grow on its own**: several bank accounts, VAT payer status, numbering series, without those columns appearing on every contact.
- **The duplicated pairs go away.** Two different entities need two sets of classes, but no longer two near-identical ones for the same concept.

## Alternatives Considered

| Option | Reason rejected |
|---|---|
| Keep `clients` and `suppliers`, share the code | The supplier of a received document has no place: it is either mixed with the user's own businesses or needs a third table almost identical to `clients`. Archiving and the party snapshot are built twice. |
| One `parties` table for businesses and contacts, with a type column | Logo, bank and VAT payer columns are empty for every contact; every query filters by type; the user's own business can leak into the customer select; permissions and plan limits differ by type anyway. |

## Open Questions

Decided when the work starts, by the user:

- **Contact selects by role.** The customer select of an invoice would also list companies the user only buys from. Options: a role flag on the contact, roles derived from the documents, or no filter with search.
- **Permissions and plan limits.** `can_create_edit_client` / `can_create_edit_supplier` and the UELS limits for clients and suppliers become limits on contacts and businesses; how the plans count them belongs to "Permission naming" and the subscription plans (steps 10 and 12).
- **`products.supplier_id`.** The product form offers the user's suppliers (today's businesses), without a foreign key. Whether it means the business that sells the product or the company it is bought from decides whether it becomes `business_id`, `contact_id`, or is dropped.

## Consequences

- Routes, translation files (`clients.php`, `suppliers.php` → `contacts.php`, `businesses.php`), permissions, admin CRUD, API endpoints, Livewire components and their tests are renamed in the step 7 branch. Billex is not in production, so no data is migrated and the development database is rebuilt.
- `rules/architecture.md` (bounded contexts) and the context list in `CLAUDE.md` are reworded from client/supplier to business/contact in the same branch.
- The issued-invoice work (step 8) snapshots the business and the contact of a document; the received-documents work points expenses at contacts.
