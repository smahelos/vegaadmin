# Invoice context

Status: maintained map of the code on `main`, last checked 2026-11-26. Start with [the system overview](OVERVIEW.md). The parties an invoice stores are described in [PARTY.md](PARTY.md#parties-on-documents), the entity limit in [USER.md](USER.md#entity-limits), money and invoice invariants in `rules/domain.md`. The planned document life cycle (issuing, numbering at issue, a stored PDF) is step 12 of [TECH_DEBT](../TECH_DEBT.md#step-12-issued-invoices); until then an invoice stays editable.

Invoices the user issues, their lines, the PDF with the QR payment code, the one-off invoice of a guest and the payment reminders. Composition root: `InvoiceServiceProvider`.

## Entities

| Entity | Table | Code |
|---|---|---|
| Invoice | `invoices` | `App\Models\Invoice`, `InvoiceDTO`, `InvoiceId`, `InvoiceWriteData`; ports `InvoiceDtoReadRepositoryInterface` and `InvoiceDtoWriteRepositoryInterface` (`EloquentInvoiceDtoRepository`, mapper `EloquentInvoiceMapper`), the list `InvoiceListReadRepositoryInterface` (`EloquentInvoiceListRepository`) |
| Invoice line | `invoice_products` | `InvoiceProductDTO`, `InvoiceProductWriteData`; written with the invoice by `EloquentInvoiceDtoRepository` (`EloquentInvoiceMapper::toLineAttributes()`) |
| Parties and payment account | `supplier_*`, `customer_*`, `account_*` columns of `invoices` | Party's `DocumentParties` ([PARTY.md](PARTY.md#parties-on-documents)) |
| Guest invoice | none: the cache, 10 minutes, under a random token | `TemporaryInvoiceStore` |

Values: `InvoiceNumber`, `InvoiceDueDate` (`due_in` days from the issue date), `InvoiceTotals`, `InvoiceStatus` (stored as its value in `invoices.payment_status`), `InvoiceTemplate`.

## Use cases

Commands in `app/Application/Invoice/Commands/`, queries in `app/Application/Invoice/Queries/`. The frontend uses `Frontend\InvoiceController` and `Frontend\InvoiceContactController` (`frontend.invoice.*` and `frontend.invoices.*` routes) and the Livewire list in `app/Livewire/Invoice/`; the admin uses `Admin\InvoiceCrudController` and, for the lines, `Admin\InvoiceItemsController`.

| Use case | Frontend | Admin | Access | Limit, rate limit |
|---|---|---|---|---|
| `CreateInvoice` | `store` (`allowed`, `throttle:create`) | `store` | `invoice.create` with owner | invoices per month |
| `UpdateInvoice` | `update` (`allowed`) | `update` | `invoice.update` with owner | — |
| `MarkInvoicePaid` | `markAsPaid` | — | `invoice.update` | — |
| `SetInvoiceTemplate` | `setTemplate` (`allowed`) | — | `invoice.update` | — |
| `SaveInvoiceCustomerToContacts`, `LinkInvoiceContact` | `InvoiceContactController` | — | `invoice.update` | save: `throttle:create` and Party's contact capacity |
| `GetInvoiceCreateForm`, `GetInvoiceForEdit` | `create`, `edit` | Backpack's create and update forms | `invoice.create`, `invoice.update` | — |
| `GetInvoice`, `GetInvoicePdf` | `show`, `download` | — | `invoice.view` | — |
| `ListInvoices`, `ListInvoiceFilters` | `InvoiceList` | Backpack's list operation, reading the model | `invoice.view` | — |
| `ListInvoiceCatalogProducts`, `PreviewInvoiceTotals` | the invoice form | `InvoiceItemsController` | any of `invoice.create`, `invoice.update` and the expense pair | — |
| `ListDocument*`, `GetDocument*`, `FindDefaultDocument*`, `FindDocumentContactDuplicates` | `DocumentPartyPicker` | `Admin\DocumentPartyCrudController` | the same `anyOf` | — |
| `CreateGuestInvoice`, `GetGuestInvoicePdf`, `DeleteGuestInvoice` | `storeGuest`, `downloadWithToken`, `destroyGuest` | — | public | `throttle:guest_invoice` |
| `SendInvoiceReminders`, `SendInvoiceReminder` | — | — | system | — |

The pages get typed data: `GetInvoice` returns `DetailInvoiceDTO` (the invoice with the payment method, the linked contact and a contact the customer could be linked to), formatted in the view through `InvoiceDetailPresenter`; `GetInvoiceCreateForm` returns `InvoiceFormDTO` and `GetInvoiceForEdit` returns `EditableInvoiceDTO` (the form with the stored `InvoiceDTO`); `InvoiceFormPresenter` turns the invoice into the form values and the item editor's lines (`EditableInvoiceItemDTO`, serialized with the request keys of a line).

"With owner" is `Access::permissionWithOwner()` (`rules/permissions.md`). `CreateInvoiceHandler` calls `EntityLimit::assertCanCreate()` for `LimitedEntity::INVOICE` inside the transaction; the frontend form shows the usage (`x-entity-usage`). The rate limiters are registered in `RouteServiceProvider`.

## Creating and updating an invoice

| Step | Code |
|---|---|
| Form fields | `InvoiceFormFields` (shared trait), frontend `InvoiceCreateFieldSetFactory` through `GetInvoiceCreateForm`, guest `GuestInvoiceCreateForm`, admin `AdminInvoiceFormFields` and `AdminInvoiceItemFields`; views `resources/views/frontend/invoices/create.blade.php` (also the guest form) and `resources/views/frontend/invoices/edit.blade.php` |
| Lines in the browser | `resources/js/invoice-item-manager.js`, `resources/js/product-selector.js`, admin `resources/js/admin/invoice-items.js` with `resources/views/admin/fields/invoice_items.blade.php` |
| Validation | `InvoiceRequest` (frontend and guest), `Admin\InvoiceRequest`; both build on `InvoiceWriteDataInput` (invoice fields) and `InvoiceItemInput` (lines), the parties on `DocumentPartyInput` (admin also `AdminDocumentPartyInput`) |
| Request to Command | frontend: `InvoiceRequestAssembler` into `InvoiceMutationPayload`; admin: `Admin\InvoiceRequest` itself (`owner()`, `toWriteData()`, `partySelection()`, `products()`); both give `InvoiceWriteData`, `InvoicePartySelection` and the lines from `InvoiceProductWriteDataAssembler` |
| Handler | `CreateInvoiceHandler`, `UpdateInvoiceHandler`: `InvoiceProductOwnership` refuses another user's products, `InvoicePartiesAssembler` copies the parties, `DocumentLogoService` copies the supplier logo |
| Storage | `InvoiceService` computes the total from the lines (`InvoiceTotals`); `EloquentInvoiceDtoRepository::create()` and `update()` write the invoice with its lines and return it with them; an update replaces all lines |

The invoice number is also the variable symbol of the QR payment (`invoice_vs`). `GetInvoiceCreateForm` suggests the next number of the year (`InvoiceService::generateInvoiceNumber()`, `InvoiceNumber::generateNext()`); the user can change it, and `InvoiceWriteDataInput` accepts only the `InvoiceNumber` format. Nothing refuses a number already used: "Invoice numbers are not unique per user and year" and "Invoice numbering ignores the requested year" in [TECH_DEBT](../TECH_DEBT.md).

The guest invoice is never stored in the database: `CreateGuestInvoiceHandler` puts the validated input in `TemporaryInvoiceStore` and returns the token and the QR code; `GetGuestInvoicePdf` prints it through `TemporaryInvoicePrintDataFactory`.

## Status, payment and reminders

- `MarkInvoicePaid` sets `InvoiceStatus::PAID` with a query update (`EloquentInvoiceDtoRepository::setStatus()`); the form sets any status through `UpdateInvoice`.
- `PENDING` is unpaid (`InvoiceStatus::isUnpaid()`); only those invoices get reminders. `CANCELLED` stays until drafts and issuing replace it (D-050). Partial payments cannot be recorded: "Invoices have no payment records" in [TECH_DEBT](../TECH_DEBT.md).
- Labels are `invoices.statuses.*` (with `overdue` for the derived state); `InvoiceStatusPresenter::color()` gives the colour of a status in the lists and on the detail page. The dashboard chart keeps its own palette in `resources/js/dashboard-statistics.js`.
- `invoices:check-payment-status` (`CheckInvoicePaymentStatus`, a cron task managed in the admin) runs `SendInvoiceReminders`: for every unpaid invoice of all users (`EloquentInvoiceReminderRepository::unpaidForReminders()`) it sends one reminder on the day `--days-before` the due date, on the due date and on the day `--days-after` it. `InvoiceReminderSender` mails the supplier and the customer that have an email address, in the language of their country, through `LaravelInvoiceReminderNotifier`. `invoices:test-reminder` (`TestInvoiceReminder`) sends one with `SendInvoiceReminder`.
- Reminders stop when the status is no longer unpaid, or after the overdue reminder.

## PDF

`GetInvoicePdf` reads the stored invoice, `InvoicePrintDataBuilder` turns it into `PrintableInvoiceDTO` (parties as Party's `PartyPrintDTO`), and `InvoicePdfRenderer` adds the QR code (Payment's `QrPaymentServiceInterface`, `QrPaymentMapper`) and renders the view the invoice's `InvoiceTemplate` names, `resources/views/pdfs/templates/{default,minimal,modern}.blade.php`, with dompdf. The PDF is rendered on every download, never stored. The output is pinned by `tests/Feature/Domain/Invoice/Services/InvoicePdfSnapshotTest.php`.

A new field printed on the PDF goes through `InvoiceWriteData`, `InvoiceDTO`, `EloquentInvoiceMapper`, `InvoiceWriteDataInput`, the form fields above, `PrintableInvoiceDTO` with `InvoicePrintDataBuilder`, `TemporaryInvoicePrintDataFactory` for the guest invoice, the three templates and their snapshots, `lang/*/invoices.php` (`invoices.fields.*`) and a migration of `invoices`.

## Events

No invoice use case publishes a domain event. `InvoiceObserver` publishes `UserDataChanged` when an invoice is created or deleted, or its amount, status, issue date or due days change through the model; `InvalidateUserCache` then clears the dashboard cache. `MarkInvoicePaid` writes with a query, so the observer does not see it. Analytics reads `invoices` directly (`EloquentAnalyticsRepository`, `EloquentApiStatisticsRepository`). The observer is "Move cache invalidation from observers to domain event handlers" in [TECH_DEBT](../TECH_DEBT.md).
