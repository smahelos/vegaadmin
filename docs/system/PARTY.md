# Party context

Status: maintained map of the code on `main`, last checked 2026-11-23. Start with [the system overview](OVERVIEW.md). The schema and the reasons are in [the design](../design/DESIGN_BUSINESSES_AND_CONTACTS.md), the behaviour in `docs/prd/PRD_BUSINESSES_AND_CONTACTS.md`, the tests of each acceptance criterion in [the verification map](../prd/VERIFICATION_BUSINESSES_AND_CONTACTS.md), the decision in `docs/adr/D-045-businesses-and-contacts.md`.

The user's businesses (their own identity on documents) and contacts (anyone they do business with), with their addresses and bank accounts, and the copies of both that invoices and expenses store. Supplier and customer are roles on a document, not entities. Composition root: `PartyServiceProvider`.

## Entities

| Entity | Table | Code |
|---|---|---|
| Business | `businesses` | `App\Models\Business`, `BusinessDTO`, `BusinessId`, ports `BusinessReadRepositoryInterface` and `BusinessWriteRepositoryInterface` (`EloquentBusinessRepository`) |
| Contact | `contacts` | `App\Models\Contact`, `ContactDTO`, `ContactId`, ports `ContactReadRepositoryInterface` and `ContactWriteRepositoryInterface` (`EloquentContactRepository`) |
| Address | `addresses`, one per business or contact | `Address`, inside `PartyProfile` |
| Bank account | `bank_accounts`, owned by a business or a contact (`AccountOwner`) | `BankAccountDTO`, `BankAccountId`, ports `BankAccountReadRepositoryInterface` and `BankAccountWriteRepositoryInterface` (`EloquentBankAccountRepository`); the account number itself is Payment's `BankAccount` |
| Document party | `supplier_*`, `customer_*` and `account_*` columns of `invoices` and `expenses` | `DocumentParties`, `DocumentPartyDTO`, `DocumentAccountDTO`, `EloquentDocumentPartiesMapper` |

Businesses and contacts share the profile (`PartyProfile`, mapped once by `EloquentPartyProfileMapper`), the address, the bank accounts, the duplicate check (`DuplicateFinder`, `PartyRecords`), archiving instead of deleting, and the capacity limit. The user's default business is `users.default_business_id`; each business and contact has a default bank account.

## Use cases

Commands in `app/Application/Party/Commands/`, queries in `app/Application/Party/Queries/`. Frontend screens are Livewire components in `app/Livewire/Party/` on the `frontend.businesses.*` and `frontend.contacts.*` pages (`Frontend\BusinessController`, `Frontend\ContactController`); the admin uses `Admin\BusinessCrudController`, `Admin\ContactCrudController` and `Admin\BankAccountCrudController`.

| Use case | Frontend | Admin | Access | Limit, events |
|---|---|---|---|---|
| `CreateBusiness` | `BusinessForm` | `BusinessCrudController` | `business.create` with owner | business capacity; `BusinessCreated` |
| `UpdateBusiness` | `BusinessForm` | `BusinessCrudController` | `business.update` with owner | `BusinessUpdated` |
| `ArchiveBusiness`, `RestoreBusiness` | `BusinessDetail` | `BusinessCrudController` | `business.archive`, `business.restore` | restore: business capacity; `BusinessArchived`, `BusinessRestored` |
| `SetDefaultBusiness` | `BusinessDetail` | `BusinessCrudController` | `business.set_default` | `DefaultBusinessChanged` |
| `CreateContact` | `ContactForm` | `ContactCrudController` | `contact.create` with owner | contact capacity; `ContactCreated` |
| `UpdateContact` | `ContactForm` | `ContactCrudController` | `contact.update` with owner | `ContactUpdated` |
| `ArchiveContact`, `RestoreContact` | `ContactDetail` | `ContactCrudController` | `contact.archive`, `contact.restore` | restore: contact capacity; `ContactArchived`, `ContactRestored` |
| Bank account create, update, archive, restore, set default (`CreateBankAccount`, …) | `BankAccounts` | `BankAccountCrudController` | `bank_account.update` | — |
| `GetBusiness`, `GetContact`, `…ForEdit` | the detail and form components | — | `*.view`, `*.update` | — |
| `ListBusinesses`, `ListContacts`, `ListLatestBusinesses`, `ListLatestContacts` | `BusinessList`, `ContactList`, `PartyListLatest` | Backpack's list operation, reading the model | `*.view` | — |
| `ListActiveBusinesses`, `FindDefaultBusiness` | `BusinessDetail`, `BusinessList` | the `BusinessArchive` component | `business.view` | — |
| `FindBusinessDuplicates`, `FindContactDuplicates` | `BusinessForm`, `ContactForm` (`ChecksPartyDuplicates`) | — | `*.view` | — |
| `ListBankAccounts`, `FindDefaultBankAccount`, `GetBankAccountForEdit` | `BankAccounts` | — | `bank_account.*` | — |

"With owner" is `Access::permissionWithOwner()`: an admin writing for an owner needs the owner's permission too (`rules/permissions.md`). Other contexts run these Commands as the owner: User's `Register` creates the first business with `CreateBusiness`; Invoice's `SaveInvoiceCustomerToContactsHandler` and `InvoicePartiesAssembler` create a contact with `CreateContact`.

Not a use case: `CompanyRegistryApplicationService` looks a Czech company up in ARES (`AresCompanyRegistry`) to prefill a form and stores nothing; `PartyLogoApplicationService` stores the logo files of businesses and contacts.

## Archiving and limits

- Businesses, contacts and bank accounts are archived (`archived_at`), never deleted. The handler sets the time and publishes the event (bank accounts publish none).
- Archiving the default business or the default bank account of an owner moves the default first: `ArchiveBusinessHandler` and `ArchiveBankAccountHandler` require an active replacement while other active records remain (`DefaultBusinessRequiredException`, `DefaultBankAccountRequiredException`) and clear the default when none remains; a changed default business also publishes `DefaultBusinessChanged`.
- Businesses and contacts are capacity limits ([USER.md](USER.md#entity-limits)): `CreateBusinessHandler`, `RestoreBusinessHandler`, `CreateContactHandler` and `RestoreContactHandler` call `EntityLimit::assertCanCreate()`. Archiving frees a place without a check. Bank accounts are not limited.
- An archived record stays on the documents that already name it and in their filters and analytics. New selections offer only active records: `ListDocumentBusinesses`, `ListDocumentContacts` and `ListDocumentBankAccounts` (Invoice queries, used by `DocumentPartyPicker` and `Admin\DocumentPartyCrudController`). Choosing an archived record that the document did not already have throws `ArchivedReferenceSelectedException`.

## Parties on documents

A document stores its own copy of both parties and the payment account; a later change of the business or contact never reaches it.

| Step | Code |
|---|---|
| The invoice form's party fields (frontend, guest) | `DocumentPartyPicker`, Blade components in `resources/views/components/party/` |
| The admin document form | `AdminDocumentFormFields`, the lookups of `Admin\DocumentPartyCrudController` |
| Validation of the document party fields | `DocumentPartyInput` (rules shared with `PartyInput`) |
| Copying a business or contact into the document | `DocumentBusinessAssembler`, `DocumentCounterpartyAssembler`, called by `InvoicePartiesAssembler` and `ExpensePartiesAssembler` |
| When the copy is refreshed | the business: on create, when the business changes and when the user reloads it; the customer: stored as entered (`InvoicePartiesAssembler`) |
| Storage | `EloquentDocumentPartiesMapper` (`supplier_*`, `customer_*`, `account_*`) |
| The supplier logo | `DocumentLogoService` copies the file for the document (invoice and expense create and update handlers) |
| Printing | `PartyPrintDTO`, built by `InvoicePrintDataBuilder`, printed by `resources/views/pdfs/templates/{default,minimal,modern}.blade.php` |

Short name, note and contact person are internal and never copied to a document.

## A new field of the party profile

The profile is written once for both entities, so a new field goes through every layer once:

- the `businesses` and `contacts` columns (a migration), the `App\Models\Business` and `App\Models\Contact` attributes, `EloquentPartyProfileMapper`, `PartyProfile`;
- the input: `PartyInput` (rules for the Livewire forms and the admin requests), `PartyDetailsForm` (Livewire), `PartyFormFields` (admin), the Blade components `resources/views/components/party/basic-fields.blade.php` and `resources/views/components/party/basic-profile.blade.php`; `basic-fields` also renders the business part of the registration form (`resources/views/livewire/user/registration-form.blade.php`), so a new field appears there too;
- `lang/*/parties.php` (`parties.fields.*`), `BusinessFactory`, `ContactFactory`, `DevelopmentDataSeeder`.

When documents should carry it too:

- the `supplier_*` and `customer_*` columns of `invoices` and `expenses` (a migration), `App\Models\Expense`, `EloquentDocumentPartiesMapper`, `DocumentPartyDTO`;
- `DocumentPartyInput`, `AdminDocumentFormFields`, `DocumentPartyOptionResource`, the components `resources/views/components/party/document-contact-fields.blade.php` and `resources/views/components/party/document-profile.blade.php`, `resources/views/livewire/party/document-party-picker.blade.php`;
- `database/factories/DocumentParties.php`;
- printed: `PartyPrintDTO` and the three PDF templates, whose output is pinned by snapshot tests.

## Events

The Party command handlers publish `BusinessCreated`, `BusinessUpdated`, `BusinessArchived`, `BusinessRestored`, the same four for contacts, and `DefaultBusinessChanged`, through `EventPublisherInterface`. Analytics' `InvalidateOwnerDashboard` clears the owner's dashboard cache after the commit (`EventServiceProvider`). Bank account use cases publish nothing.
