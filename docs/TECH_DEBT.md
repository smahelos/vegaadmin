# Technical Debt

Known gaps between the code and the intended architecture (see `CLAUDE.md`, `docs/adr/`). Most items are unfinished steps of the DDD migration (commits `a65aaffb` 2025-09-09 and `7e999b2a` 2025-10-12 in the original Bitbucket history): the target class exists, but the code was never switched over to it. Remove an item once it is resolved.

## Order of work

Decided 2026-10-23: most important first (money, law, data exposure), but an item that others build on goes before them, and work that touches the same files is done together. Items not listed are picked up by the branch that touches their code.

**A. Foundations**

1. ~~Tooling — Pint over the whole codebase in one commit, Larastan in CI, before the large rewrites so that their diffs stay clean.~~ Done 2026-10-23 (branch `ls-tooling`): Pint and PHP_CodeSniffer over the whole codebase, Larastan at level 1, all three in CI; raising the level is "Larastan level" below.
2. ~~Security — "Public statistics endpoints expose all users' revenue", "State changes over GET", "Sorting from the URL without an allowlist", "Middleware registered twice", "Exception messages shown to users".~~ Done 2026-10-24 (branch `ls-security`); found on the way: "Cron tasks are not fully gated by permissions", "Admin access middleware logs every request".
3. ~~"Run tests on MySQL instead of in-memory SQLite" with the squashed migrations, before the invoice work adds new migrations.~~ Done 2026-10-25 (branch `ls-mysql-tests`): migrations squashed by context, reference data in `ReferenceDataSeeder`, the suite runs on MySQL locally and in CI.
4. ~~"Domain events are published inside the transaction" with "No queue worker" — the issued-invoice work publishes its events on top of it; both deliver work outside the request, and the queue's `after_commit` is set there.~~ Done 2026-10-25 (branch `ls-events-after-commit`): domain handlers (database writes) run inside the transaction, Laravel listeners (cache) after the commit with their failures logged, queued jobs are stored in the transaction on the `database` connection (`rules/architecture.md`, Events); a `queue` container runs the worker.
5. ~~"Test runs and CI are slower than they need to be" with the `tests/Unit` part of "Tests that pin structure instead of behaviour" — every later branch runs the suite many times.~~ Done 2026-10-25 (branches `ls-test-speed`, `ls-unit-tests`): no more migrating again after tests that left their transaction, no Basset downloads; `tests/Unit` holds only behaviour tests without Laravel and runs as its own CI job without MySQL. `schema:dump` was deferred, see "Test runs and CI are slower than they need to be".

**B. Invoices**

6. ~~What an issued invoice freezes must be right first: "Modern and minimal PDFs always print …", "VAT rates are typed into the database by hand", "Calculate due dates through `InvoiceDueDate`", "Statuses are looked up by string slugs and labelled in English".~~ Done 2026-10-25 (branch `ls-invoice-print`): the PDFs print the stored tax point date, invoice text and payment method (`PaymentMethodCode`) in the PDF language, the tax point date is required, due dates go through `InvoiceDueDate`, PDF dates through `DateFormatterInterface`; invoice statuses are the `InvoiceStatus` enum and overdue is derived from the due date, which also made the payment reminders work again. Left over: "VAT rates by the supplier's country", "Status labels are translated in the model"; found on the way: "Dates outside the PDFs are formatted by hand", "Payment reminders query Eloquent in the console command"; the admin form could not save an invoice (its request required a field the form does not send), fixed on the branch.
7. "Clients and suppliers mix the user's businesses with their contacts" and "Optional client and supplier fields cannot be cleared" with "Rename IČO and DIČ to `company_number` and `vat_number`", "Build `PartyPrintDTO` for the parties of the print data", "Build `BankAccount` and validate bank accounts through it" and the print DTO names from "Data class names and properties". Scope refined 2026-10-26 in `docs/PRD_BUSINESSES_AND_CONTACTS.md`: separate addresses, multiple bank accounts with defaults, one-off counterparties and stored party/account data on documents, business/contact archiving and restore, active-count limits and a shared user product catalogue. PDF snapshots are regenerated once. Minimal current-expense adaptation to business plus contact or one-off party is included; full received-document types, statuses and VAT remain step 8. This is planned work, not completed work.
8. "Issued invoices can be changed and deleted", with "Validate the variable symbol in QR payments" (numbering at issue is decided there) and the product part of "Clients, suppliers and products cannot be archived"; then "Expenses are not modelled as received documents", which shares the VAT lines, the VAT payer flag on Business and the document tables (`docs/adr/D-044-document-tables.md`). Step 7 already stores document party/account data; step 8 freezes the complete document at issue and stores its PDF.
9. "Currency conversion of invoice items happens in the browser" (ČNB rates) with "Resilience of external calls".

**C. Payments and subscription plans**

10. "Permission naming" — the plans grant permissions.
11. Payments: "Payment notification route", "GoPay is called inside the database transaction", "Typed GoPay request with real customer data", "Subscription activation ignores the plan's duration", "Build refunds", "Payment adapter guard checks nothing", "UELS dashboard bulk reset fails".
12. The subscription plans, with "Subscription screens with placeholder content".

**D. Conversion to the standard, one context at a time** (`/convert-to-standard`)

13. Invoice, Party, Product, Payment, User, Analytics, Content — each with the items of "Conversion to the standard" that apply to it and the context's own items (Product: unit field); "Move cache invalidation from observers to domain event handlers" after step 4.
14. "Frontend", then "API responses" (the Livewire forms remove endpoints), then "Route names and URLs" (fewest references left).

**E. Before production and last**

15. "`backpack_pro()` override claims a PRO licence" — a decision before production.
16. "No error monitoring" — before the first deploy.
17. "Erasing a user account" — before the first real user; needs the retention rules of step 8.
18. The remaining "Party / Admin" items.
19. "Architecture guards are migration scaffolding" with "`ddd:guard` snapshot test fails now and then in parallel runs".

## Security

### Payment notification route
- **Where:** `routes/web.php` (`POST /payment/notify` in the `web` group), `app/Http/Controllers/Frontend/PaymentController.php::notify()`
- **Problem:** the route is not excluded from CSRF, so an external notification most likely ends with 419 (to verify); the whole request payload is logged. GoPay's documentation describes the notification as an HTTP GET on `notification_url` with the payment `id`, so a POST-only route may not match at all.
- **Fix:** check what GoPay sends, then a route for that verb excluded from CSRF (`validateCsrfTokens(except: …)`), gateway-verified handling as in `rules/payments.md`, log only the payment id; a test that the notification passes without a CSRF token.

### Cron tasks are not fully gated by permissions
- **Where:** `app/Http/Controllers/Admin/CronTaskCrudController.php` (no `allowAccess()` / `denyAccess()`), `app/Models/CronTask.php::simulateRun()`
- **Problem:** saving and running a task check `can_create_edit_cron_task`, but every admin user, `backend_user` without permissions included, can list, show and delete tasks. A task's command is an Artisan command or any `Class::method`, resolved from the container and called. `simulateRun()` swallows exceptions and returns `false`.
- **Fix:** gate every CRUD operation by its `can_*_cron_task` permission (`permissions.md`, with "Permission naming"), allow only scheduled Artisan commands from a list, move running into an application service that throws on failure.

### Admin access middleware logs every request
- **Where:** `app/Http/Middleware/RequireBackpackAccess.php`
- **Problem:** every admin request writes an `info` log entry with the user's e-mail, roles and permissions.
- **Fix:** drop the debug log; log only a denied access, with the user id.

## Invoice

### Issued invoices can be changed and deleted
- **Where:** `InvoiceCrudApplicationService` (update, delete), frontend `InvoiceController` (edit, update, set-template, delete), `Admin\InvoiceCrudController` and `Admin\InvoiceRequest`, `InvoicePrintDataBuilder` (reads the current client and supplier), `InvoicePdfRenderer`, `app/Console/Commands/DatabaseArchiveCommand.php` and `archive_policies` (36 months), `Money` (no negative amounts)
- **Problem:** an issued invoice is a tax document kept unchanged for 10 years (§ 34 and § 35 ZDPH; corrections through a corrective tax document, § 42 and § 45 ZDPH; § 35 ZÚ; to confirm with an accountant after the 2025 amendment). Today an invoice has no draft/issued state (`draft` is a payment status), can be edited in the frontend and the admin and is deleted for good (no `deleted_at`); the admin edits `payment_amount` without the lines, so the amount can disagree with them. It stores only `client_id` and `supplier_id`, so a later change of the party changes every old invoice; the PDF is rendered again from the current template and logo on every download. `db:archive` moves invoices and items older than 36 months out of the main tables and deletes clients and suppliers by the same policy.
- **Fix (decided 2026-10-23, own branch, step 8 of the order of work):** a document life cycle separate from the payment status (draft → issued → cancelled by a corrective document); issuing assigns the number, stores a snapshot of the parties, bank account, lines, totals, currency, payment method, template and logo, and stores the rendered PDF with its hash, which later downloads return. The Domain refuses changes to an issued invoice; only its payment status changes. Corrections and cancellations are corrective tax documents (credit or debit note) linked to the invoice, with their own PDF; a cancellation can copy the invoice into a new draft. The admin shows the amount read-only. Invoices, corrective documents and their PDFs are kept for 10 years while the account exists (archiving them is the user's duty; "Erasing a user account"); who issued or cancelled a document and when is recorded. Open decisions: numbering at issue, a number series for corrective documents, negative `Money` or a sign by document type, existing invoices, VAT payers vs non-payers; deleting drafts (the uncalled `delete()`, `getForEdit()`, `softDelete()` and `restore()` of `InvoiceCrudApplicationService` were deleted in commit "Delete unused invoice code", branch `ls-dead-code`). Decided 2026-10-26 for step 7's party work: step 7 stores the party and account data on the invoice already; an issued invoice changes nothing, not even its `contact_id`, so "Save to contacts" and "Link to an existing contact" (step 7 offers them on every invoice without a contact) stay only on drafts; `db:archive` never deletes businesses or contacts (step 7 removes the `clients` / `suppliers` archive policies and adds none).

### Invoices cannot be sent by email
- **Where:** frontend invoice detail; only `app/Console/Commands/CheckInvoicePaymentStatus.php` sends invoice email (payment reminders)
- **Problem:** the user downloads the PDF and sends it themselves. The party PRD (`docs/PRD_BUSINESSES_AND_CONTACTS.md`, rule 10.3) already decides how the recipient works, but there is no sending to apply it to.
- **Fix:** a "send by email" use case that sends the stored PDF to the invoice's `customer_email`; a recipient entered while sending is not saved to the contact unless the user explicitly chooses to and may update the contact.

### Expenses are not modelled as received documents
- **Where:** `expenses` (`2026_10_25_000009_create_expense_tables.php`), `App\Models\Expense`, `ExpenseRequest` (frontend and admin), `Admin\ExpenseCrudController`, expense statuses in `ReferenceDataSeeder`
- **Problem:** Billex is for VAT payers and non-payers alike, but an expense is one amount with an optional `tax_amount` and a `tax_included` flag. It has no document type, no VAT breakdown by rate, no taxable supply date (DUZP) or date of receipt (which decides the VAT deduction), no supplier's document number and no due date; its statuses (draft, in review, approved, rejected…) are an approval process for companies, not the payment state a sole trader needs. A document the user claims a VAT deduction from is kept for 10 years (§ 35 ZDPH).
- **Fix (decided 2026-10-25, after "Issued invoices can be changed and deleted"):** an expense is a received document, stored as `docs/adr/D-044-document-tables.md` describes: a document type enum (received invoice and simplified tax document / receipt first; corrective document, advance invoice and the tax document for a received payment later as new cases), a VAT regime enum (standard first; reverse charge and acquisition from the EU later), VAT lines by rate shared with issued documents (a non-payer enters only the total), supplier's document number, issue date, DUZP, date of receipt, due date, attachment; a payment state (unpaid, paid) instead of the approval statuses; a VAT payer flag on Business (location decided 2026-10-26 in the party PRD, not on the login account). The user still deletes an expense: it is their record of someone else's document. Every table or column only one document type needs is decided by the user (criteria in the ADR). Minimal current-expense party adaptation in step 7 is approved in `docs/PRD_BUSINESSES_AND_CONTACTS.md`: own business plus contact or one-off supplier, stored party/account data and matching forms/filters. Existing statuses and calculations stay until this full document model is delivered.

### Status labels are translated in the model
- **Where:** `App\Models\Status::getTranslatedNameAttribute()`
- **Problem:** invoice statuses are the `InvoiceStatus` enum, expense statuses plain slugs in the `statuses` table: the expense counterpart `StatusCode` and the translation in `StatusService` had no caller and were deleted (branch `ls-dead-code`). The model accessor translates the slug itself, with a fallback to the stored name.
- **Fix:** labels only from `statuses.statuses.<slug>` (every case has the key), resolved by one presenter used by the invoice mapper and the dashboard instead of the model. The expense statuses change with "Expenses are not modelled as received documents".

### Build `PartyPrintDTO` for the parties of the print data
- **Where:** `app/Domain/Invoice/DTO/PrintableInvoiceData.php`
- **Problem:** `PrintableInvoiceData` has ~40 flat `supplier_*` / `client_*` fields. A `PartyPrintData` drafted to hold one party was never used and was deleted (commit "Delete unused party code", branch `ls-dead-code`).
- **Fix:** build `PartyPrintDTO` for one party, then `supplier: PartyPrintDTO`, `customer: PartyPrintDTO`; update `InvoicePrintDataBuilder`, `TemporaryInvoicePrintDataFactory`, `InvoicePdfRenderer`, the PDF templates and snapshots.

### Currency conversion of invoice items happens in the browser
- **Where:** `resources/js/currency-manager.js` (`getExchangeRate()`, `convertAmount()`), `resources/js/invoice-item-manager.js`, `app/Domain/Shared/Money/Services/CurrencyExchangeService.php`, `InvoiceRequestAssembler`, `/api/currencies/{exchange-rate,convert}`
- **Problem:** the invoice form converts item prices in JavaScript with floats; the server checks only that every item is in the invoice currency (`InvoiceRequest`), stores the converted price as it arrives and keeps no rate or date with the invoice. The form also previews line and invoice totals in JavaScript floats; the stored amounts are computed on the server (`InvoiceTotals`). Rates come from the free ExchangeRate-API (`open.er-api.com`); when it fails, the service silently returns rates hardcoded in the class (EUR→CZK 25.29 against 24.27 on 2026-10-23), and when a rate is missing the browser uses 1:1. VAT in CZK on a foreign-currency invoice needs the ČNB rate (§ 4 odst. 5 ZDPH; to confirm with an accountant). `Money::convertTo()` exists but nothing calls it.
- **Fix (decided 2026-10-23, own branch after the issued-invoice immutability):** ČNB daily rates behind an exchange rate port; conversion on the server through `Money::convertTo()` when the invoice is stored; the rate, its date and source stored with the invoice and printed on it; an unavailable rate is an error shown to the user, never a fallback table or 1:1; `/api/currencies/convert` becomes the form's conversion or is deleted.

### VAT rates by the supplier's country
- **Where:** `ReferenceDataSeeder::VAT_RATES`, `InvoiceRequest` (allowed item rates), invoice and product forms
- **Problem:** the reference data creates the Czech rates (0, 12 and 21 %) for every supplier; a supplier from another country would need its own rates.
- **Fix:** decide with an accountant whether the rates depend on the supplier's country; until then Czech rates only.

### Payment reminders query Eloquent in the console command
- **Where:** `app/Console/Commands/CheckInvoicePaymentStatus.php`, `app/Console/Commands/TestInvoiceReminder.php`
- **Problem:** the command loads every unpaid invoice with Eloquent three times and filters them by due date in PHP, resolves the notifier with `app()` and catches `\Exception` around the whole run; the delivery layer works with models (`rules/architecture.md`).
- **Fix:** an Invoice application use case that reads the unpaid invoices due on a given day through a read port (the due date computed in SQL) and sends the reminders through `InvoiceReminderNotifierInterface`; the commands only call it.

### Format invoice amounts through a presenter
- **Where:** `resources/views/frontend/invoices/show.blade.php`
- **Problem:** amounts are formatted in Blade with `number_format(..., 2, ',', ' ')` and fallback logic; the Czech format is used for every locale.
- **Fix:** an invoice detail presenter using `MoneyFormatterInterface`, like `app/Application/Analytics/Presenters/*`.

## Payment

### GoPay is called inside the database transaction
- **Where:** `app/Domain/Payment/Services/SubscriptionPaymentService.php::processSubscriptionPayment()`
- **Problem:** the transaction wraps `createPayment()` of the gateway. When a later step fails, the rollback deletes the local payment record, but the payment created at GoPay stays and its notification finds no record; the transaction also holds its locks for the whole HTTP call.
- **Fix:** store the pending payment in one transaction, call the gateway outside it, then store the gateway id and status in a second transaction; a failed gateway call marks the payment failed. Move the use case to Application (`rules/application.md`).

### Subscription activation ignores the plan's duration
- **Where:** `app/Domain/Payment/Services/SubscriptionPaymentService.php` (activation after a paid notification), `app/Domain/Payment/DTO/SubscriptionPlanDTO.php`, `EloquentSubscriptionWriteRepository::activateById()`
- **Problem:** the activation reads `$subscription->subscriptionPlan->duration_days ?? 0`, but the plan DTO has no `duration_days` (it has `billingPeriod` and `billingInterval`), so a paid subscription is activated with `ends_at = now()`. Larastan reports the undefined property from level 2. Found 2026-10-26 while checking the branch `ls-dead-code` at level 2.
- **Fix:** compute the end from the plan's billing period and interval through the clock, with a test of the notification that activates a subscription. With the payments work (Order of work 11).

### Build refunds
- **Where:** `payments.refunded_amount`, statuses `refunded` and `partially_refunded` (`2026_10_25_000010_create_subscription_tables.php`, `lang/*/payments.php`), `app/Infrastructure/Providers/Payment/GoPayGateway.php`
- **Problem:** Billex cannot refund a payment. A refund flow that nothing called (`RefundState`, `RefundResultDTO`, the refund use cases, the gateway's refund call and `Payment::applyRefund()` with the logic in the model) was deleted (commit "Delete unused payment code", branch `ls-dead-code`); the column and the statuses remain.
- **Fix:** when refunds are needed: a `RefundState` value object computes the new state (accumulation, partial or full), the gateway refunds through the payment port, the repository only persists the result, returned as `RefundResult` (`rules/architecture.md`, Data classes).

### Typed GoPay request with real customer data
- **Where:** `app/Infrastructure/Providers/Payment/GoPayGateway.php`
- **Problem:** `createPayment(array $paymentData)` is untyped and the amount is not checked against gateway limits; a `PaymentAmount` value object for the limits had no caller and was deleted with its `payment:limits` report (commit "Delete unused payment code", branch `ls-dead-code`). Without customer data the gateway sends a placeholder customer (`Test User`, `test@example.com`); `SubscriptionController` passes none.
- **Fix:** build `PaymentAmount` (`Money` checked against the gateway's limits) and a request DTO (amount as `PaymentAmount`, plan, customer, URLs) filled with the subscriber's real data. Do this with the subscription plans work.

### Payment DTOs do not match the model casts
- **Where:** `app/Domain/Payment/DTO/PaymentDTO.php`, `app/Domain/Payment/DTO/PaymentWriteData.php`, `app/Models/Payment.php`
- **Problem:** the model casts `gateway_data` to an array, but both DTOs type it `?string`: mapping a payment that holds gateway data throws a `TypeError`. Found while adding Larastan (2026-10-23).
- **Fix:** type the gateway data in the DTOs (a typed value or `array<string, mixed>`), with a repository test that stores and reads it back. Do this with the payments work (Order of work 11).

### Validate the variable symbol in QR payments
- **Where:** `app/Domain/Payment/DTO/QrPaymentPayload.php`
- **Problem:** the variable symbol is not validated (Czech QR standard: max 10 digits); constant and specific symbols are not supported.
- **Fix:** a `VariableSymbol` value object in the payload; add CS/SS if needed.

### Build `BankAccount` and validate bank accounts through it
- **Where:** `app/Application/Party/Services/PartyInputValidationService.php::isValidIBANFormat()`, `app/Domain/Payment/Providers/QrPayment/`
- **Problem:** IBAN is validated with a simplified regex (no mod-97 checksum); QR providers format accounts themselves. A `BankAccount` value object with the checksum had no caller and was deleted (commit "Delete unused payment code", branch `ls-dead-code`).
- **Fix:** build `BankAccount` (IBAN checksum, Czech account number and bank code, formatting) and validate and convert through it, also in the QR providers (`rules/payments.md`). Deliver with the planned multiple-account work in step 7 (`docs/PRD_BUSINESSES_AND_CONTACTS.md`); no implementation is completed yet. The validator is stricter, so check development account data when rebuilding the seeders.

### Payment method request does not match the table
- **Where:** `app/Http/Requests/Admin/PaymentMethodRequest.php`, table `payment_methods`
- **Problem:** the request validates `description` and `is_active`, which the table does not have, and leaves `country`, `currency` and `icon` unvalidated although the admin form edits them.
- **Fix:** validate the columns the form sends (`country` as an ISO code, `currency` through the `Currency` enum) and drop the rules for missing columns.

### Payment adapter guard checks nothing
- **Where:** `app/Console/Commands/PaymentAdapterGuardCommand.php`, `app/Domain/Payment/Services/GatewayRegistry.php`
- **Problem:** `php artisan payment:adapter-guard` reports "No gateways registered" in the console, so the CI step passes without validating any gateway.
- **Fix:** register the gateways (GoPay) where the guard can see them, or let the guard resolve the registry the same way the application does.

### Subscription screens with placeholder content
- **Where:** `resources/views/frontend/subscriptions/{index,show}.blade.php`, `app/Http/Controllers/Admin/SubscriptionCrudController.php`
- **Problem:** the checkout asks to agree to the terms of service and the privacy policy, but both links point to `#`. Every yearly plan carries a fixed "2 months free" badge, whatever its price. The admin list prints every amount with a "CZK" suffix, also for EUR and USD subscriptions.
- **Fix:** link real terms and privacy pages (Content), compute the saving from the monthly and yearly prices or drop the badge, format amounts with the subscription's currency through `MoneyFormatterInterface`. Do this with the subscription plans work.

## Party / Admin

### Clients and suppliers mix the user's businesses with their contacts
- **Where:** `clients`, `suppliers`, `expenses.supplier_id`, `products.supplier_id`; the 50 files in 25 Client / Supplier pairs of the Party context and its delivery (models, repositories, mappers, ports, DTOs, write data, events, observers, controllers, requests, form fields, Livewire components), views and `resources/js/{client,supplier}-form-data.js` (~85 % identical); `clients.php` / `suppliers.php`; `can_create_edit_client` / `can_create_edit_supplier` and their UELS limits
- **Problem:** `suppliers` holds the user's own businesses (registration creates one, it carries the logo and the bank account of the QR payment), `clients` holds the companies the user invoices, and `expenses.supplier_id` points to the user's own businesses where a received document needs the company the user pays. Supplier and customer are roles on a document, so a company that is both would need two records, and received documents have no counterparty.
- **Fix (decided 2026-10-25, refined 2026-10-26, `docs/adr/D-045-businesses-and-contacts.md`, step 7):** `Business` (`businesses`, "Moje firmy") replaces `suppliers`, `Contact` (`contacts`, "Kontakty") replaces `clients` and also holds the suppliers of received documents. One shared address book per user, document-derived roles, optional email/phone, explicit saving of one-off counterparties, separate addresses and multiple bank accounts with one common default. Archive/restore businesses and contacts in this branch; plans count active records. Only Czech IČO duplicates block in version 1; foreign matches warn. Remove `products.supplier_id` for a shared user catalogue. The original ADR questions, edge behaviour and minimal current-expense scope are resolved in the approved version 1.0 of `docs/PRD_BUSINESSES_AND_CONTACTS.md`. This is a plan, not a completed conversion.

### Clients, suppliers and products cannot be archived
- **Where:** `clients`, `suppliers`, `products` (no `deleted_at`), `ProductService::deleteProduct()`, the restricting foreign keys of `invoices`, `invoice_products` and `expenses`, the unique indexes `(user_id, ico)` and `(user_id, slug)`
- **Problem:** documents keep their client, supplier and product (foreign keys restrict deleting them), so a user cannot remove a client they have invoiced from their lists; the delete is refused with a message. Soft deletes would collide with the unique indexes: a new client with the IČO of an archived one is refused.
- **Fix (decided 2026-10-25, refined 2026-10-26):** business/contact archiving (an `archived_at` column, not Laravel soft deletes, so the unique key on the Czech IČO keeps covering archived records) and restore move to step 7 with their new model (`docs/PRD_BUSINESSES_AND_CONTACTS.md`); product archiving stays in step 8. Archived records disappear from new selections and remain in the archive, on documents and in statistics. Matching an archived Czech registration identity offers explicit restoration without silently changing its state or data. Archive frees active-count capacity; restore requires capacity. The last active business may be archived, with history retained and further issuing requiring an active business. Domain methods throw exceptions instead of returning `false` (`rules/domain.md`).

### Expense detail has no attachment links
- **Where:** `app/Http/Controllers/Admin/ExpenseCrudController.php` (`setupShowOperation()`)
- **Problem:** the detail reuses the list column, which prints attachment file names joined by commas and cut to 50 characters, with no way to open a file. A renderer with links was present only as commented-out code since the first commit (2025-05-28) and was removed.
- **Fix:** a show column listing each attachment as a link (file names escaped, URLs from the storage disk, not concatenated paths), with an HTTP test of the detail page.

### Unreachable branch of the frontend user request
- **Where:** non-profile branch of `app/Http/Requests/UserRequest.php`
- **Problem:** no route reaches it; it checks `frontend.can_create_edit_user`, which is not in `PermissionCatalog`. `PermissionCatalogCoverageTest` allowlists it as unreachable.
- **Fix:** delete the branch, then drop the allowlist.

### Admin list columns build display text by hand
- **Where:** `app/Http/Controllers/Admin/{EntityUsage,EntityLimit,PerformanceMetric,DatabaseMaintenanceLog}CrudController.php`; badge HTML and classes in `Status::getColorPreviewAttribute()` and `UserActivitySummary::getActivityBadgeClassAttribute()`; hardcoded labels also in the `Client`, `Supplier`, `Product`, `Invoice`, `Status`, `Subscription` and `ExpenseCategory` CRUD controllers
- **Problem:** amounts and sizes are formatted with `'€' . number_format(...)` and private `formatBytes()` helpers, ignoring the locale and the entity's currency. Labels and cell texts are hardcoded English (`->label('User')`, `'Anonymous'`, `'Pending'`). The maintenance results and performance metadata closures put MySQL output (`Msg_text`, storage engine, metadata values) into HTML unescaped. Models return badge HTML (`getColorPreviewAttribute()`).
- **Fix:** amounts through `MoneyFormatterInterface`, labels and texts as translation keys, every interpolated value through `e()` or a Blade column view, badge HTML out of the models into column views with the status as an enum (`rules/admin.md`, `rules/persistence.md`, `rules/translations.md`).

### Database dashboard shows invented metrics
- **Where:** `app/Http/Controllers/Admin/DatabaseDashboardController.php` (`generateSampleDataForMetricType()`, cleanup type `sample`, the `sample_metrics` count), `app/Console/Commands/CleanPerformanceMetrics.php`, `resources/views/admin/database/dashboard.blade.php`
- **Problem:** when no metric of a type is stored, the performance chart shows random values labelled as sample data. The `sample` cleanup and the `sample_metrics` count look for rows marked `metadata.generated`, which nothing writes since the unrouted sample generator was deleted (branch `ls-dead-code`).
- **Fix:** an empty state instead of random values; delete the `sample` cleanup type, the count and their translations.

### Admin dashboard formats revenue by hand
- **Where:** `resources/views/vendor/backpack/ui/dashboard.blade.php` (monthly revenue card, revenue overview), `$dashboardStats` built for it
- **Problem:** revenue is a float printed as `€ ` + `number_format(..., 2)` with a `la-euro-sign` icon, whatever the invoices' currencies and the locale; the view also formats and computes values in Blade.
- **Fix:** the dashboard stats come from an application service as a DTO with `Money` totals per currency, rendered through `MoneyFormatterInterface` by a presenter (`rules/domain.md`, `rules/frontend.md`).

### `backpack_pro()` override claims a PRO licence
- **Where:** `app/Helpers/BackpackHelpers.php` (`backpack_pro()` always returns `true`), Backpack's `src/Stats.php`
- **Problem:** the override lifts Backpack's PRO gates, which Billex's own filters need (`CrudFilter` refuses to work without PRO). Outside the `local` environment, Backpack sends usage stats to backpackforlaravel.com on about 1 in 100 admin requests, including `BACKPACK_PRO_VERSION => backpack_pro()`, so production would report a PRO licence it does not have. PRO-only operations (`FetchOperation`, `InlineCreateOperation`, `CloneOperation`, `BulkDeleteOperation`) would fail with a missing class instead of a clear error.
- **Fix:** decide before production: buy a PRO licence, or keep the gates closed and make the filters work without the override (e.g. own filter classes that do not extend `CrudFilter`'s PRO check).

### Admin interface has no Slovak texts
- **Where:** `vendor/backpack/crud/src/resources/lang` (no `sk`), admin locales in `config/backpack/crud.php` (`cs`, `de`, `sk`)
- **Problem:** the admin offers Slovak, but Backpack itself ships no Slovak texts, so buttons, pagination, filters and messages of every admin screen stay English in `sk`. Only the role and permission screens are covered, by `lang/vendor/backpack/sk/permissionmanager.php`.
- **Fix:** add `lang/vendor/backpack/sk/{base,crud}.php` with the keys of the package's `en` files, checked against the package after each Backpack upgrade; or drop Slovak from the admin locales.

## Product

### Products have no unit field
- **Where:** `app/Infrastructure/Forms/Products/ProductFormFields.php`, frontend and admin product forms and requests
- **Problem:** `products.unit` exists and is copied into the invoice item when a product is picked, but no form sets it, so every product counts in pieces.
- **Fix:** a unit select from `<x-unit-options />` (frontend) and the same option list in the admin, validated with `Rule::enum(Unit::class)`.

## Analytics

### Dashboard statistics sum amounts across currencies
- **Where:** `app/Infrastructure/Persistence/Eloquent/Analytics/Repositories/EloquentApiStatisticsRepository.php`, `app/Application/Analytics/Contracts/ApiStatistics{Repository,ApplicationServiceInterface}.php`, `app/Http/Controllers/Api/StatisticsController.php`, `resources/js/dashboard-statistics.js`, `app/Domain/Analytics/Services/DashboardService.php::getMonthlyStatistics()`
- **Problem:** `SUM(invoices.payment_amount)` and `SUM(expenses.amount)` add invoices and expenses in different currencies into one number. The port returns `list<array<string, mixed>>` instead of DTOs, lives in Application without the `Interface` suffix, the service has an interface without being a port, and the controller returns arrays without a `JsonResource`. The dashboard no longer reads the server-side monthly statistics (`getMonthlyStatistics()`, `MonthlyStatDTO`, `MonthlyStatPresenter`), but `DashboardApplicationService` still computes them. A failed request leaves a chart empty with only a console error, in all five charts.
- **Fix:** sums per currency as `Money` in statistics DTOs (`MonthlyStatDTO`-style), a Domain port `StatisticsReadRepositoryInterface`, a `final` service without interface, resources for the responses; the charts show one series per currency or convert through ČNB rates (decide with "Currency conversion of invoice items happens in the browser"). Reuse `MonthlyStatDTO` for the API or delete the server-side monthly statistics; a translated empty and error state in `dashboard-statistics.js`.

## Entity limits

### UELS API routes shadow each other
- **Where:** `routes/api.php` (`/uels` and `/uels/{id}` in the frontend group, `/uels` and `/uels/{entity}` in the public `auth:web` group)
- **Problem:** two routes share each URI, so `api.uels` (`getUELS`) and `api.uels.entity` are never reached; `/uels/{entity}` answers with `getUELSById`.
- **Fix:** one route per endpoint inside the `api.require.frontend` group, delete the endpoints without a consumer (`http.md`, API responses).

### UELS dashboard bulk reset fails
- **Where:** `app/Http/Controllers/Admin/UELSDashboardController.php::bulkResetUsage()`
- **Problem:** the action passes a `User` model to `UniversalLimitService::resetUsage(?int $userId, …)` and ends with a `TypeError` (500). Its validation marks both checkboxes `accepted`, so resetting only anonymous usage or only users cannot be submitted at all.
- **Fix:** pass the user id, make both options optional (at least one required), cover the action with a feature test. With the subscription plans work.

### Hardcoded units in the usage CRUD
- **Where:** `app/Http/Controllers/Admin/EntityUsageCrudController.php` (`formatBytes()`), `resources/views/admin/uels-dashboard.blade.php`, `resources/js/uels-manager.js`
- **Problem:** value-based limits print a fixed `€` and sizes `KB`, the widget title in `uels-manager.js` prints the raw entity type and period slugs. The unreachable reset actions and the request of the read-only CRUD were deleted (branch `ls-dead-code`).
- **Fix:** format amounts through `MoneyFormatterInterface` with the limit's currency, pass translated labels to the widget script.

## Cross-cutting

### Move cache invalidation from observers to domain event handlers
- **Where:** `app/Infrastructure/Persistence/Eloquent/*/Observers`, `app/Domain/Analytics/Events/AnalyticsDataChanged.php`, `app/Domain/Analytics/Events/Handlers/InvalidateAnalyticsCache.php`
- **Problem:** events are not yet used as `.claude/rules/architecture.md` (Events) requires. Cache invalidation runs through observers → `UserDataChanged`; `AnalyticsDataChanged` is never published, so its registered handler never runs. Only the `*Created` events of invoices, clients, suppliers and products are published; the update, delete and set-as-default events nobody published were deleted (commit "Delete unpublished domain events and unread event accessors", branch `ls-dead-code`) and are added back with their publishers.
- **Fix:** add and publish the entity events from the domain services, let the cache invalidation handle them as Laravel listeners (after the commit, `rules/architecture.md` Events; `InvalidateAnalyticsCache` is a domain handler today, which would run inside the transaction), then remove the observers and `AnalyticsDataChanged`.

### Dates outside the PDFs are formatted by hand
- **Where:** `app/Infrastructure/Shared/Support/DateHelper.php` (formats for cs and en only), 15 views in `resources/views` (`->format('d.m.Y')`, `DateHelper::format()`, e.g. `frontend/invoices/show.blade.php`, the Livewire invoice lists), `app/Infrastructure/Notifications/Invoice/Messages/Invoice{Upcoming,Overdue,}DueReminder.php`
- **Problem:** the PDFs format dates through `DateFormatterInterface` in their language, but the frontend, the admin and the reminder emails print the Czech format for every locale.
- **Fix:** dates through `DateFormatterInterface` in the recipient's or the page's locale: a `<x-date>` Blade component for views, the notifications format with the payload's locale; delete `DateHelper`.

### `ddd:guard` snapshot test fails now and then in parallel runs
- **Where:** `tests/Feature/Console/DddGuardDiJsonSnapshotTest.php`
- **Problem:** in `php artisan test --parallel` the `Artisan::call('ddd:guard', …)` call occasionally throws (seen 2026-10-22); the test passes alone and in the next full runs.
- **Fix:** find what the command shares with other parallel processes (scanned files, cache, output buffer) and isolate it, or drop the test together with the guards (Architecture guards are migration scaffolding).

### Test runs and CI are slower than they need to be
- **Where:** `tests/TestCase.php` (`RefreshDatabase`), `phpunit.xml`
- **Problem:** every parallel process migrates and seeds before its first test: 12–14 s each with 12 processes, about 170 s of the 663 s the 2986 tests take together (2026-10-25, branch `ls-unit-tests`); the run takes 0:57. Migrating is about 6 s of it, seeding the rest.
- **Fix:** `php artisan schema:dump` would save the migrating part (about 6 s of the run) but needs the `mysql` client in `app.dockerfile` and in CI and a new dump after every migration; decided 2026-10-25 to measure again after step 8, when the invoice and document migrations are in.

### Erasing a user account
- **Where:** `users` (soft deletes), `UserCrudController`, the restricting `user_id` foreign keys of `clients`, `suppliers`, `products`, `expenses`, `invoices` and `subscriptions`; cascading `entity_limit_usage`, `model_has_roles`, `model_has_permissions`
- **Problem:** there is no way to erase a user under GDPR. A database cascade would delete documents without the export and notice the law requires, so the foreign keys restrict and nothing disappears by accident. For the user's invoices, clients and expenses Billex is a processor: the duty to archive them is the user's, and after the service ends Billex returns and deletes them (Art. 28(3)(g) GDPR). For the account, subscription and Billex's own invoices for it Billex is the controller and keeps them for 10 years as a legal obligation (Art. 17(3)(b)).
- **Fix (proposed 2026-10-25, to be reviewed by a lawyer, with the terms of service and the data processing agreement):** an application use case, not a cascade: the user requests erasure and receives an export of all their data (invoice PDFs, expenses, clients); the account is blocked at once; after the announced period the use case deletes their business data, credentials, tokens, logs and settings in dependency order; only Billex's documents for the subscription stay, with the minimum data about the user as customer.

### Resilience of external calls
- **Where:** `app/Http/Controllers/Api/AresLookupController.php`, `app/Infrastructure/Shared/Http/Services/HttpClientService.php`, `config/analytics.php`
- **Problem:** ARES is called with `Http::get()` directly from a controller without a timeout; exchange rates and countries go through `HttpClientService` without timeouts.
- **Fix:** timeouts and a circuit breaker in `Shared` around `HttpClientService`; move the ARES call into a service.

### No error monitoring
- **Where:** `bootstrap/app.php` (`withExceptions`), `config/logging.php` (`LOG_STACK`), `phpunit.xml`, `vite.config.js`, the layouts `resources/views/layouts/frontend.blade.php` and `app.blade.php` and the Backpack theme layout
- **Problem:** errors outside a browser the developer is looking at — scheduled tasks in the `cron` container, queued jobs, GoPay and ARES calls, JavaScript in users' browsers — end up only in `laravel.log` or nowhere. Many controllers catch an exception and only log it, so it never reaches the exception handler.
- **Fix (decided 2026-10-25):** Sentry Cloud with the EU data region (chosen when the organisation is created, cannot be changed later), one project per environment or the `environment` tag. PHP: `sentry/sentry-laravel`, `Integration::handles($exceptions)` next to `ExceptionHandling::registerCallbacks()`, a `sentry` log channel at level `error` in `LOG_STACK` so logged-and-caught exceptions arrive without touching controllers, `send_default_pii` off and no SQL bindings (invoices hold clients' personal data), empty `SENTRY_LARAVEL_DSN` in `phpunit.xml`. Browser: `@sentry/browser` initialised first in `resources/js/app.js` and in the admin layout, no session replay until amounts and personal data are masked; inline `<script>` blocks in the layouts move into modules (`rules/frontend.md`) so no code runs before the SDK. Source maps: `build.sourcemap: 'hidden'` (Sentry matches them through the debug IDs the plugin injects), uploaded by `@sentry/vite-plugin` and deleted after the upload, never served; the plugin keeps them unless `sourcemaps.filesToDeleteAfterUpload` is set (`public/build/**/*.map`). The build that uploads them, in CI or on deploy, fails when `public/build` still contains a `.map` file.

## Conversion to the standard

The code standard is defined in `.claude/rules/` (decided 2026-10-20). Each item below is a gap between that standard and the code; convert one bounded context at a time.

### Larastan level
- **Where:** `phpstan.neon`
- **Problem:** Larastan runs at level 1 (2026-10-23); level 2 reports about 770 more errors (2026-10-26; among them a call to a method that does not exist, which broke saving a default supplier until the branch `ls-dead-code`, and "Subscription activation ignores the plan's duration"), level 6 over 4000. One level applies to the whole codebase. Array value types are not checked yet: `phpcs.xml` leaves out Slevomat's `MissingTraversableTypeHintSpecification` (about 1150 signatures without `list<…>` / `array<string, …>`), which Larastan reports from level 6.
- **Fix:** raise the level once the whole codebase passes the next one: levels 2–6 while the contexts are converted (Order of work 13), 7–10 (`max`) after that, one level per branch. No baseline file and no ignore comments.

### `strict_types` and `final`
- **Where:** all of `app/`, `tests/`, `database/` (149 of 1018 files declare `strict_types`, 120 classes are `final`, 2026-10-25); files touched by a branch keep their old shape, e.g. `app/Http/Controllers/Frontend/ProfileController.php` (neither)
- **Fix:** add `declare(strict_types=1)` and `final` (except abstract bases and Eloquent models, `rules/php.md`); the test suite catches the coercions this exposes.

### Transaction boundaries
- **Where:** `ProductApplicationService` (`DB::transaction()`), `app/Domain/User/Services/UserService.php` (`create()`, `updateById()`), `SubscriptionPaymentService`, `app/Infrastructure/Persistence/Eloquent/Shared/Database/EloquentTransactionBoundary.php` (not `final`)
- **Problem:** use cases open transactions through the facade instead of `TransactionBoundaryInterface`; Domain services open transactions themselves, `UserService` around a single write.
- **Fix:** per `rules/application.md`: the application service opens the transaction through the port when the use case writes more than one record; single writes need none.

### Two sets of repositories
- **Where:** 19 repository contracts in `app/Application/*/Contracts` returning Eloquent models (e.g. `InvoiceReadRepositoryInterface`), next to the Domain `*Dto*RepositoryInterface` ports
- **Problem:** Application, controllers and Livewire work with models; the same entity has two persistence paths.
- **Fix:** move every use to the Domain ports, delete the Application repositories and their implementations. Then, per `rules/persistence.md` (decided 2026-10-20): ports without `Dto` and always with `Interface` (`ClientReadRepositoryInterface`; today 24 ports incl. `BankDtoRepository`, `AnalyticsDtoReadRepository`); a read port for every read entity, a write port only where the Domain writes (unsplit today: `StatusDtoRepositoryInterface`, `BankDtoRepository`, `ArtisanCommandsDtoRepositoryInterface`, `AnalyticsDtoReadRepository`); one `Eloquent<Entity>Repository` per entity implementing both (merge `EloquentPaymentDto{Read,Write}Repository`, `EloquentSubscriptionDto{Read,Write}Repository`).

### Framework and model dependencies outside Infrastructure
- **Where:** `App\Models` in 37 Application files, 39 of 55 controllers and all 10 Livewire components; `Illuminate\Http` in 7 Application files; facades/helpers in 13 Application files (e.g. `Log` in `ApplicationUseCaseLogging`, `session()` in `InvoicePdfApplicationService`, `Cache` in `TemporaryInvoiceStore` — their tests have to boot Laravel); `config()` in `app/Domain/Shared/File/Config/FileUploadConfig.php`; `app/Domain/Payment/Services/SubscriptionPaymentService.php` imports `GoPayGateway`
- **Fix:** per `rules/architecture.md` (dependency table); Livewire components call application services.

### Interfaces without a port
- **Where:** 44 of 45 `*ServiceInterface` contracts in Domain and Application have a single implementation; 17 test files mock them
- **Fix:** delete the interfaces and their bindings, make the services `final`, rewrite the mocking tests to run the real service with fakes at the ports; refresh the provider snapshot.

### Data class names and properties
- **Where:** `app/*/*/DTO/`, `app/Domain/Shared/Money/Enum`, `app/Domain/Invoice/Notifications/Enums`, `app/Application/Shared/Form/Enum`
- **Problem:** suffixes `DTO`/`Data`/`Payload`/`ResultDTO` mixed; 167 snake_case vs 75 camelCase properties, mixed within one class.
- **Fix:** `PrintableInvoiceData` → `PrintableInvoiceDTO`, `PrintableInvoiceItemData` → `PrintableInvoiceItemDTO`, `QrPaymentPayload` → `QrPaymentDTO`, `InvoiceReminderPayload` → `InvoiceReminderDTO`, `SubscriptionPaymentResultDTO` → `SubscriptionPaymentResult`; replace `InvoiceMutationPayload` with `InvoiceWriteData`; move `InvoiceActionStatus` to `Enums/`, rename every `Enum/` directory to `Enums/`; camelCase properties with `final readonly class`.

### Value object accessors
- **Where:** all 13 classes in `app/Domain/*/ValueObjects` and `app/Domain/Shared/*/ValueObjects`
- **Problem:** private state read through three naming styles: `getValue()` (46 calls), `toInt()` (`UserId`, `PartyId`; 83 calls), `get<Part>()` (`Money::getAmount()`, `InvoiceNumber::getNumber()`, …).
- **Fix (decided 2026-10-20):** `final readonly class` with public readonly properties for held state (`->value`, `->amount`, `->iban`), computed values as methods without `get` (`formattedIban()`, `year()`), per `rules/php.md`.

### Business logic in models
- **Where:** `app/Models/Invoice.php` (`subtotal_money` / `total_tax_money` accessors computing totals through `InvoiceTotals`, due date, `overdue` and `paid` for the lists, logging, `HasRoles`), `HasRoles` also on `PaymentMethod`, `$guarded` in 6 models; `User::invoices()` goes through the user's clients instead of `invoices.user_id`, so the `total_invoices` of `EloquentUserRepository` misses invoices without a client (`client_id` is nullable); invoices from 2025 in the development database keep their items only as JSON in `invoice_text`
- **Fix:** logic into Domain, totals built by `EloquentInvoiceMapper` once Application no longer receives models, `$fillable`, `User::invoices()` as `hasMany` on `user_id` (`rules/persistence.md`).

### Validation in several places
- **Where:** frontend and admin form requests of the same entity with diverging rules (e.g. `InvoiceRequest` vs `Admin\InvoiceRequest`), `app/Application/*/Services/*InputValidationService.php`, `app/Domain/*/Validation/`; entity limits checked in requests, controllers and observers
- **Fix:** one rule set per entity in form requests, business validation in value objects, limit check once in the application service (`rules/http.md`, `rules/permissions.md`).

### Directory layout
- **Where:** `app/Infrastructure/Interfaces/`, `app/Infrastructure/Services/`, `app/Infrastructure/Providers/Payment/` (→ `Gateways/Payment/`), `app/Infrastructure/Forms/Products/` (→ `Product/`), `app/Application/Invoice/Services/Specialized/`, `app/Domain/Payment/Providers/QrPayment/`; contracts without the `Interface` suffix: `AnalyticsDtoReadRepository`, `Locale`, `BankDtoRepository`, `ApiStatisticsRepository`, `BankRepository`, `Str`, `Config`
- **Fix:** per `rules/architecture.md` (Directory layout, Ports and interfaces); framework wrappers in `Domain/Shared` that are not ports (`Str`, `Config`, `Console`) are removed.

### Error handling
- **Where:** 121 `catch (\Exception …)` in 53 files, mostly `Log::error($e->getMessage())` plus a generic message; failures returned as `false` or `['success' => false]`; only 4 domain exceptions
- **Fix:** context exceptions, mapping in `app/Exceptions/ExceptionHandling.php` (`rules/php.md`, Errors). The tests that pinned the catch-all responses (`exception_returns_500` and the like) were deleted with the `tests/Unit` clean-up; the conversion tests the mapped responses instead.

### Tests that pin structure instead of behaviour
- **Where:** `tests/Feature` only (`tests/Unit` was cleaned up 2026-10-25, branch `ls-unit-tests`): 27 `class_exists`/`method_exists` calls, 23 files using reflection, 24 `$fillable`/`$casts` references (e.g. `tests/Feature/Models/*`); `CreatesFrontendTestEnvironment` creating permission tables and hand-written permission lists; 15 tests named `test_*` or `@test` (PHPUnit 12 drops `@test`)
- **Fix:** delete structural tests and convert the rest with each context (step 13), use `PermissionCatalog` and migrations (`rules/testing.md`).

### Frontend
- **Where:** `resources/views` (25 files with inline `<script>`, 100 `style=""` attributes, 103 `@php` blocks, `number_format` for money), `resources/js` (6.5k lines of page scripts and `window.*` globals beside Livewire/Alpine)
- **Problem:** besides the above, the invoice forms are filled twice over. `InvoiceFormApplicationService::prepareCreateData()` passes empty `userInfo` and `clientInfo` (the edit form gets neither), which `frontend/invoices/{create,edit}.blade.php` still read as fallback values and edit links; the supplier and client are filled in the browser by `supplier-form-data.js` / `client-form-data.js` from `/api/supplier/default`, `/api/client/default` or the `supplier_id` / `client_id` URL parameter.
- **Fix:** per `rules/frontend.md`; the Livewire invoice form fills the supplier and client on the server from `defaultSupplier` / `selectedClient` and drops `userInfo`, `clientInfo`, both scripts and their endpoints ("API responses").

### Translations
- **Where:** 44 `trans()` calls in `app/` and `resources/views`; 9 form requests with `messages()`; `lang/*/admin.php` sections `dashboard`, `user_activity`, `user`, `auth`, `general`; `lang/*/emails.php` calls `config()` and `date()`
- **Problem:** the structure and every literal key are in place (`rules/translations.md`, `TranslationKeysExistTest`), but some code still uses `trans()`, some requests repeat standard messages, the admin dashboard and user activity texts sit in `admin.php` beside the menu, and the email texts compute the app name and year inside the language file. Keys that no code references were never counted after the restructuring.
- **Fix:** `__()` on the remaining calls; drop `messages()` that repeat `lang/*/validation.php`; move the dashboard and user activity texts to `lang/*/admin/dashboard.php` (or their entity files); pass the app name and year to the email texts as parameters; delete unreferenced keys file by file. Convert with `/convert-to-standard` context by context.

### Route names and URLs
- **Where:** `routes/web.php`, `routes/api.php`, every `route('…')` call in `app/`, `resources/views/`, `resources/js/` and `tests/`
- **Problem:** index routes are plural without an action, the others singular (`frontend.clients` vs `frontend.client.create`); API names follow no pattern (`api.client`, `api.invoice.query`, `api.client.default`); URLs are singular (`/client`, `/invoice/{id}/edit`); custom actions vary (`mark-as-paid`, `set-template`, `batch-actions`).
- **Fix (decided 2026-10-20):** plural URLs and `<area>.<resource plural>.<action>` names through `Route::resource()` / `apiResource()`, custom actions as kebab-case verbs (`rules/http.md`, Routes); update all references.

### API responses
- **Where:** `app/Http/Controllers/Api/*` (114 `response()->json` calls in controllers), `resources/js/*` consumers
- **Problem:** whole objects are serialised (`response()->json($client)`), error bodies differ (`{"error"}`, `{"error", "code"}`), status codes are wrong (another user's client → 403 with a "not found" message, any exception → 404), every action repeats `try/catch` and an auth check the middleware already does.
- **Fix (decided 2026-10-20):** `JsonResource` per DTO with snake_case keys and `{"data"}` wrapping, standard error format through `ExceptionHandling` (`rules/http.md`, API responses). Endpoints that only feed vanilla-JS form prefill (`/api/client/{id}`, `/api/client/default`, `/api/supplier/…`, `/api/session-refresh`) are removed when those forms move to Livewire instead of being converted.

### Permission naming
- **Where:** `app/Infrastructure/Authorization/PermissionCatalog.php`
- **Problem:** three schemes for the same entities: `can_create_edit_<entity>` (113 checks, deleting included), `can_{view,create,update,delete}_<entity>` (only `can_view_*` is checked) and `<guard>.api.<entity>`.
- **Fix (decided 2026-10-20):** granular `can_{view,create,update,delete}_<entity>` (`frontend.` prefix for the web guard), other actions `can_<verb>_<entity>`, Backpack operations gated one by one; drop `can_create_edit_*` and `<guard>.api.<entity>` (keep `<guard>.api.access`). A migration removes the old permissions and grants the new ones to the existing roles (`rules/permissions.md`).

### Architecture guards are migration scaffolding
- **Where:** `config/ddd_guard.php` (allowlist for the deleted `app/Services`, scan path `app/Policies`, Bitbucket detection), `ddd:legacy-report`, `architecture/baselines/*` (README still describes a throwaway container)
- **Fix:** after the conversion, replace with Larastan and architecture tests of the dependency table in `rules/architecture.md`; delete the baselines and their commands.

### Comments
- **Where:** history and migration notes ("Wave 2", "Phase 1", "legacy compatibility", "Removed …"), Czech comments (`bootstrap/app.php` and 11 other files), `TODO`s without an item here
- **Fix:** per `rules/php.md` (Comments).

### Rename IČO and DIČ to `company_number` and `vat_number`
- **Where:** 105 files in `app/`, `resources/`, `lang/`, `tests/` — properties, variables and parameters `$ico` / `$dic`, `->ico` / `->dic`, prefixed variants (`client_ico`, `client_dic`, `supplier_ico`, `supplier_dic`); DB columns `ico` / `dic` in `clients` and `suppliers` (indexes `idx_clients_user_ico`, `idx_suppliers_user_ico`); translation keys (`clients.fields.ico`, …)
- **Problem:** the Czech abbreviations IČO (company registration number) and DIČ (VAT number) are used as identifiers in English code.
- **Fix (decided 2026-10-20):** rename everything, the same name in every layer: PHP `companyNumber` / `vatNumber`, DB columns and indexes `company_number` / `vat_number`, request and form keys `client_company_number`, `supplier_vat_number`, …, translation keys `….company_number` / `….vat_number`. Not `company_id` / `vat_id`: the `_id` / `Id` suffix is reserved for entity identifiers and foreign keys, and `vat_id` or `client_company_id` would read as one.
