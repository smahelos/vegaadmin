# Businesses and contacts — implementation and verification

Working branch: `ls-businesses-contacts`, implementation based on `f8ec0a4a`. This checkpoint covers the dashboard conversion, final cleanup and reminder rendering fixes; it does not close step 7 or the final PR gate. Requirements remain in [PRD](PRD_BUSINESSES_AND_CONTACTS.md), [DESIGN](DESIGN_BUSINESSES_AND_CONTACTS.md) and [D-045](adr/D-045-businesses-and-contacts.md); this document records implementation status without changing those requirements.

## Delivered paths

- Business and Contact replace the old entities. Shared profiles, addresses, bank accounts, duplicate checks, archiving, restoration and capacity checks have one implementation. Source identity is separate from the login account.
- Documents store their own supplier/customer and payment data. One-off counterparties need no address-book entry. Issued invoices copy the Business logo; expenses copy the external Contact supplier logo or accept a one-off logo. Each document owns its file copy.
- Reminder notifications render their six templates from stored supplier/customer names, invoice identity and Money. A shared mail factory formats dates and amounts for the recipient locale; rendering is covered in all four locales.
- Frontend and admin selectors scope records to their owner. Existing archived references remain identifiable; new choices use active records. Products belong to the user, and the admin invoice editor saves lines and computes totals through the application path.
- Analytics includes archived sources and one-off customers. Revenue grouping uses contact identity, then registration number and country, then name. Dashboard projections use ids, DTOs and Money. Owner cache invalidation runs after commit.
- Latest-party widgets show five active records ordered by `created_at DESC, id DESC`, with a View all link and no pagination or COUNT query. Paginated lists use the common `Paginates::paginator` conversion.
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
| A10 account defaults and owner constraints | `Database/Migrations/BusinessAndContactConstraintTest`, `Application/Party/Services/BankAccountApplicationServiceTest` |
| A11 account validation and archive | `tests/Unit/Domain/Payment/ValueObjects/BankAccountTest`, `Livewire/Party/BankAccountsTest`, `Application/Party/Services/BankAccountApplicationServiceTest` |
| A12 issued-document roles and print | `Domain/Invoice/Services/InvoicePrintDataBuilderTest`, `Domain/Invoice/Services/InvoicePdfSnapshotTest`, `Domain/Invoice/Services/InvoicePdfServiceFeatureTest` |
| A13 expense roles and external account | `Http/Controllers/Admin/ExpenseCrudControllerTest`, `Http/Controllers/Admin/DocumentSupplierLogoTest` |
| A14 archive, selections and history | `Application/Party/Services/ContactApplicationServiceTest`, `Http/Controllers/Admin/AdminDocumentSnapshotsTest`, `Infrastructure/Persistence/Eloquent/Analytics/Repositories/CustomerRevenueTest` |
| A15 default and last business | `Application/Party/Services/BusinessApplicationServiceTest`, `Application/Invoice/Services/InvoicePartiesAssemblerTest` |
| A16 capacity, restoration and serialization | `Application/Party/Services/ContactApplicationServiceTest`, `Domain/Party/Services/CapacityLimitTest`, `Http/Controllers/Api/PartyCapacityTest` |
| A17 downgrade without erasure | `Application/Party/Services/BusinessApplicationServiceTest`, `Application/Party/Services/ContactApplicationServiceTest` |
| A18 ownership across boundaries | `Http/Controllers/Frontend/PartyPagesTest`, `Http/Controllers/Frontend/InvoiceContactControllerTest`, `Http/Controllers/Admin/DocumentPartyCrudControllerTest`, `Http/Controllers/Api/PartyCapacityTest` |
| A19 admin and frontend invariants | `Http/Controllers/Admin/BusinessCrudControllerTest`, `Http/Controllers/Admin/ContactCrudControllerTest`, `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Admin/ExpenseCrudControllerTest` |
| A20 atomic registration and independent identity | `Application/User/Services/RegistrationApplicationServiceTest`, `Livewire/User/RegistrationFormTest` |
| A21 business prefill and explicit customer | `Livewire/Party/DocumentPartyPickerTest`, `Http/Controllers/Frontend/InvoiceCreateControllerFeatureTest` |
| A22 complete analytics | `Infrastructure/Persistence/Eloquent/Analytics/Repositories/CustomerRevenueTest`, `Domain/Analytics/Services/DashboardServiceFeatureTest`, `Domain/Analytics/PartyDashboardInvalidationTest` |
| A23 locales and PDF content | `Architecture/TranslationKeysExistTest`, `Domain/Invoice/Services/InvoicePdfSnapshotTest`, `Infrastructure/Notifications/InvoiceReminderRenderingTest` |
| A24 document correction and explicit address-book save | `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Frontend/InvoiceContactControllerTest` |
| A25 document-only email and account data | `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Admin/ExpenseCrudControllerTest`; sending an invoice by email remains separate work |
| A26 archived references during editing | `Http/Controllers/Admin/AdminDocumentSnapshotsTest`, `Livewire/Party/DocumentPartyPickerTest`, `Application/Invoice/Services/InvoicePartiesAssemblerTest` |
| A27 restoring defaults | `Application/Party/Services/BusinessApplicationServiceTest` |
| A28 company change, lines and counterparty identity | `Http/Controllers/Admin/InvoiceCrudControllerTest`, `Http/Controllers/Admin/InvoiceItemsControllerTest`, `Http/Controllers/Frontend/InvoiceContactControllerTest`, `Application/Invoice/Services/InvoicePartiesAssemblerTest` |
| A29 same identity across entity kinds | `Application/Party/Services/BusinessApplicationServiceTest`, `Database/Migrations/BusinessAndContactConstraintTest` |

## Verification artifacts

Local logs are under `storage/app/tmp-step7/codex47-*`. They include migration rebuild, rollback and reapplication of all ten branch migrations, reference and development seeders, complete PHP formatting/static checks, the full test suite, five architecture guards, dependency audits and browser checks. The handoff `codex47-work-state.md` records the final counts and any failures. Both cleanup migrations are also applied to the local `invoice` database; invoice, archive, business and contact row counts were preserved.

Browser checks cover dashboard charts and contact filtering, both latest-party widgets, frontend invoice create/edit, business/contact logo previews and guest invoice logo preview across desktop/mobile and light/dark layouts. They save no business records. PDF snapshots are checked, not regenerated by this cleanup.

## Remaining boundaries

Step 7 is not closed merely by the cleanup or green tests. The final gate requires reviewing the coverage and the complete branch. Composer audit still reports the existing `symfony/mime` advisory CVE-2026-45070, fixed in 7.4.22; dependency updates are outside this cleanup.

Issue-time locking, immutable stored PDFs, product archiving and the full received-document model remain step 8. QR payload/provider conversion, the general DTO conversion, API/UELS resources, maintenance services and remaining frontend globals are tracked in [TECH_DEBT](TECH_DEBT.md). Financial aggregate semantics have not been changed to introduce currency conversion.
