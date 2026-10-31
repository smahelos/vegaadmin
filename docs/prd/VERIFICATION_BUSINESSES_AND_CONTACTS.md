# Businesses and contacts — implementation and acceptance coverage

Reviewed: 2026-10-30. Status: the step 7 Business/Contact scope is implemented and technically verified. This document records delivered behaviour and the locations that verify it; it is not the product specification or a record of one particular test run.

Requirements: [PRD](PRD_BUSINESSES_AND_CONTACTS.md). Technical design: [DESIGN](../design/DESIGN_BUSINESSES_AND_CONTACTS.md). Decision: [D-045](../adr/D-045-businesses-and-contacts.md).

## Implemented paths

- Business and Contact replace the old entities. Shared profiles, addresses, bank accounts, duplicate checks, archiving, restoration and capacity checks have one implementation. Source identity is separate from the login account.
- Documents store their own supplier/customer and payment data. One-off counterparties need no address-book entry. Issued invoices copy the Business logo; expenses copy the external Contact supplier logo or accept a one-off logo. Each document owns its file copy.
- Reminder notifications render their six templates from stored supplier/customer names, invoice identity and Money. A shared mail factory formats dates and amounts for the recipient locale; rendering is covered in all four locales.
- Frontend and admin selectors scope records to their owner. Existing archived references remain identifiable; new choices use active records. Products belong to the user, and the admin invoice editor saves lines and computes totals through the application path.
- Analytics includes archived sources and one-off customers. Revenue grouping uses contact identity, then registration number and country, then name. Dashboard projections use ids, DTOs and Money. Owner cache invalidation runs after commit.
- Latest-party widgets show five active records ordered by `created_at DESC, id DESC`, with a View all link and no pagination or COUNT query. Paginated lists use the common `Paginates::paginator` conversion.
- The common invoice list filters by business and contact, independently or together with stored-customer/number search and enum status. Reads use an application service, a Domain read port and typed projections. Options include owned archived sources; unknown/foreign references return 404, and filter changes reset pagination. Money and due dates use locale formatters.
- QR providers consume the same validated `BankAccount` stored on the document. `QrPaymentDTO` carries it without decomposing/reparsing account strings. Decimal output rounds half up through Money and pads two places without float conversion; amounts rounding to zero produce no QR. Country selection uses the account country; the existing CZ/SK/EU format coverage is retained. `InvoiceReminderDTO` replaces the remaining reminder payload contract.
- Default bank-account changes hold the owner lock in their transaction, like account archiving, so a concurrent archive cannot race the active-account check. Account mutations also require the selected Business/Contact owner, including calls made directly to the application service; an account of another owner in the same user account is refused. Expenses require the own business name while invoices require its full address, matching DESIGN Q47.
- Capacity and first-business default decisions use current locking reads after locking the user. Earlier consistent reads in the same transaction cannot hide another request's committed activation. Counterparty identity checks apply to frontend and admin documents, while an unchanged historical identity remains valid after the source contact is corrected.
- Obsolete client/supplier permissions are removed from the catalog and existing grants by a dedicated migration. Its rollback restores definitions and system-role defaults; arbitrary grants of removed permissions are not recovered. Additive catalog sync continues to preserve unrelated permissions.
- UELS API uses business/contact names and active lifetime counts. Monitoring queries use current tables. Invoice archival stores business/contact references and the full stored document data; it never erases businesses or contacts. This preserves the existing retention behavior pending step 8.
- Old translation files, selectors, upload configuration and unused classes are removed. Document role names `supplier` / `customer` remain. Historical migrations retain old names for rollback; GoPay credentials and HTTP clients are unrelated to Party entities.

## Acceptance test map

These are the principal regression locations for PRD A01–A29. A row identifies related coverage, rather than claiming that one test alone proves the whole scenario. Paths below are relative to `tests/Feature`, except the explicitly named unit tests.

| Scenario | Principal tests |
|---|---|
| A01 shared contacts and products | `Http/Controllers/Frontend/SharedProductCatalogTest`, `Http/Controllers/Admin/InvoiceItemsControllerTest`, `Application/Invoice/Services/InvoicePartiesAssemblerTest` |
| A02 optional profile fields and clearing | `Application/Party/Services/ContactApplicationServiceTest`, `Livewire/Party/ContactFormTest` |
| A03 registration country | `tests/Unit/Domain/Party/ValueObjects/RegistrationNumberTest`, `Livewire/Party/ContactFormTest` |
| A04 Czech constraint and foreign warnings | `Database/Migrations/BusinessAndContactConstraintTest`, `Application/Party/Services/ContactApplicationServiceTest` |
| A05 contacts without document roles | `Livewire/Party/DocumentPartyPickerTest`, `Http/Controllers/Admin/DocumentPartyCrudControllerTest` |
| A06 one-off document data | `Http/Controllers/Admin/ExpenseCrudControllerTest`, `Domain/Invoice/Services/InvoicePdfServiceFeatureTest`, `Infrastructure/Persistence/Eloquent/Analytics/Repositories/CustomerRevenueTest` |
| A07 explicit saving, permissions and atomicity | `Application/Invoice/Services/InvoicePartiesAssemblerTest`, `Http/Controllers/Frontend/InvoiceControllerFeatureTest`, `Application/Invoice/Services/Specialized/InvoiceCrudApplicationServiceTest` |
| A08 stored copies and explicit reload | `Application/Invoice/Services/InvoicePartiesAssemblerTest`, `Livewire/Party/DocumentPartyPickerTest`, `Http/Controllers/Admin/AdminDocumentSnapshotsTest` |
| A09 address ownership | `Database/Migrations/BusinessAndContactConstraintTest`, `Application/Party/Services/ContactApplicationServiceTest` |
| A10 account defaults and owner constraints | `Database/Migrations/BusinessAndContactConstraintTest`, `Application/Party/Services/BankAccountApplicationServiceTest`, `Domain/Party/Services/BankAccountDefaultSerializationTest` |
| A11 account validation and archive | `tests/Unit/Domain/Payment/ValueObjects/BankAccountTest` (including checksum-valid malformed CZ/SK IBANs), `Livewire/Party/BankAccountsTest`, `Application/Party/Services/BankAccountApplicationServiceTest` |
| A12 issued-document roles and print | `Domain/Invoice/Services/InvoicePrintDataBuilderTest`, `Domain/Invoice/Services/InvoicePdfSnapshotTest`, `Domain/Invoice/Services/InvoicePdfServiceFeatureTest` |
| A13 expense roles, optional own-business address and external account | `Http/Controllers/Admin/ExpenseCrudControllerTest`, `Http/Controllers/Admin/DocumentSupplierLogoTest` |
| A14 archive, selections and history | `Application/Party/Services/ContactApplicationServiceTest`, `Http/Controllers/Admin/AdminDocumentSnapshotsTest`, `Infrastructure/Persistence/Eloquent/Analytics/Repositories/CustomerRevenueTest` |
| A15 default and last business | `Application/Party/Services/BusinessApplicationServiceTest`, `Application/Invoice/Services/InvoicePartiesAssemblerTest` |
| A16 capacity, restoration and serialization | `Application/Party/Services/ContactApplicationServiceTest`, `Domain/Party/Services/CapacityLimitTest`, `Http/Controllers/Api/PartyCapacityTest` |
| A17 downgrade without erasure | `Application/Party/Services/BusinessApplicationServiceTest`, `Application/Party/Services/ContactApplicationServiceTest` |
| A18 ownership across boundaries and invoice filters | `Http/Controllers/Frontend/PartyPagesTest`, `Http/Controllers/Frontend/InvoiceContactControllerTest`, `Http/Controllers/Admin/DocumentPartyCrudControllerTest`, `Http/Controllers/Api/PartyCapacityTest`, `Livewire/Invoice/InvoiceListFeatureTest`, `Application/Invoice/Services/InvoiceListApplicationServiceTest` |
| A19 admin and frontend invariants | `Http/Controllers/Admin/BusinessCrudControllerTest`, `Http/Controllers/Admin/ContactCrudControllerTest`, `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Admin/ExpenseCrudControllerTest` |
| A20 atomic registration and independent identity | `Application/User/Services/RegistrationApplicationServiceTest`, `Livewire/User/RegistrationFormTest` |
| A21 business prefill and explicit customer | `Livewire/Party/DocumentPartyPickerTest`, `Http/Controllers/Frontend/InvoiceCreateControllerFeatureTest` |
| A22 complete analytics | `Infrastructure/Persistence/Eloquent/Analytics/Repositories/CustomerRevenueTest`, `Domain/Analytics/Services/DashboardServiceFeatureTest`, `Domain/Analytics/PartyDashboardInvalidationTest` |
| A23 locales and PDF content | `Architecture/TranslationKeysExistTest`, `Domain/Invoice/Services/InvoicePdfSnapshotTest`, `Infrastructure/Notifications/InvoiceReminderRenderingTest` |
| A24 document correction and explicit address-book save | `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Frontend/InvoiceContactControllerTest` |
| A25 document-only email and account data | `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Admin/ExpenseCrudControllerTest`; sending an invoice by email remains separate work |
| A26 archived references during editing | `Http/Controllers/Admin/AdminDocumentSnapshotsTest`, `Livewire/Party/DocumentPartyPickerTest`, `Application/Invoice/Services/InvoicePartiesAssemblerTest` |
| A27 restoring defaults | `Application/Party/Services/BusinessApplicationServiceTest` |
| A28 company change, lines and counterparty identity | `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Admin/AdminDocumentSnapshotsTest`, `Http/Controllers/Admin/InvoiceItemsControllerTest`, `Http/Controllers/Frontend/InvoiceContactControllerTest`, `Application/Invoice/Services/InvoicePartiesAssemblerTest` |
| A29 same identity across entity kinds | `Application/Party/Services/BusinessApplicationServiceTest`, `Database/Migrations/BusinessAndContactConstraintTest` |

## Source review against A01–A29

The review compares the PRD with the actual storage, use cases and consumers, in addition to the tests listed above. File/class names below identify the owning implementation. This is a review of the delivered scope; it does not introduce step 8 requirements.

| Scenario | Source invariant checked and review result |
|---|---|
| A01 | Products and contacts are scoped to `user_id`; `InvoiceProductOwnership` and the document assemblers do not assign them to a business. The two-business product regression uses real saves. |
| A02 | `PartyInput` requires a name only; `EloquentPartyProfileMapper` and `PartyRecords::saveAddress` persist cleared optional values and delete an emptied address. |
| A03 | `RegistrationNumber` requires a country for a filled number; `PartyDetailsForm::prefillRegistrationCountry` only fills an empty choice and never follows later address changes. |
| A04 | Separate generated Czech unique keys include archived rows; `PartyRecords` maps 1062 to the existing party id. `DuplicateFinder` only blocks Czech identity and reports foreign/name/email warnings. |
| A05 | `DocumentPartyPicker::contactResults` searches all active owned contacts; no role or previous-document condition exists. |
| A06 | `DocumentParties` persists a nullable contact reference and full supplier/customer/account values; `InvoicePrintDataBuilder` reads these stored values, including guests. No implicit contact creation occurs. |
| A07 | `InvoiceCrudApplicationService` wraps contact assembly, document and lines in one transaction. Request authorization checks saving to contacts; capacity and Czech uniqueness remain enforced. Added a late line-write failure regression proving contact, address, invoice and usage rollback. |
| A08 | Invoice/expense assemblers retain stored party/account data for unchanged references; explicit business reload copies current data. Print and QR read stored account data even after the source account changes and is archived. |
| A09 | `PartyRecords` manages one address through the owner's relation; separate owner foreign keys and the address CHECK reject invalid owners. |
| A10 | One default pointer per owner and a composite FK prevent multiple/foreign defaults. First-account creation and default changes are serialized with archive/restore by the user lock. The new second-PDO regression failed before the `setDefault` transaction fix. No currency routing exists. |
| A11 | `BankAccount` validates IBAN and supported CZ/SK notation once, with exactly 24 characters and a numeric body for CZ/SK before conversion; the reconstructed IBAN must equal the supplied value. Checksum-valid extra digits cannot be silently discarded. Account archival requires a replacement when other active accounts remain. Historical copies are independent. |
| A12 | `InvoicePartiesAssembler` maps business to supplier and selected business account to payment; the customer is the contact/one-off copy. PDF snapshots and QR exercise this path. |
| A13 | `ExpensePartiesAssembler` maps own business to customer and external contact/one-off to supplier, with the external account. Review found the invoice-only complete-address check also applied to the expense customer; the HTTP regression failed before restricting that check to invoice suppliers (DESIGN Q47). Existing expense statuses/calculations stay. |
| A14 | New selections exclude archived sources; stored references remain readable. Invoice filter options explicitly include owned archived parties for history and never restore them. |
| A15 | `BusinessApplicationService::archive` requires a replacement default if another active business exists; the last archive clears the pointer. A new invoice rejects an archived business. |
| A16 | `CapacityLimit` locks the user and counts active rows through a current locking read. Create/restore run inside that transaction; no configured limit allows no creation. Second-PDO tests prove both serialization independently of FK locks and refusal after another request fills the last slot following an earlier consistent read. Business default selection also uses a current count. |
| A17 | Capacity runs on create/restore only; edits, existing-document use and archival do not require a free slot or erase records on downgrade. |
| A18 | Scoped Party/document reads and composite FKs guard ownership; admin selectors scope to the record owner. Bank-account operations bind the supplied id to the selected Business/Contact, so another owner's account cannot be changed through a component with different permissions. The list service validates filter ownership and invoice-view permission before reads; malformed ids are rejected at delivery. |
| A19 | Party, invoice and expense delivery reuse the same Domain values and application paths. Admin operations check their granular permissions and apply the record owner's capacity. The remaining API consumers (selectors/UELS) are covered; a new public CRUD API is not part of DESIGN Q28. |
| A20 | `RegistrationApplicationService` creates the login, plan role and first business/default/account in one transaction. `UserWriteData` contains login identity separately; no business synchronization appears in account update persistence. |
| A21 | Picker startup reads `users.default_business_id`; contact is only explicitly selected/entered. There is no contact default or document-currency account routing. |
| A22 | `CustomerRevenueQuery` left-joins owned contacts, including archive, and distinguishes contact, country/registration and name groups. Owner cache invalidation runs after commit. Currency aggregate semantics remain unchanged. |
| A23 | Delivered Party/filter labels use cs/de/en/sk keys; PDF snapshots and all six reminder templates have locale checks. Browser verification covers the new filters in all four locales and both layouts/themes. Inherited unrelated UELS wording remains in its standardization scope. |
| A24 | `linkContact` in the picker preserves entered fields; explicit reload fills them. The stored-invoice contact service only changes the reference, with duplicate, ownership, identity, capacity and request-permission checks. |
| A25 | Expense/guest one-off accounts stay on the document and do not update the address book; document corrections preserve contact data. Sending documents by email does not yet exist and remains explicitly outside this delivery. Reminders use stored emails. |
| A26 | Assemblers distinguish stored and new references; stored archived contact/account copies survive, while new archived selections fail. Added regressions for both account cases; the picker visibly marks archived sources. |
| A27 | Business creation/restoration sets default only when no business is active. Restoration does not change independently archived account rows. |
| A28 | Changing business forces its account ownership check while product lines remain a separate user-owned catalogue. Counterparty identity changes fail in frontend and admin unless a matching contact or one-off selection is used. Keeping the stored identity after an address-book correction remains allowed, including archived references; expense validation errors identify the supplier field. |
| A29 | Business and Contact have separate uniqueness constraints and shared duplicate logic. Neither creation nor update copies or synchronizes the other entity kind. |

## Remaining boundaries

Step 8 covers issue-time locking, immutable stored PDFs, issue-time numbering and variable-symbol rules, product archiving, the full received-document model and the Business tax profile. Sending an invoice by email remains separate work; A25's sending rule applies when that operation exists. Independent party, account and logo copies in step 7 do not freeze the complete document.

General API/UELS resources, maintenance services, remaining mutation DTOs and frontend globals remain in [TECH_DEBT](../TECH_DEBT.md). Existing financial aggregate meanings are unchanged. Test-run logs, local implementation journals and delivery preparation are local working material under the [documentation policy](../DOCUMENTATION.md); this map remains usable without them.
