# Invoice context

Status: maintained map of the code on `main`, last checked 2026-11-27. Start with [the system overview](OVERVIEW.md). The parties an invoice stores are described in [PARTY.md](PARTY.md#parties-on-documents), the entity limit in [USER.md](USER.md#entity-limits), money and invoice invariants in `rules/domain.md`. The document life cycle is [D-050](../adr/D-050-issued-documents.md): a draft is edited freely, issuing numbers and locks it. The stored PDF, reminders and statistics of issued invoices and corrective documents are the rest of step 12 of [TECH_DEBT](../TECH_DEBT.md#step-12-issued-invoices).

Invoices the user issues, their lines, the PDF with the QR payment code, the one-off invoice of a guest and the payment reminders. Composition root: `InvoiceServiceProvider`.

## Entities

| Entity | Table | Code |
|---|---|---|
| Invoice | `invoices` | `App\Models\Invoice`, `InvoiceDTO` (with `InvoiceIssue` once issued), `InvoiceId`, `InvoiceWriteData`; ports `InvoiceDtoReadRepositoryInterface` and `InvoiceDtoWriteRepositoryInterface` (`EloquentInvoiceDtoRepository`, mapper `EloquentInvoiceMapper`), the list `InvoiceListReadRepositoryInterface` (`EloquentInvoiceListRepository`) |
| Invoice line | `invoice_products` (price, quantity and rate; no VAT of its own) | `InvoiceProductDTO`, `InvoiceProductWriteData`; written with the invoice by `EloquentInvoiceDtoRepository` (`EloquentInvoiceMapper::toLineAttributes()`) |
| VAT line of an issued payer's invoice | `vat_lines` (rate, base, VAT; written at issue) | `App\Models\VatLine`, `VatLine` of `InvoiceTotals`; `EloquentInvoiceMapper::toVatLineAttributes()` |
| Parties and payment account | `supplier_*`, `customer_*`, `account_*` columns of `invoices` | Party's `DocumentParties` ([PARTY.md](PARTY.md#parties-on-documents)) |
| Guest invoice | none: the cache, 10 minutes, under a random token | `TemporaryInvoiceStore` |

Values: `InvoiceNumber`, `InvoiceDueDate` (`due_in` days from the issue date), `InvoiceTotals` with its `VatLine`s (base and VAT of one rate, rounded per rate as `rules/domain.md` says; `InvoiceTotals::fromVatLines()` for an issued payer's invoice), `InvoiceStatus` (stored as its value in `invoices.payment_status`; a draft stores `PENDING` and shows none), `InvoiceTemplate`, the document language (Shared's `Language`, `invoices.document_language`).

## Use cases

Commands in `app/Application/Invoice/Commands/`, queries in `app/Application/Invoice/Queries/`. The frontend uses `Frontend\InvoiceController` and `Frontend\InvoiceContactController` (`frontend.invoice.*` and `frontend.invoices.*` routes) the Livewire list and the issue dialog in `app/Livewire/Invoice/`; the admin shows invoices read-only through `Admin\InvoiceCrudController` (List and Show, reading the model).

| Use case | Frontend | Admin | Access | Limit, rate limit |
|---|---|---|---|---|
| `CreateInvoice` | `store` (`allowed`, `throttle:create`) | — | `invoice.create` | — |
| `UpdateInvoice` | `update` (`allowed`) | — | `invoice.update` | — |
| `IssueInvoice`, `GetIssueInvoiceForm` | `IssueInvoiceDialog` | — | `invoice.update` | issue: invoices per month |
| `DeleteInvoice` | `destroy` | — | `invoice.delete` | — |
| `CopyInvoice` | `copy` (`throttle:create`) | — | `invoice.create` | — |
| `SetInvoiceStatus` | `setStatus` | — | `invoice.update` | — |
| `SetInvoiceTemplate` | `setTemplate` (`allowed`) | — | `invoice.update` | — |
| `SaveInvoiceCustomerToContacts`, `LinkInvoiceContact` | `InvoiceContactController` | — | `invoice.update` | save: `throttle:create` and Party's contact capacity |
| `GetInvoiceCreateForm`, `GetInvoiceForEdit` | `create`, `edit` | — | `invoice.create`, `invoice.update` | — |
| `GetInvoice`, `GetInvoicePdf` | `show`, `download` | — | `invoice.view` | — |
| `ListInvoices`, `ListInvoiceFilters` | `InvoiceList` | Backpack's list operation, reading the model | `invoice.view` | — |
| `ListDocument*`, `GetDocument*`, `FindDefaultDocument*`, `FindDocumentContactDuplicates` | `DocumentPartyPicker` | `Admin\DocumentPartyCrudController` | any of `invoice.create`, `invoice.update` and the expense pair | — |
| `CreateGuestInvoice`, `GetGuestInvoicePdf`, `DeleteGuestInvoice` | `storeGuest`, `downloadWithToken`, `destroyGuest` | — | public | `throttle:guest_invoice` |
| `SendInvoiceReminders`, `SendInvoiceReminder` | — | — | system | — |

The pages get typed data: `GetInvoice` returns `DetailInvoiceDTO` (the invoice with the payment method, the linked contact, the VAT payer flag that applies to it and, for a draft, a contact the customer could be linked to), formatted in the view through `InvoiceDetailPresenter`; `GetInvoiceCreateForm` returns `InvoiceFormDTO` and `GetInvoiceForEdit` returns `EditableInvoiceDTO` (the form with the stored `InvoiceDTO`); `InvoiceFormPresenter` turns the invoice into the form values and the item editor's lines (`EditableInvoiceItemDTO`, serialized with the request keys of a line).

The monthly quota counts issued invoices: `IssueInvoiceHandler` calls `EntityLimit::assertCanCreate()` for `LimitedEntity::INVOICE` inside the transaction, `EloquentLimitedEntityCounter` counts `issued_at`, and the issue dialog and the list show the usage (`x-entity-usage`). The rate limiters are registered in `RouteServiceProvider`.

## Creating and updating a draft

| Step | Code |
|---|---|
| Form fields | `InvoiceFormFields` (shared trait), frontend `InvoiceCreateFieldSetFactory` through `GetInvoiceCreateForm` (document language prefilled from the default business), guest `GuestInvoiceCreateForm` (number and dates, which only the guest types); views `resources/views/frontend/invoices/create.blade.php` (also the guest form) and `resources/views/frontend/invoices/edit.blade.php` |
| Lines in the browser | `resources/js/invoice-item-manager.js`, `resources/js/product-selector.js` |
| Validation | `InvoiceRequest` (frontend and guest; `InvoiceWriteDataInput::guestRules()` adds the guest's number and dates), built on `InvoiceWriteDataInput` (invoice fields) and `InvoiceItemInput` (lines), the parties on `DocumentPartyInput` |
| Request to Command | `InvoiceRequestAssembler` into `InvoiceMutationPayload`: `InvoiceWriteData`, `InvoicePartySelection` and the lines from `InvoiceProductWriteDataAssembler` |
| Handler | `CreateInvoiceHandler`, `UpdateInvoiceHandler`: `InvoiceProductOwnership` refuses another user's products, `InvoicePartiesAssembler` copies the parties, `DocumentLogoService` copies the supplier logo |
| Storage | `InvoiceService` refuses a change of an issued invoice (`InvoiceIssuedException`; `GetInvoiceForEdit` throws it too and the controller redirects to the detail) and computes the totals by rate from the lines with the current VAT payer flag of the business (`InvoiceTotals::forSupplier()`; a non-payer's non-zero rate throws `NonPayerVatRateException`, which `ExceptionHandling` shows as a validation error of `invoice-products`); `EloquentInvoiceDtoRepository::create()` and `update()` write the invoice with its lines and return it with them; an update replaces all lines |

## Issuing, deleting and copying

A draft has no number, issue date or tax point date (`invoice_vs`, `issue_date`, `tax_point_date` are NULL, `issued_at` too) and shows its due as days after issue. The detail page (`resources/views/frontend/invoices/show.blade.php`) offers a draft the issue dialog, editing, the template, the contacts and deleting; an issued invoice the payment status (paid or unpaid) and a copy. The issue dialog is the Livewire component `IssueInvoiceDialog`, one per page (the detail and the invoice list), opened by the browser event `open-issue-dialog` with the invoice id from the detail's button or a draft's row in the list. "Save and issue" in the edit form submits `issue=1`; `InvoiceController::update` then redirects to the detail with `?issue=1`, which mounts the dialog open.

- `GetIssueInvoiceForm` gives the dialog its defaults (`IssueInvoiceFormDTO`: the next number of the current year, today) and refuses an issued invoice; the dialog validates with `InvoiceWriteDataInput::numberRules()`. `IssueInvoice` runs `InvoiceService::issueInvoice()`: the number must be of the issue date's year (`InvoiceNumberYearException`), an empty tax point date is the issue date, and `EloquentInvoiceDtoRepository::issue()` stores the number, the dates, `issued_at`, `issued_by`, the business's VAT payer flag and register entry of that moment (`is_vat_payer`, `register_entry`) and, for a payer, the VAT lines. The unique index `(user_id, invoice_vs)` refuses a number another invoice of the owner has (`InvoiceNumberTakenException`). Both exceptions carry the next free number of the year (`InvoiceService::nextNumber()`, `InvoiceNumber::generateNext()`), which the dialog shows in the error of the number field; the quota error is shown in the dialog too.
- The invoice number is also the variable symbol of the QR payment; `InvoiceWriteDataInput::numberRules()` accepts only the `InvoiceNumber` format. A guest invoice gets the first number of the clock's year.
- `DeleteInvoice` deletes a draft with its lines and removes its supplier logo copy; an issued invoice cannot be deleted.
- `CopyInvoice` creates a draft with the parties, lines and settings of any invoice of the owner, with the business's current VAT payer flag.
- `SetInvoiceStatus` changes the payment status of an issued invoice only (`InvoiceNotIssuedException` for a draft).

The forms show the base of each line and the summary by rate below the lines: the frontend form computes it in `resources/js/invoice-item-manager.js` with the server's algorithm (`x-invoice-vat-summary`). For a non-payer the rate column is hidden and set to 0, the name column takes its width on large screens and the line base is labelled as the total; the frontend form reads the flag from `#supplier-vat-payer` in `resources/views/livewire/party/document-party-picker.blade.php` (the selected business's flag, or the guest's checkbox); the same element carries the business's document language, which the form takes when another business is picked. The detail page shows the rate column and the VAT rows only for a payer: the stored flag of an issued invoice, the business's current one for a draft.

The guest invoice is never stored in the database: `CreateGuestInvoiceHandler` puts the validated input with the guest's VAT payer checkbox (`is_vat_payer`, unchecked by default) in `TemporaryInvoiceStore` and returns the token and the QR code; `GetGuestInvoicePdf` prints it through `TemporaryInvoicePrintDataFactory`.

## Status, payment and reminders

- `SetInvoiceStatus` sets the status with a query update (`EloquentInvoiceDtoRepository::setStatus()`); the forms do not set it.
- `PENDING` is unpaid (`InvoiceStatus::isUnpaid()`); only issued unpaid invoices get reminders. The list labels a draft `invoices.statuses.draft` and its status filter shows only issued invoices. Partial payments cannot be recorded: "Invoices have no payment records" in [TECH_DEBT](../TECH_DEBT.md).
- Labels are `invoices.statuses.*` (with `overdue` for the derived state); `InvoiceStatusPresenter::color()` gives the colour of a status in the lists and on the detail page. The dashboard chart keeps its own palette in `resources/js/dashboard-statistics.js`.
- `invoices:check-payment-status` (`CheckInvoicePaymentStatus`, a cron task managed in the admin) runs `SendInvoiceReminders`: for every issued unpaid invoice of all users (`EloquentInvoiceReminderRepository::unpaidForReminders()`) it sends one reminder on the day `--days-before` the due date, on the due date and on the day `--days-after` it. `InvoiceReminderSender` mails the supplier and the customer that have an email address, in the language of their country, through `LaravelInvoiceReminderNotifier`. `invoices:test-reminder` (`TestInvoiceReminder`) sends one with `SendInvoiceReminder`.
- Reminders stop when the status is no longer unpaid, or after the overdue reminder.

## PDF

`GetInvoicePdf` reads the stored invoice and its business's invoicing settings, `InvoicePrintDataBuilder` turns them into `PrintableInvoiceDTO` (parties as Party's `PartyPrintDTO`), and `InvoicePdfRenderer` adds the QR code (Payment's `QrPaymentServiceInterface`, `QrPaymentMapper`) and renders the view the invoice's `InvoiceTemplate` names, `resources/views/pdfs/templates/{default,minimal,modern}.blade.php`, with dompdf. A payer's PDF prints the rate and base of each line, the total and the summary by rate (base, VAT, total); a non-payer's prints neither rates nor VAT, labels the line amount as the total and states that the supplier is not a VAT payer (`invoices.messages.not_vat_payer`). An issued invoice prints the flag and register entry copied at issue and the stored VAT lines; a draft prints the business's current ones, the title `invoices.titles.draft_preview`, its due as days after issue and no number, dates or QR code. The PDF is in the invoice's document language, whoever downloads it. Amounts are written with the currency after the number and the separators of the document language (`MoneyFormatterInterface::formatWithCurrencyAfter()`, "12 346,00 Kč", "500,00 €"); the web pages keep the currency before the amount. A line prints its base rounded on its own, the rate and the total round the unrounded sum once, so in rare cases the line column does not add up to the total. The PDF is rendered on every download, never stored. The output is pinned by `tests/Feature/Domain/Invoice/Services/InvoicePdfSnapshotTest.php`.

A new field printed on the PDF goes through `InvoiceWriteData` (or `InvoiceIssue` for a value fixed at issue), `InvoiceDTO`, `EloquentInvoiceMapper`, `InvoiceWriteDataInput`, the form fields above, `PrintableInvoiceDTO` with `InvoicePrintDataBuilder`, `TemporaryInvoicePrintDataFactory` for the guest invoice, the three templates and their snapshots, `lang/*/invoices.php` (`invoices.fields.*`) and a migration of `invoices`.

## Events

No invoice use case publishes a domain event. `InvoiceObserver` publishes `UserDataChanged` when an invoice is created or deleted, or its amount, status, issue date or due days change through the model (issuing does); `InvalidateUserCache` then clears the dashboard cache. `SetInvoiceStatus` and `DeleteInvoice` write with a query, so the observer does not see them. Analytics reads `invoices` directly (`EloquentAnalyticsRepository`, `EloquentApiStatisticsRepository`). The observer is "Move cache invalidation from observers to domain event handlers" in [TECH_DEBT](../TECH_DEBT.md).
