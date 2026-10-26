# D-045: The User's Businesses and Their Contacts Are Separate Entities

Status: Accepted 2026-10-25
Amended: 2026-10-26, product decisions for businesses, contacts and document parties; shared `addresses` and `bank_accounts` tables from the technical design (PRD 1.1).
Product specification: [PRD: Moje firmy a kontakty](../PRD_BUSINESSES_AND_CONTACTS.md).
This ADR records the target; the implementation remains planned.

## Context

The Party context stores two kinds of records under the names client and supplier:

- **`suppliers`** is the user's own identity, the business that issues invoices. Registration creates a default one from the user's data (`RegisterController::register`). It carries the logo and the bank account printed on the invoice and used for the QR payment. A user may have several.
- **`clients`** are the people and companies the user invoices.
- **`expenses.supplier_id`** points to `suppliers` as well, though on a received document the supplier is the company the user pays, not the user. Received documents are planned (`docs/adr/D-044-document-tables.md`, TECH_DEBT "Expenses are not modelled as received documents") and need that company as a record of its own.

Supplier and customer are roles on a document, not kinds of companies. On an issued invoice the user's business is the supplier and the other company is the customer; on a received invoice it is the other way round. The same company is often both: the user buys printing from it and also invoices it for design work.

The code duplicates the two kinds: 50 files come in 25 Client / Supplier pairs (models, repositories, mappers, ports, DTOs, write data, events, observers, controllers, requests, form fields, Livewire components), which `rules/architecture.md` calls a defect.

## Decision

1. **`Business`** (table `businesses`, UI "Moje firmy") is the user's own identity: name, address, company and VAT number, logo and bank accounts. It replaces `suppliers`. Registration creates the default business. Its name and communication details are independent of the login account. A future VAT payer profile belongs to the business, not the user; the behaviour is delivered with the document work in step 8.
2. **`Contact`** (table `contacts`, UI "Kontakty") is anyone the user does business with: the customers of issued documents and the suppliers of received documents. It replaces `clients`. All businesses of one user share one address book. A contact has no customer/supplier flags; the document supplies its role, and new selections search all active contacts. Only the name is required to create a contact; email and phone are optional. Sending a document by email requires a recipient address at that operation. Absence of a company number does not imply a private person.
3. **Documents identify their source records and store their own party data.** An issued document has `business_id` and optional `contact_id` (its customer), a received document has `business_id` and optional `contact_id` (its supplier). Supplier and customer stay words of the document and its print, not entity names. Their roles follow from the document direction (`D-044-document-tables.md`), never from contact flags. The issued print maps business to supplier and counterparty to customer; the received print reverses them. A test per delivered direction pins this and the source of the payment account.
4. **One class per entity** in the Party context. Behaviour that both entities share (address, company number lookup, archiving) is written once and used by both, not copied into a pair.
5. **Done together with step 7 of the order of work**, including the rename to `company_number` and `vat_number`, `PartyPrintDTO`, stored document-party and account data, addresses, bank accounts, archiving and active-count limits. These changes rewrite the same paths and PDF snapshots once. Full issued-document locking, stored PDFs and product archiving remain step 8.
6. **One-off counterparties need no contact.** A counterparty entered on a document is saved to the address book only through an explicit choice. Otherwise `contact_id` is NULL and its data remain on the document for display and printing. Selecting an address-book record copies its data into the document; later address-book changes do not refresh an existing document automatically. Updates in either direction are explicit.
7. **Registration identity has its own country**, required with a company number and independent of the address country. In the first version, only a matching Czech IČO is a blocking duplicate within the user's contact address book, including archived records. Other foreign country/number matches, names and emails produce warnings, not automatic merging or blocking. An archived match offers explicit restoration, never silent restoration or overwriting. This replaces the original universal `(user_id, company_number)` uniqueness decision; the conditional Czech identity is still protected by the database against concurrent inserts. The PRD links the international-register evidence behind this restriction.
8. **Addresses are separate records from the start.** One `addresses` table has real foreign keys `business_id` and `contact_id`, exactly one of them set (a `CHECK` constraint); `bank_accounts` has the same shape, so neither relies on polymorphic IDs. The first version manages one invoicing address per owner; the model supports later multiple-address selection and an explicit default address. Multiple addresses do not create copies of the contact. Delivery addresses are outside the first version.
9. **Businesses and contacts may have multiple bank accounts now.** Each owner has one common default selection across document currencies; no automatic currency routing. Accounts may be archived, and removing the default requires a replacement or an explicit state without an active account. A document stores its specific payment data independently. On an issued invoice the account comes from the business; a received document records its supplier's account. One-off payment data do not silently update the address book.
10. **The user's business has a default; their counterparty does not.** A new document prefills the default active business and allows another selection. The counterparty is explicitly selected, entered or supplied by an action on its detail. The old client default is removed.
11. **Archive and restore businesses and contacts in step 7.** They disappear from new selections but remain in the archive and on existing documents. Archiving a default business with other active ones requires an explicit replacement. The last active business may be archived: history remains available and another document cannot be issued without creating or restoring an active business.
12. **Plans limit active businesses and contacts separately per account.** Archiving frees capacity; restoring requires capacity, like creation. Registration's first business counts. Using an existing contact or a one-off counterparty consumes no new contact slot; addresses and accounts are not additional contacts or businesses. Downgrading does not erase or archive data or prevent editing/using existing records; creation and restoration require capacity under the new plan. Numeric plan values remain subscription-plan work.
13. **Products are shared by user.** Remove `products.supplier_id` without a business or contact replacement. Future assignment to businesses is recorded as a direction; its cardinality and usage are unknown, so no unused column or join table is introduced now.
14. **Adapt current expenses in step 7.** Replace `expenses.supplier_id` with `business_id` for the user's business and optional `contact_id` for the external supplier, or stored one-off party data. Adapt the forms, filters and ownership checks and store party/account data. Keep existing expense statuses and calculations until the full received-document work in step 8.
15. **Apply the PRD's edge rules.** Saving a new contact from a document is atomic with the document; later address-book saving is explicit. Archived sources remain identifiable on existing work without silent restoration. The first business created or restored when none is active becomes default; otherwise restoration leaves the default unchanged. Changing the business on a working document keeps product lines and requires that business's payment account. A Business and Contact may share registration identity without synchronization, with the same duplicate rules applied separately within each entity.

## Rationale

- **The model matches the documents.** A company that is both customer and supplier is one record with one company number, one lookup and one archive state.
- **Received documents get a real counterparty.** Today an expense could only point to the user's own businesses.
- **Document parties have one representation.** Step 7 stores the party and account data needed even without an address-book reference; step 8 freezes the whole issued document on top of it. Archiving covers business and contact rather than three overlapping address books.
- **The business can grow on its own**: VAT payer status and numbering series belong to its own operational profile. Bank accounts are available for contacts as well, because received documents need external payment details.
- **The duplicated pairs go away.** Two different entities need two sets of classes, but no longer two near-identical ones for the same concept.

## Alternatives Considered

| Option | Reason rejected |
|---|---|
| Keep `clients` and `suppliers`, share the code | The supplier of a received document has no place: it is either mixed with the user's own businesses or needs a third table almost identical to `clients`. Archiving and the party snapshot are built twice. |
| One `parties` table for businesses and contacts, with a type column | Own issuing identities and counterparties have different behaviour, defaults, permissions and plan limits; combining them requires repeated type scoping. Sparse bank columns are not the rationale: contacts also have bank accounts. |

## Implementation Boundaries

The three original questions (contact selections, counting limits and the product relation) were resolved on 2026-10-26. Granular permissions for the affected entities follow the repository standard; global permission conversion and numeric plan values remain separate work.

The PRD's final review also approved the edge behaviour and minimal adaptation of current expenses in step 7. No product scope decision remains open for this feature. Full received-document types, statuses and VAT behaviour remain step 8; detailed storage choices follow the accepted invariants without expanding this scope.

## Consequences

- Routes, translation files (`clients.php`, `suppliers.php` → `contacts.php`, `businesses.php`), permissions, admin CRUD, API endpoints, Livewire components and their tests are renamed in the step 7 branch. Billex is not in production, so no data is migrated and the development database is rebuilt.
- `rules/architecture.md` (bounded contexts) and the context list in `CLAUDE.md` are reworded from client/supplier to business/contact in the same branch.
- Step 7 stores document parties independently of the address book, including one-off counterparties and the specific payment account. Step 8 adds issue-time locking, complete snapshots and stored PDFs. No production data is changed by this documentation decision.
