# Technical Design: Businesses and Contacts

Date: 2026-10-26. Status: agreed technical specification for step 7 of the order of work ([TECH_DEBT](../TECH_DEBT.md)). Documentation reviewed 2026-10-30 without changing product decisions.
Product requirements: [PRD: Moje firmy a kontakty](../prd/PRD_BUSINESSES_AND_CONTACTS.md) (version 1.2). Decision: [D-045](../adr/D-045-businesses-and-contacts.md).
Implementation status and acceptance coverage: [VERIFICATION_BUSINESSES_AND_CONTACTS.md](../prd/VERIFICATION_BUSINESSES_AND_CONTACTS.md).

This document specifies how to build the PRD. It was settled question by question with the user (Q1–Q48); the numbers in brackets point to those decisions. Product behaviour is the PRD's; where this document and the PRD disagree, the PRD wins and this document is corrected. Future tense and the historical delivery plan below describe the agreed design, not remaining implementation tasks. VERIFICATION records the implemented scope and acceptance coverage; individual test runs are assessed with the change being delivered.

## 1. Schema

### Businesses and contacts (Q3, Q4, Q16)

| Column | `businesses` | `contacts` | Note |
|---|---|---|---|
| `id`, `user_id` | yes | yes | unique `(id, user_id)` for the composite foreign keys below |
| `name` | yes | yes | required |
| `short_name` | yes | yes | replaces `shortcut`; internal, never printed |
| `registration_country` | yes | yes | `char(2)`, required with a company number |
| `company_number` | yes | yes | replaces `ico`; text, leading zeros kept, empty is `NULL` |
| `czech_company_number` | yes | yes | virtual: `IF(registration_country = 'CZ', company_number, NULL)` |
| `vat_number` | yes | yes | replaces `dic` |
| `email`, `phone` | yes | yes | optional |
| `note` | yes | yes | replaces `description`; internal, never printed |
| `logo` | yes | yes | source logo; a document copies its supplier’s file |
| `contact_person` | — | yes | |
| `default_bank_account_id` | yes | yes | see default selections |
| `archived_at` | yes | yes | archiving, not Laravel soft deletes |
| timestamps | yes | yes | |

Indexes on both tables: `UNIQUE (user_id, czech_company_number)` (blocks a Czech IČO duplicate, archived records included, also under concurrent inserts; MySQL ignores `NULL` in unique keys), `(user_id, company_number)` for foreign-number warnings, `(user_id, archived_at, name)` for active and archived lists, `(user_id, email)`. A duplicate-key error (1062) on the Czech IČO becomes a domain exception carrying the ID of the existing record, so the form can offer it or its restoration.

`users.default_business_id` is nullable with a composite foreign key `(default_business_id, id) → businesses(id, user_id)`. `clients.is_default` and `suppliers.is_default` disappear. `user_activity_summary` reports `total_businesses` and `total_contacts`, archived records included.

### Addresses and bank accounts (Q1, Q2, Q17)

One `addresses` table and one `bank_accounts` table, each with nullable `business_id` and `contact_id` and `CHECK ((business_id IS NULL) <> (contact_id IS NULL))`: every row belongs to exactly one owner, with real foreign keys and no polymorphic IDs.

- `addresses`: `street`, `city`, `postal_code` (string 20), `country`, all nullable. An empty address is not stored; emptying every field deletes the row. The application allows one address per owner in version 1.
- `bank_accounts`: `label`, `iban` (no spaces, upper case), `number` (`prefix-number`), `bank_code`, `country`, `swift`, `bank_name`, `archived_at`. Czech and Slovak accounts store both forms, the missing one computed. `bank_code` is validated against the active rows of `banks` for that country (also fixes TECH_DEBT "Inactive banks are still offered").
- Default account: `businesses.default_bank_account_id` / `contacts.default_bank_account_id` with a composite foreign key `(default_bank_account_id, id) → bank_accounts(id, business_id)` / `(id, contact_id)`, so the default always belongs to its owner and there is never more than one. Whether the default is active is checked by the application.
- Account creation, restoration, archiving and default selection hold the owner's `users` row lock in the same transaction. The single pointer prevents two defaults; the lock also prevents selecting an account while another transaction archives it.

### Documents (Q6, Q7, Q18)

`invoices` and `expenses` store their parties as columns, not in a separate table or JSON:

- `supplier_*` and `customer_*`: `name`, `company_number`, `registration_country`, `vat_number`, `street`, `city`, `postal_code`, `country`, `email`, `phone`
- `account_*`: `iban`, `number`, `bank_code`, `country`, `swift`, `bank_name`
- short name, note and contact person are never copied
- `supplier_logo` is a private copy, including the file: an invoice copies the selected Business logo; an expense copies the external Contact supplier logo or accepts a one-off supplier upload without a contact. The own Business on an expense is the customer and never supplies its logo. Later source changes or deletion do not affect the document. Keeping the same supplier preserves the copy; changing the supplier copies the new source (including an empty logo), and explicit business reload on an invoice refreshes its logo too. Authenticated invoices have no independent logo upload; guest invoices retain a one-off upload.
- `NOT NULL` covers exactly the required document data (Q47): on invoices `supplier_*` and `customer_*` `name`, `street`, `city`, `postal_code` and `country`; on expenses the same five `supplier_*` columns and `customer_name`, since an expense does not require the business's address. Every other party and account column is nullable.

References: `business_id` NOT NULL with a composite foreign key `(business_id, user_id) → businesses(id, user_id)`; `contact_id` nullable, `(contact_id, user_id) → contacts(id, user_id)`; `bank_account_id` nullable, on invoices `(bank_account_id, business_id) → bank_accounts(id, business_id)`, on expenses `(bank_account_id, contact_id) → bank_accounts(id, contact_id)` with `CHECK (bank_account_id IS NULL OR contact_id IS NOT NULL)`, because MySQL does not check a composite foreign key when one of its columns is `NULL` and an expense's contact is optional. Indexes `(user_id, contact_id)` and `(user_id, customer_name)`. `client_id`, `supplier_id` and `products.supplier_id` disappear.

The generated unique key, the CHECK with restricting foreign keys and the composite default-account key were verified on MySQL 8.0 (local 8.0.46, CI `mysql:8.0`). The verification map identifies the schema and behavioural regression coverage; individual migration-run records are local working material.

## 2. Domain

### Value objects (Q5, Q12, Q13)

- `Domain/Shared/Geography/Enums/Country`: backed enum of ISO 3166-1 alpha-2 codes, generated once from ICU (`intl`) without the 15 codes that are not assigned countries (AC, CP, CQ, DG, EA, EU, EZ, IC, QO, TA, UN, XA, XB, XK, ZZ), which leaves the 249 assigned ISO 3166-1 codes; validated with `Rule::enum`; localized names through `Locale::getDisplayRegion` in Infrastructure.
- `Party/ValueObjects/RegistrationNumber`: Czech numbers are trimmed and must have 8 digits and a valid mod-11 checksum, without padding zeros; other countries are only trimmed.
- `Party/ValueObjects/VatNumber`: trimmed only.
- `Party/ValueObjects/Address`: the country is required once any other part is filled.
- `Payment/ValueObjects/BankAccount`: IBAN (mod 97) or a local number with bank code and country; SWIFT; an IBAN and a local number given together must match; `formattedIban()`, `toIban()`. Local numbers only for CZ and SK (weighted mod 11, conversion to IBAN and back); other countries take the IBAN.

### Shared party behaviour (Q14, Q19)

- `PartyProfile` (with `?Address`) is the shared part inside the business and contact write data and DTOs.
- A domain service checks duplicates for both entities.
- The domain service `BusinessCreator` creates a business with its capacity check, address, default selection and first bank account; `BusinessApplicationService` and `RegistrationApplicationService` (User context) both call it inside one transaction through `TransactionBoundaryInterface`.
- `AccountOwner` (`BusinessId` | `ContactId`) names the owner of a bank account.

### Capacity limits (Q8, Q9, Q10)

No counter. Creating or restoring a business or contact locks the owner (`SELECT … FROM users WHERE id = ? FOR UPDATE`), counts the active rows and inserts or restores in the same transaction (`rules/permissions.md`). Limits `limit_active_businesses_basic` (10) and `limit_active_contacts_basic` (16): entity types `business` / `contact`, `metric_type` `active`, `period_type` `lifetime`; the UELS dashboard shows the counted rows for `active`. The owner's limit applies in the admin too, without bypass: `can_create_{business,contact}_unlimited` is not introduced. The old `limit_yearly_{clients,suppliers}_basic`, the party `UsageRecordingHandler`, their `entity_limit_usage` rows and the client/supplier `_unlimited` permissions disappear.

### Events (Q26)

`BusinessCreated`, `BusinessUpdated`, `BusinessArchived`, `BusinessRestored`, the same four for `Contact`, and `DefaultBusinessChanged`, published by the application services. A Laravel listener invalidates the owner's dashboard cache after the commit. `ClientObserver`, `SupplierObserver` and the party `UsageRecordingHandler` go away.

## 3. Application (Q4, Q14)

- `BusinessApplicationService` and `ContactApplicationService` apply the shared capacity check on creation or restoration. `BankAccountApplicationService` serves either owner through `AccountOwner`; accounts consume no additional capacity, while their default/archive operations use the same owner lock for serialization.
- Ports: `Business`, `Contact` and `BankAccount` read and write repositories. Business/contact `searchForUser` takes `PartySearch` and `PageRequest` for active or archived lists; `listLatestActiveForUser` serves the latest widgets. `BusinessReadRepositoryInterface::listActiveForUser` supplies business selections; `findByIdForUser` also finds archived records. No physical delete.
- Deleted with their pairs: `InvoicePartyService`, `PartyApplicationService`, `PartyAuthorizationService`, `PartyInputValidationService`, `PartyCreationValidator`, `PartyDtoFactory`, the `*ArrayMapper` classes and the Application `Client*` / `Supplier*` repositories.
- `DocumentPartyDTO` is shared by prefix (`supplier_`, `customer_`); `PartyPrintDTO` is built from it. `DocumentParties` holds both parties and their source references, with `DocumentAccountDTO` carrying the validated `BankAccount`. Step 8 adds issue-time locking of the complete document and stored PDFs on top of these independent copies.
- `QrPaymentDTO` carries the stored `BankAccount` and Money through to the CZ/SK/EU providers; decimal amounts are rounded through Money without float conversion. `InvoiceReminderDTO` carries stored names, typed invoice identity and Money to the shared locale-aware mail factory; recipient addresses are selected from the stored document emails by the reminder application service.

## 4. Delivery

### Registration (Q20, Q21)

A Livewire component: login details plus business details (prefilled from the login, independent of it). A bank account is optional; when filled it becomes the first default. The shared Livewire form object `PartyDetailsForm` (fields, validation, ARES action) serves `RegistrationForm`, `BusinessForm`, `ContactForm` and `DocumentPartyPicker`. `/api/ares-lookup`, `AresLookupController` and `ares-lookup.js` go away.

### Frontend screens (Q22)

`Route::resource('businesses' | 'contacts')` with `index`, `create`, `show`, `edit` only; names `frontend.businesses.index` and so on. Livewire `Party/BusinessForm`, `ContactForm`, `BusinessList`, `ContactList` (active and archived), `BankAccounts`; archive, restore and default selection are Livewire actions. `InvoiceListBySupplier` and `InvoiceListByClient` are replaced by `InvoiceList` with independent or combined `business_id` / `contact_id` filters, alongside search and status. Filter options include owned archived parties for history, foreign/unknown IDs return 404, and changes reset pagination.

`PartyListLatest` shows five active owned records ordered by `created_at DESC, id DESC`, with a View all link and no pagination or COUNT query. Paginated lists use the common `Paginates::paginator` conversion and pagination view.

### Invoice form (Q23, Q24, Q29–Q34, Q44)

- Livewire `Party/DocumentPartyPicker` embedded in the Blade invoice form: business and account selection, contact search, one-off counterparty, "Save to contacts as well", explicit reload of the current data, archived source marked.
- The business is chosen, never typed. Its data and the account are copied when the invoice is saved and stay unchanged on later edits, unless the business changes or the user explicitly reloads; the form warns when the business has changed since. A business without a complete address is a validation error on `business_id` linking to the business's edit form.
- Required document data, the same for a selected contact, a one-off party, the frontend, the admin and guests: `supplier_*` and `customer_*` name, street, city, postal code and country. Company and VAT numbers are already stored when supplied; their requiredness by tax regime and further fiscal requirements belong to step 8.
- Account: only active accounts of the selected business, no one-off account on an issued invoice; required only for the `bankaccount` payment method; the default account is prefilled; a business without an active account shows a link to add one.
- The request accepts new references only when they are active and owned, keeps a stored archived reference, and answers a changed identity on a contact-backed party with a validation error that offers a one-off party.
- "Save to contacts" on the invoice detail, for an invoice without `contact_id`: one transaction with the capacity lock creates the contact and its address from `customer_*` and sets `contact_id`; the document data stay. A Czech IČO match offers "Link to the existing contact", which sets `contact_id` only. Requires `can_create_contact` and `can_update_invoice`. Step 7 offers it on every invoice without a contact; once step 8 introduces issuing, only on new invoices and drafts, because an issued invoice changes nothing, not even `contact_id`.
- Payment reminders read the stored `supplier_email` / `customer_email`; an empty address skips the reminder. Sending a document by email does not exist yet (TECH_DEBT "Invoices cannot be sent by email").
- Guest invoice: `DocumentPartyPicker` in guest mode, every party and the account one-off, no selections and no saving to contacts; `TemporaryInvoiceStore` keeps the `DocumentPartyDTO` shape and the print reads it like a stored invoice.
- Removed: `client-form-data.js`, `supplier-form-data.js`, `bank-fields.js`, `/api/client*`, `/api/supplier*`, `/api/admin/client*`, `/api/admin/supplier*`.

### Admin (Q25, Q35, Q36, Q45)

- `BusinessCrudController` and `ContactCrudController` with an owner select and the address, through the application services; archive and restore as buttons with POST routes, an active/archived filter, no Delete operation. `BankAccountCrudController` is opened from its owner.
- Admin invoice and expense: user → business (required, only that user's active ones) → contact (optional, only that user's active ones) → the counterparty fields (`customer_*` on the invoice, `supplier_*` on the expense). A new or changed contact copies its data into those fields on save and the typed values are ignored; without a contact the typed values are used (required as above); an edit that keeps the contact saves the typed values as a correction of the document. The business side and, on the invoice, the account come from the selected business. No "Save to contacts" in the admin.
- Expense account: an optional `bank_account_id` from the active accounts of the selected contact, or one-off `account_*` fields validated through `BankAccount`; copied by the same rule. Without a contact only the one-off fields.

### Permissions (Q11)

- Frontend: `frontend.can_{view,create,update,archive,restore}_{business,contact}` and `frontend.can_set_default_business`.
- Admin: `can_{view,create,update,archive,restore}_{business,contact}` and `can_set_default_business`.
- No delete permissions. Accounts, addresses and the default account fall under the owner's update. The API checks the same permissions plus `<guard>.api.access`. The client/supplier permissions are removed by a migration; every frontend role gets all the new ones.

### API (Q28)

The frontend stays on Livewire. A public API is built only for a real consumer, with its own ADR (authentication, versioning, resources over the same application services).

### Analytics (Q27)

Customer revenue groups by `contact_id` (name from the contact, archived ones included); documents without a contact group by `customer_company_number` + `customer_registration_country`, otherwise by `customer_name`. The former Party filter `clientIds` is replaced by `contactIds`. Existing aggregate and currency meanings stay unchanged; general chart/UELS resource conversion remains in TECH_DEBT.

## 5. Translations (Q39, Q40)

- `lang/*/parties.php`: the shared profile, address and bank account fields (`parties.fields.*`, `parties.bank_accounts.*`), hints, placeholders and validation.
- `businesses.php` and `contacts.php`: `entity`, `titles`, `actions`, `messages`, `filters` and their own fields (logo, contact person, default).
- `invoices.php` and `expenses.php`: only the role headings (supplier, customer) and document messages. `clients.php` and `suppliers.php` go away.

| Key | cs | sk | de | en |
|---|---|---|---|---|
| `company_number` | IČO | IČO | Registernummer | Company number |
| `vat_number` | DIČ | IČ DPH | USt-IdNr. | VAT number |
| Businesses | Moje firmy | Moje firmy | Meine Firmen | My businesses |
| Contacts | Kontakty | Kontakty | Kontakte | Contacts |

The company number has a hint that a foreign company enters the number from its own register.

## 6. Seeders and factories (Q37, Q38)

- `ReferenceDataSeeder`: the two capacity limits assigned to `frontend_user` and `frontend_user_plus`; the `clients` / `suppliers` archive policies are deleted and none is added for businesses or contacts (they are erased only with the account, step 17).
- `DevelopmentDataSeeder`, per frontend account: two active businesses (the default with a Czech account and a second EUR IBAN account) and one archived; today's six contacts (one Austrian), one without a company number, one archived, two expense suppliers with an account; invoices mostly with a contact and two with a one-off customer; expenses with a contact supplier and one-off. Everything within the 10 / 16 limits.
- Factories `BusinessFactory`, `ContactFactory`, `AddressFactory`, `BankAccountFactory` replace the old pairs; `ClientFactory` and `SupplierFactory` are deleted. Business and Contact factories expose `archived()`; bank-account fixtures set `archived_at` through factory state. Addresses have no archive state in this version.

## 7. Tests (Q41, Q42)

- Each acceptance scenario is tested in the layer that owns its invariant: Domain unit tests for value objects and domain services, Application feature tests on MySQL for ownership, limits, archiving, defaults and transactions, HTTP and Livewire tests for delivery and permissions, PDF snapshots for the printed roles. Test names describe behaviour, not scenario IDs; VERIFICATION maps A01–A29 to tests and records the source review for the PR.
- Concurrency: constraint tests insert directly and expect 1062 (Czech IČO, archived included), 3819 (the account owner CHECK and the expense account CHECK) and 1452 (the composite default and business keys). The capacity lock is tested with a second PDO connection with `innodb_lock_wait_timeout = 1`. That connection creates and commits the owner, because any locking read of a row inserted in the uncommitted test transaction waits on the insert's implicit lock and fails with 1205 even without the capacity lock. The test transaction then creates a contact (a business would update `users.default_business_id` and lock the row by itself): `SELECT … FROM users WHERE id = ? FOR SHARE` on the second connection succeeds before the call, since the foreign key checks hold only shared locks, and fails with 1205 after it. The second connection deletes the owner once the test transaction is rolled back. A10 combines the single-pointer constraint with the owner lock: `BankAccountDefaultSerializationTest` also probes the lock through a second PDO connection after setting the default, so a concurrent archive must wait.
- Tests about clients and suppliers are deleted or rewritten with the classes they test. VERIFICATION maps the final acceptance coverage; individual test-run results belong to the PR or local working material.

## 8. Agreed delivery sequence and completion (Q15, Q43)

The original plan used one branch `ls-businesses-contacts` and one PR, with phase commits following PRD §14:

1. Schema and seeders. The development invoices come in phase 4, created through the invoice application service once it stores the parties (Q48); phase 1 seeds the businesses, contacts, products and expenses.
2. Domain and Application.
3. Delivery, translations and permissions.
4. Documents, print, QR, products, expenses and analytics; PDF snapshots regenerated once.
5. Clean-up of the old pairs, TECH_DEBT items ticked off after verification.

During implementation, a phase commit could leave tests of code it had not reached yet failing (Q48). The phase gates were a test/development schema rebuild, rollback and reapplication of its migrations, the unit suite and new tests. This historical sequence does not call for another rebuild of existing local data. The final branch gate requires the full suite, static analysis, architecture checks, dependency audits and the applicable browser/build checks, with no skipped or excluded tests. VERIFICATION records the implemented paths and acceptance coverage. Individual test-run and migration records belong to delivery verification and local working material.

The rules, PRD and D-045 were established before implementation (`0242e349`, `e43175b5`). The final documentation review keeps these product decisions and aligns the technical names and verification descriptions with the implemented paths. Final delivery requires verification of the complete change and its affected documentation; a historical test run does not replace checks of a later change. Step 8 and the general standardization backlog are separate work.
