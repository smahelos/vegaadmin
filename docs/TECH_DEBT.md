# Technical Debt

Known gaps between the code and the intended architecture (see `AGENTS.md`, `docs/adr/`). Most items are unfinished steps of the DDD migration (commits `a65aaffb` 2025-09-09 and `7e999b2a` 2025-10-12 in the original Bitbucket history): the target class exists, but the code was never switched over to it.

## Order of work

Decided 2026-11-06, replacing the priority order of 2026-10-23: the steps follow their dependencies, not their importance. A step that others build on goes before them, and work that touches the same files is done in the same step. The steps keep their numbers, which other documents refer to; steps 1–10 are done, the open ones are numbered in their order from 11.

Each open step has a section below with its items. An item without a **Status** line has not been started; an item that is partly done says what is left. A resolved item is removed, and a step is struck through once its section is empty. An item found later goes into the step that touches its code.

**Done**

1. ~~Tooling — Pint over the whole codebase in one commit, Larastan in CI, before the large rewrites so that their diffs stay clean.~~ Done 2026-10-23 (branch `ls-tooling`): Pint and PHP_CodeSniffer over the whole codebase, Larastan at level 1, all three in CI; raising the level is "Larastan level" below.
2. ~~Security — "Public statistics endpoints expose all users' revenue", "State changes over GET", "Sorting from the URL without an allowlist", "Middleware registered twice", "Exception messages shown to users".~~ Done 2026-10-24 (branch `ls-security`); found on the way: "Cron tasks are not fully gated by permissions", and the per-request admin access log, removed 2026-11-04.
3. ~~"Run tests on MySQL instead of in-memory SQLite" with the squashed migrations, before the invoice work adds new migrations.~~ Done 2026-10-25 (branch `ls-mysql-tests`): migrations squashed by context, reference data in `ReferenceDataSeeder`, the suite runs on MySQL locally and in CI.
4. ~~"Domain events are published inside the transaction" with "No queue worker" — the issued-invoice work publishes its events on top of it; both deliver work outside the request, and the queue's `after_commit` is set there.~~ Done 2026-10-25 (branch `ls-events-after-commit`): domain handlers (database writes) run inside the transaction, Laravel listeners (cache) after the commit with their failures logged, queued jobs are stored in the transaction on the `database` connection (`rules/architecture.md`, Events); a `queue` container runs the worker.
5. ~~"Test runs and CI are slower than they need to be" with the `tests/Unit` part of "Tests that pin structure instead of behaviour" — every later branch runs the suite many times.~~ Done 2026-10-25 (branches `ls-test-speed`, `ls-unit-tests`): no more migrating again after tests that left their transaction, no Basset downloads; `tests/Unit` holds only behaviour tests without Laravel and runs as its own CI job without MySQL. `schema:dump` was deferred, see "Test runs and CI are slower than they need to be".
6. ~~What an issued invoice freezes must be right first: "Modern and minimal PDFs always print …", "VAT rates are typed into the database by hand", "Calculate due dates through `InvoiceDueDate`", "Statuses are looked up by string slugs and labelled in English".~~ Done 2026-10-25 (branch `ls-invoice-print`): the PDFs print the stored tax point date, invoice text and payment method (`PaymentMethodCode`) in the PDF language, the tax point date is required, due dates go through `InvoiceDueDate`, PDF dates through `DateFormatterInterface`; invoice statuses are the `InvoiceStatus` enum and overdue is derived from the due date, which also made the payment reminders work again. Left over: "VAT rates by the supplier's country", "Status labels are translated in the model"; found on the way: "Dates outside the PDFs are formatted by hand"; the admin form could not save an invoice (its request required a field the form does not send), fixed on the branch.
7. ~~Businesses and contacts: "Clients and suppliers mix the user's businesses with their contacts" and "Optional client and supplier fields cannot be cleared" with "Rename IČO and DIČ to `company_number` and `vat_number`", "Build `BankAccount` and validate bank accounts through it" and the print/QR/reminder DTO names from "Data class names and properties" (`docs/adr/D-045-businesses-and-contacts.md`, `docs/prd/PRD_BUSINESSES_AND_CONTACTS.md`).~~ Done 2026-10-31 (branch `ls-businesses-contacts`, PR #66), implemented and verified as [the verification map](prd/VERIFICATION_BUSINESSES_AND_CONTACTS.md) records. Left to step 12: locking the issued document, its stored PDF, issue-time numbering, the variable symbol and product archiving; to step 13: the full received-document model and the Business tax profile.
8. ~~Entity limit design: "The entity limit system needs a decided design".~~ Done 2026-11-06 (branch `ls-entity-limit-design`): `docs/adr/D-047-entity-limits.md` and `docs/prd/PRD_ENTITY_LIMITS.md` accepted, the Entity limits section of `rules/permissions.md` rewritten; step 10 converts the code.
9. ~~Permission naming: "Permission naming".~~ Done 2026-11-08 (branch `ls-permission-naming`): one per-operation scheme with literal names, including the subscription, plan, plan feature and user screens; the PermissionManager role and permission screens gated by their own permissions; frontend products checked by permission; `<guard>.api.<entity>` removed.
10. ~~Entity limit conversion: "Entity limits do not follow D-047".~~ Done 2026-11-07 (branch `ls-entity-limit-conversion`): `role_limits` with its admin CRUD screen, the plan role assignment time on `users`, the `EntityLimit` domain service in every use case that creates or restores a limited entity, usage on the dashboard, the create and list pages and the admin user detail, rate limits in `config/billex.php`; the old system with its tables, `limit_*` and `can_create_<entity>_unlimited` permissions, API, dashboard and CRUD screens is deleted.

**Open**

11. [Foundations for the rewrites](#step-11-foundations-for-the-rewrites) — in progress (the permission gate with the whole permission conversion, the grants, the controller rule, `Money` formatting, the value object accessors, the currency of `Money` and the currency lists done, `ProductPrice` left to "Product prices are not `Money` on the write path", the currency of the DTOs to "Data class names and properties"): reliable tests and CI and the developer documentation of the contexts, which every later step uses.
12. [Issued invoices](#step-12-issued-invoices) — not started: the document life cycle with numbering at issue, its status column and the stored PDF.
13. [Expenses as received documents](#step-13-expenses-as-received-documents) — not started; shares the document tables and VAT lines of step 12 (`docs/adr/D-044-document-tables.md`).
14. [Sending invoices](#step-14-sending-invoices) — not started; sends the PDF that step 12 stores.
15. [Exchange rates](#step-15-exchange-rates) — not started; the rate is stored with the document of step 12.
16. [Statistics by currency](#step-16-statistics-by-currency) — not started; chooses between one series per currency and the ČNB rates of step 15.
17. [Test speed](#step-17-test-speed) — not started; measured once the migrations of steps 12 and 13 are in.
18. [Payments](#step-18-payments) — not started.
19. [Subscription plans](#step-19-subscription-plans) — not started; the plans grant the permissions of step 9 and the limits of step 10 and are paid through step 18.
20. [System admin screens](#step-20-system-admin-screens) — not started: screens of no bounded context (cron tasks, archiving, maintenance, admin users).
21. [Conversion to the standard](#step-21-conversion-to-the-standard) — in progress (PHP readability done): one context at a time, in the order of their dependencies.
22. [Frontend, API and routes](#step-22-frontend-api-and-routes) — not started: "Frontend", then "API responses" (the Livewire forms remove endpoints), then "Route names and URLs" (fewest references left).
23. [After the conversion](#step-23-after-the-conversion) — not started: Larastan up to `max`, architecture tests instead of the guards.
24. [Before production](#step-24-before-production) — not started: the PRO licence decision, error monitoring before the first deploy, erasing a user account before the first real user (needs the retention rules of step 12), refunds when they are needed.

## Step 11: Foundations for the rewrites

### `ddd:guard` snapshot test fails now and then in parallel runs
- **Where:** `tests/Feature/Console/DddGuardDiJsonSnapshotTest.php`
- **Problem:** in `php artisan test --parallel` the `Artisan::call('ddd:guard', …)` call occasionally throws (seen 2026-10-22); the test passes alone and in the next full runs.
- **Fix:** find what the command shares with other parallel processes (scanned files, cache, output buffer) and isolate it, or drop the test together with the guards (Architecture guards are migration scaffolding).

### npm audit allows the picomatch advisory
- **Where:** `.github/audit-ci.jsonc`, `.github/workflows/dependency_audit.yml`
- **Problem:** `GHSA-fc5q-39xc-vjpf` (ReDoS in `picomatch`) affects every version and has no fix (https://github.com/micromatch/picomatch/issues/234, 2026-11-18). It is reached only through the asset build (`vite`, `tinyglobby`, `flowbite-datepicker`), so the audit allows it rather than failing every branch.
- **Fix:** once a fixed `picomatch` is released, update `package-lock.json` and remove the advisory from the allowlist; the audit log reports it as not found when it no longer occurs.

### Subscription factory can generate a time skipped by daylight saving
- **Where:** `database/factories/SubscriptionFactory.php`; columns `starts_at`, `ends_at`, `trial_ends_at` and `next_billing_at` of `subscriptions`.
- **Problem:** the factory draws these values with `dateTimeBetween()`, and the columns are `timestamp`, which the database converts through its `SYSTEM` time zone. A draw inside the hour skipped by daylight saving, such as `2027-03-28 02:26:41`, is refused as an invalid value and fails an unrelated test (seen 2026-11-04; the next full run passed).
- **Fix:** generate times that cannot fall into the skipped hour, for example a fixed time of day, or run the test connection in UTC; a test creating a subscription then always passes.

### Admin browser scripts fail against the current forms
- **Where:** `tests/Browser/admin-document-forms.mjs`, `tests/Browser/admin-invoice-items.mjs` (last changed 2026-10-30); no workflow, script or document runs them.
- **Problem:** both scripts fail on `main` against the development data (seen 2026-11-12): `admin-document-forms.mjs` stops at its layout assertion "invoice-create: owner and business are not on the same row", `admin-invoice-items.mjs` finds no `[name=invoice-products]` input on the invoice page. Since nothing runs them, a change to the admin document forms is not checked in a browser, and a failure says nothing about the branch it runs on.
- **Fix:** find why each assertion fails (a changed form or a broken page) and fix the form or update the script; then either run the scripts where a change to the admin document forms is checked, or delete what the feature tests already cover.

### Documentation can quote code that no longer exists
- **Where:** `AGENTS.md`, `.claude/rules/`, `.claude/references/`, `.claude/skills/`, `docs/` (ADRs, PRDs, designs, `TECH_DEBT.md`)
- **Problem:** these files quote classes, paths, artisan commands and rule files in backticks, and nothing checks that they still exist. The conversion renames and deletes code all the time (no backward compatibility), so a reference goes stale silently, and an agent that trusts it looks for a missing class or builds on a removed one.
- **Fix (proposed 2026-11-19):** an architecture test that collects the backtick references of these files (paths under `app/`, `tests/`, `database/`, `routes/`, `config/`, `resources/`, `phpstan/`, `.claude/`, `docs/`, and class names ending in a known suffix such as `Interface`, `Test`, `Rule`, `Exception`) and fails on each one that resolves to no file or class. Struck-through done items and quoted history (commit messages, `Seen` notes) are skipped; a reference that is meant to be gone is written without backticks. It catches renames and deletions, not a rule whose text no longer matches the code.

### No developer documentation of how the system works
- **Where:** `docs/` (only rules, ADRs, PRDs and designs); `AGENTS.md` and `docs/README.md` as the entry points
- **Problem:** the rules say how code is written and the ADRs why something was decided, but nothing describes how the system works: which entities and use cases a context has, where each use case starts, which events it publishes and who reacts, which permissions and limits apply. People and AI agents find this by searching the code, and an agent guesses more when it cannot find the entry point.
- **Fix (proposed 2026-11-07):** one page per bounded context plus a system overview in `docs/`, listed in `docs/README.md`: purpose, entities, main use cases with their application service, events (publisher, reactions), permissions and limits, links to ADRs and PRDs. Code is referenced by class names and paths, never restated in prose; normative rules stay in `.claude/rules/` and are only linked. The test of "Documentation can quote code that no longer exists" covers these pages, so a rename fails the suite instead of leaving the page wrong. Start with the User context (entity limits) and check in a fresh agent session that a task in that context reads fewer files; end-user documentation of the frontend is a separate audience and not part of this item.

## Step 12: Issued invoices

### Issued invoices can be changed and deleted
- **Where:** `UpdateInvoice`, frontend `InvoiceController` (edit, update, set-template, delete), `Admin\InvoiceCrudController` and `Admin\InvoiceRequest`, `InvoicePrintDataBuilder` (reads stored document parties), `InvoicePdfRenderer`, `app/Console/Commands/DatabaseArchiveCommand.php` and `archive_policies` (36 months), `Money` (no negative amounts)
- **Problem:** an issued invoice is a tax document kept unchanged for 10 years (§ 34 and § 35 ZDPH; corrections through a corrective tax document, § 42 and § 45 ZDPH; § 35 ZÚ; to confirm with an accountant after the 2025 amendment). Today an invoice has no draft/issued state (`draft` is a payment status), can be edited in the frontend and the admin and is deleted for good (no `deleted_at`); the document remains editable after issue. Step 7 stores independent party, account and supplier-logo copies, and the admin item editor computes totals from the lines; the complete document still is not frozen and the PDF is rendered again on every download. `db:archive` moves invoices and items older than 36 months out of the main tables; businesses and contacts have no automatic erasure policy.
- **Fix (decided 2026-10-23, own branch):** a document life cycle separate from the payment status (draft → issued → cancelled by a corrective document); issuing assigns the number, stores a snapshot of the parties, bank account, lines, totals, currency, payment method, template and logo, and stores the rendered PDF with its hash, which later downloads return. The Domain refuses changes to an issued invoice; only its payment status changes. Corrections and cancellations are corrective tax documents (credit or debit note) linked to the invoice, with their own PDF; a cancellation can copy the invoice into a new draft. The statistics, which today sum every invoice as revenue, then leave out drafts and cancelled documents. The admin shows the amount read-only. Invoices, corrective documents and their PDFs are kept for 10 years while the account exists (archiving them is the user's duty; "Erasing a user account"); who issued or cancelled a document and when is recorded. Open decisions: numbering at issue, a number series for corrective documents, negative `Money` or a sign by document type, existing invoices, VAT payers vs non-payers; deleting drafts (the uncalled `delete()`, `getForEdit()`, `softDelete()` and `restore()` of `InvoiceCrudApplicationService` were deleted in commit "Delete unused invoice code", branch `ls-dead-code`). Decided 2026-10-26 for step 7's party work: step 7 stores the party and account data on the invoice already; an issued invoice changes nothing, not even its `contact_id`, so "Save to contacts" and "Link to an existing contact" (step 7 offers them on every invoice without a contact) stay only on drafts; `db:archive` never deletes businesses or contacts (step 7 removes the `clients` / `suppliers` archive policies and adds none).

### Invoice numbering ignores the requested year
- **Where:** `app/Domain/Invoice/ValueObjects/InvoiceNumber.php::generateNext()`, `GuestInvoiceCreateForm`.
- **Problem:** `GetInvoiceCreateForm` takes the year from `ClockInterface` and `InvoiceService::generateInvoiceNumber()` queries that year, but the value object reads the system year again through `date('Y')`, and the guest form calls it without a year at all. Around the year boundary the query and the number can disagree, and an injected clock cannot control the number (`rules/domain.md`, Time).
- **Fix:** with issue-time numbering, resolve the year once in the use case (honour an explicit year; derive current time through `ClockInterface`) and pass the same year to the repository query and pure number generator. Cover a requested year different from the system year and a year rollover with behaviour tests.

### Invoice pages read the Eloquent model
- **Where:** `InvoiceFormDTO` (`GetInvoice`, `GetInvoiceForEdit`, `GetInvoiceCreateForm`, `GuestInvoiceCreateForm`), `Application\Invoice\Contracts\InvoiceReadRepositoryInterface::find()`, `frontend/invoices/show.blade.php` and `edit.blade.php`
- **Problem:** the show and edit pages get an untyped array with the Eloquent `Invoice` inside, so Application and the views receive a model (`rules/architecture.md`, Persistence path). Phase 5a moved the checks into the Queries and kept the pages as they were (decided 2026-11-12). `GetInvoice` therefore reads the invoice twice, as the model for the page and as `InvoiceDTO` for the parties.
- **Fix:** with the document life cycle, typed page DTOs built from `InvoiceDTO` and the snapshot, and the Application read port deleted.

### Invoice status is stored as a reference to a statuses row
- **Where:** `invoices.payment_status_id` (`2026_10_25_000008_create_invoice_tables.php`), `App\Models\Invoice::paymentStatus()`, `EloquentInvoiceDtoRepository::statusId()` and `setStatus()` (`MarkInvoicePaid`), the list filters and sorting, `EloquentApiStatisticsRepository`, `DatabaseArchiveCommand`
- **Problem:** the invoice status is the `InvoiceStatus` enum, but it is stored as a foreign key to a `statuses` row that the invoice and expense statuses share; the repository and the status actions look the id up by slug, and the lists and statistics join `statuses`. Enum-backed values are stored as strings (`rules/persistence.md`, Migrations and schema).
- **Fix:** with the document life cycle ("Issued invoices can be changed and deleted"), a string column `invoices.status` with the enum value, filled from the slug by a migration; the reads map it to the enum in the mapper, and the invoice statuses leave the `statuses` table.

### Invoice numbers are not unique per user and year
- **Where:** `invoices` (only the index `idx_invoices_user_invoice_vs`), `InvoiceWriteDataInput`, `InvoiceService`
- **Problem:** the forms accept only numbers in the `InvoiceNumber` format, but nothing refuses a number the user already used in the same year, neither the requests nor the database; two invoices can carry the same variable symbol (`rules/domain.md`, Invoices).
- **Fix:** with issue-time numbering: a unique index on the owner and the number, and a domain exception for a used number that the exception handler shows as a validation error, with a test of both forms.

### Validate the variable symbol in QR payments
- **Where:** `app/Domain/Payment/DTO/QrPaymentDTO.php`
- **Problem:** the variable symbol is not validated (Czech QR standard: max 10 digits); constant and specific symbols are not supported.
- **Fix:** a `VariableSymbol` value object in the payload; add CS/SS if needed.

### PDF templates are plain strings outside the write path
- **Where:** `InvoiceDTO::$template`, `InvoiceDtoWriteRepositoryInterface::setTemplate()` (`SetInvoiceTemplateHandler` passes the enum's value), `InvoicePdfRenderer::getTemplateName()`, `TemporaryInvoicePrintDataFactory`
- **Problem:** the forms, `InvoiceWriteData` and `SetInvoiceTemplateRequest` and `SetInvoiceTemplate` use the `InvoiceTemplate` enum, but the repository and printing pass and compare the names as strings (`rules/php.md`, Types).
- **Fix:** `InvoiceTemplate` in the DTO, the template action and the renderer, validated with `Rule::enum`.

### Products cannot be archived
- **Where:** `products`, `ProductService::deleteProduct()` and the restricting foreign key from `invoice_products`.
- **Problem:** products used on invoices cannot leave new selections while remaining identifiable on existing documents.
- **Fix:** product archiving is part of step 12; products are a capacity limit (D-047), so archiving will free a place as deleting does now. Business/contact archiving and restore are implemented in step 7: archived records leave new selections but remain in history and analytics; archive frees capacity, restore requires capacity, and archiving the last business leaves no default. The Czech identity constraint continues to cover archived records.

## Step 13: Expenses as received documents

### Expenses are not modelled as received documents
- **Where:** `expenses` (`2026_10_25_000009_create_expense_tables.php`), `App\Models\Expense`, `ExpenseRequest` (frontend and admin), `Admin\ExpenseCrudController`, expense statuses in `ReferenceDataSeeder`
- **Problem:** Billex is for VAT payers and non-payers alike, but an expense is one amount with an optional `tax_amount` and a `tax_included` flag. It has no document type, no VAT breakdown by rate, no taxable supply date (DUZP) or date of receipt (which decides the VAT deduction), no supplier's document number and no due date; its statuses (draft, in review, approved, rejected…) are an approval process for companies, not the payment state a sole trader needs. A document the user claims a VAT deduction from is kept for 10 years (§ 35 ZDPH).
- **Fix (decided 2026-10-25, after "Issued invoices can be changed and deleted"):** an expense is a received document, stored as `docs/adr/D-044-document-tables.md` describes: a document type enum (received invoice and simplified tax document / receipt first; corrective document, advance invoice and the tax document for a received payment later as new cases), a VAT regime enum (standard first; reverse charge and acquisition from the EU later), VAT lines by rate shared with issued documents (a non-payer enters only the total), supplier's document number, issue date, DUZP, date of receipt, due date, attachment; a payment state (unpaid, paid) instead of the approval statuses; a VAT payer flag on Business (location decided 2026-10-26 in the party PRD, not on the login account). The user still deletes an expense: it is their record of someone else's document. Every table or column only one document type needs is decided by the user (criteria in the ADR). Minimal current-expense party adaptation in step 7 is approved in `docs/prd/PRD_BUSINESSES_AND_CONTACTS.md`: own business plus contact or one-off supplier, stored party/account data and matching forms/filters. Existing statuses and calculations stay until this full document model is delivered.

### Status labels are translated in the model
- **Where:** `App\Models\Status::getTranslatedNameAttribute()`
- **Problem:** invoice statuses are the `InvoiceStatus` enum, expense statuses plain slugs in the `statuses` table: the expense counterpart `StatusCode` and the translation in `StatusService` had no caller and were deleted (branch `ls-dead-code`). The model accessor translates the slug itself, with a fallback to the stored name.
- **Fix:** labels only from `statuses.statuses.<slug>` (every case has the key), resolved by one presenter used by the invoice mapper and the dashboard instead of the model. The expense statuses change with "Expenses are not modelled as received documents".

### Expense detail has no attachment links
- **Where:** `app/Http/Controllers/Admin/ExpenseCrudController.php` (`setupShowOperation()`)
- **Problem:** the detail reuses the list column, which prints attachment file names joined by commas and cut to 50 characters, with no way to open a file. A renderer with links was present only as commented-out code since the first commit (2025-05-28) and was removed.
- **Fix:** a show column listing each attachment as a link (file names escaped, URLs from the storage disk, not concatenated paths), with an HTTP test of the detail page.

### VAT rates by the supplier's country
- **Where:** `ReferenceDataSeeder::VAT_RATES`, `InvoiceRequest` (allowed item rates), invoice and product forms
- **Problem:** the reference data creates the Czech rates (0, 12 and 21 %) for every supplier; a supplier from another country would need its own rates.
- **Fix:** decide with an accountant whether the rates depend on the supplier's country; until then Czech rates only.

### Expense amounts that Money cannot read end with a 500
- **Where:** `app/Http/Requests/Admin/ExpenseRequest.php` (`amount`, `tax_amount`)
- **Problem:** `numeric|decimal:0,2` accepts `.50` and `+50`, but `Money::fromString()` reads only plain decimals and throws, so the admin expense store and update answer 500 instead of a validation error. Found 2026-11-16 with the same fix in the admin subscription request (phase 6a of step 11).
- **Fix:** the amount rule the subscription request uses (`regex:/^\d+(\.\d{1,2})?$/`), with an HTTP test of the refused formats.

## Step 14: Sending invoices

### Invoices cannot be sent by email
- **Where:** frontend invoice detail; only `app/Console/Commands/CheckInvoicePaymentStatus.php` sends invoice email (payment reminders)
- **Problem:** the user downloads the PDF and sends it themselves. The party PRD (`docs/prd/PRD_BUSINESSES_AND_CONTACTS.md`, rule 10.3) already decides how the recipient works, but there is no sending to apply it to.
- **Fix:** a "send by email" use case that sends the stored PDF to the invoice's `customer_email`; a recipient entered while sending is not saved to the contact unless the user explicitly chooses to and may update the contact.

## Step 15: Exchange rates

### Currency conversion of invoice items happens in the browser
- **Where:** `resources/js/currency-manager.js` (`getExchangeRate()`, `convertAmount()`), `resources/js/invoice-item-manager.js`, `app/Domain/Shared/Money/Services/CurrencyExchangeService.php`, `InvoiceRequestAssembler`, `/api/currencies/{exchange-rate,convert}`
- **Problem:** the invoice form converts item prices in JavaScript with floats; the server checks only that every item is in the invoice currency (`InvoiceRequest`), stores the converted price as it arrives and keeps no rate or date with the invoice. The form also previews line and invoice totals in JavaScript floats; the stored amounts are computed on the server (`InvoiceTotals`). Rates come from the free ExchangeRate-API (`open.er-api.com`); when it fails, the service silently returns rates hardcoded in the class (EUR→CZK 25.29 against 24.27 on 2026-10-23), and when a rate is missing the browser uses 1:1. VAT in CZK on a foreign-currency invoice needs the ČNB rate (§ 4 odst. 5 ZDPH; to confirm with an accountant). `Money::convertTo()` exists but nothing calls it.
- **Fix (decided 2026-10-23, own branch after the issued-invoice immutability):** ČNB daily rates behind an exchange rate port; conversion on the server through `Money::convertTo()` when the invoice is stored; the rate, its date and source stored with the invoice and printed on it; an unavailable rate is an error shown to the user, never a fallback table or 1:1; `/api/currencies/convert` becomes the form's conversion or is deleted.

### Resilience of external calls
- **Where:** `app/Infrastructure/Shared/Http/Services/HttpClientService.php`, `config/analytics.php`
- **Problem:** exchange rates and countries go through `HttpClientService` without timeouts. ARES has its own gateway with a timeout (`AresCompanyRegistry`).
- **Fix:** timeouts and a circuit breaker in `Shared` around `HttpClientService`.

## Step 16: Statistics by currency

### Dashboard statistics sum amounts across currencies
- **Where:** `app/Infrastructure/Persistence/Eloquent/Analytics/Repositories/EloquentApiStatisticsRepository.php`, `app/Application/Analytics/Contracts/ApiStatisticsRepository.php`, the chart Queries in `app/Application/Analytics/Queries`, `app/Http/Controllers/Api/StatisticsController.php`, `resources/js/dashboard-statistics.js`
- **Problem:** `SUM(invoices.payment_amount)` and `SUM(expenses.amount)` add invoices and expenses in different currencies into one number. The port returns `list<array<string, mixed>>` instead of DTOs, lives in Application without the `Interface` suffix, and the controller returns arrays without a `JsonResource`. A failed request leaves a chart empty with only a console error, in all five charts.
- **Fix:** sums per currency as `Money` in statistics DTOs, a Domain port `StatisticsReadRepositoryInterface` returning them to the chart Queries, resources for the responses; the charts show one series per currency or convert through ČNB rates (decide with "Currency conversion of invoice items happens in the browser"). A translated empty and error state in `dashboard-statistics.js`.

### Analytics still uses untyped projections
- **Where:** API chart repositories and `StatisticsController`
- **Problem:** these paths predate the current standard: SQL row arrays and ad-hoc JSON, presentation in model accessors and broad catches remain.
- **Fix:** chart DTOs, domain read ports, presenters and JsonResources. Dashboard reads already use typed ids, DTOs and Money. The inherited CZK aggregate meaning without currency conversion still needs an explicit multi-currency analytics decision.

### Admin dashboard has no charts
- **Where:** the admin dashboard (`GetAdminDashboard`, `AdminDashboardPresenter`)
- **Problem:** the admin dashboard shows counts, revenue per currency and user activity only as numbers; the user dashboard has charts, the admin one has none. An idea, not a defect (2026-11-18).
- **Fix (proposed, low priority):** take it up only together with another change of the admin dashboard or of the charts in this step: charts of the same statistics over time, built on the per-currency statistics DTOs of "Dashboard statistics sum amounts across currencies".

## Step 17: Test speed

### Test runs and CI are slower than they need to be
- **Where:** `tests/TestCase.php` (`RefreshDatabase`), `phpunit.xml`
- **Problem:** every parallel process migrates and seeds before its first test: 12–14 s each with 12 processes, about 170 s of the 663 s the 2986 tests take together (2026-10-25, branch `ls-unit-tests`); the run takes 0:57. Migrating is about 6 s of it, seeding the rest.
- **Fix:** `php artisan schema:dump` would save the migrating part (about 6 s of the run) but needs the `mysql` client in `app.dockerfile` and in CI and a new dump after every migration; decided 2026-10-25 to measure again after steps 12 and 13, when the invoice and document migrations are in.

## Step 18: Payments

### Plan currencies are not checked against the gateway
- **Where:** `SubscriptionRequest`, `SubscriptionPlanRequest` (`Rule::enum(Currency::class)` limited to CZK, EUR and USD), the currency options of `SubscriptionCrudController` and `SubscriptionPlanCrudController`, `GoPayGateway::getSupportedCurrencies()` (CZK and EUR)
- **Problem:** the admin can sell a plan in USD, which the gateway does not declare, and nothing checks it before the payment reaches GoPay; the requests and the two admin forms keep the same list in four places (seen 2026-11-19).
- **Fix:** decide which currencies the plans are sold in (what the GoPay account accepts) and keep that list once, in the gateway: `PaymentGatewayInterface::getSupportedCurrencies()` returns `list<Currency>`, the two requests validate the plan and subscription currency against it and the admin forms take their options from it.

### GoPay is called inside the database transaction
- **Where:** `app/Domain/Payment/Services/SubscriptionPaymentService.php::processSubscriptionPayment()`
- **Problem:** the transaction wraps `createPayment()` of the gateway. When a later step fails, the rollback deletes the local payment record, but the payment created at GoPay stays and its notification finds no record; the transaction also holds its locks for the whole HTTP call.
- **Fix:** store the pending payment in one transaction, call the gateway outside it, then store the gateway id and status in a second transaction, and only then redirect the customer to the gateway; a failed gateway call marks the payment failed. Move the use case to Application (`rules/application.md`).

### A refused subscription payment ends with a 500
- **Where:** `app/Application/Payment/Commands/SubscribeHandler.php`, `app/Domain/Payment/Services/SubscriptionPaymentService.php::processSubscriptionPayment()`
- **Problem:** when the gateway refuses a payment, the service has already stored the `payments` row, and the handler then deletes the subscription. A subscription with payments is not deleted (`SubscriptionInUseException`), so the user gets a 500 instead of the `payment_failed` message and the pending subscription stays. A gateway that answers success without a redirect URL leaves the pending subscription behind too: the customer sees `payment_failed` and every retry adds one more.
- **Fix:** decide what a failed attempt leaves behind (the payment row records it) and do it in `Subscribe` for both cases; a test of the refused path with a stored payment, which the fake payment service skips today.

### A customer can start several pending subscriptions
- **Where:** `app/Application/Payment/Commands/SubscribeHandler.php`
- **Problem:** the check looks only for an active subscription and locks nothing. A double click or two tabs create two pending subscriptions with two gateway payments; when both are paid, both become active and the customer pays twice.
- **Fix:** refuse a new subscription while the user has a pending or active one, checked under a lock of the user's subscriptions (`lockForUpdate`) in the same transaction as the insert.

### A cancelled subscription can be activated by its payment
- **Where:** `app/Application/Payment/Commands/CancelSubscriptionHandler.php`, `SubscriptionPaymentService::cancelSubscriptionPayment()` and `handlePaymentCallback()`, `SubscriptionDtoWriteRepositoryInterface::activateById()`
- **Problem:** the result of cancelling the pending payment at the gateway is ignored and the user always sees "cancelled". When the gateway refuses the cancel and the customer then pays, the callback activates the subscription without checking its status, so the cancelled subscription becomes active and the customer is charged. `cancel()` does not check the status either: cancelling an expired subscription rewrites it to cancelled.
- **Fix:** activate only a pending subscription; cancel only a pending or active one and refuse the others; a refused gateway cancel ends with `subscriptions.messages.cancellation_failed` (deleted in phase 6a of step 11, add it back with `add-translation`) and the subscription stays as it was.

### An inactive plan can be subscribed to
- **Where:** `app/Application/Payment/Commands/SubscribeHandler.php`, `SubscriptionPlanReadRepositoryInterface::findById()`
- **Problem:** the plans page hides inactive plans, but `POST /{locale}/subscribe/{plan}` with the id of one creates a pending subscription at its old price and starts a payment.
- **Fix:** `Subscribe` answers an inactive plan like a missing one (`SubscriptionPlanNotFoundException`), with a test.

### The admin sets a subscription's status by hand
- **Where:** `app/Http/Controllers/Admin/SubscriptionCrudController.php` (status field), `CreateSubscription`, `UpdateSubscription`, `EloquentSubscriptionDtoWriteRepository::fill()`
- **Problem:** the admin form writes any status, active included, without the plan role and without checking for another active subscription. `rules/payments.md` allows only the domain operation to activate, renew or expire a subscription and to set the plan role with it. The user can end up with two active subscriptions (`findActive()` then picks either) and with a plan role that does not match the subscription.
- **Fix:** the admin form shows the status read-only; activating, cancelling and expiring are admin actions that run the domain operation (with step 19, which builds the plan roles).

### Payment DTOs do not match the model casts
- **Where:** `app/Domain/Payment/DTO/PaymentDTO.php`, `app/Domain/Payment/DTO/PaymentWriteData.php`, `app/Models/Payment.php`
- **Problem:** the model casts `gateway_data` to an array, but both DTOs type it `?string`: mapping a payment that holds gateway data throws a `TypeError`. Found while adding Larastan (2026-10-23). `SubscriptionDTO` no longer carries the subscription's `metadata`, which nothing read (phase 6a of step 11).
- **Fix:** type the gateway data in the DTOs (a typed value or `array<string, mixed>`), with a repository test that stores and reads them back.

### Payment adapter guard checks nothing
- **Where:** `app/Console/Commands/PaymentAdapterGuardCommand.php`, `app/Domain/Payment/Services/GatewayRegistry.php`
- **Problem:** `php artisan payment:adapter-guard` reports "No gateways registered" in the console, so the CI step passes without validating any gateway.
- **Fix:** register the gateways (GoPay) where the guard can see them, or let the guard resolve the registry the same way the application does.

### Payment method request does not match the table
- **Where:** `app/Http/Requests/Admin/PaymentMethodRequest.php`, table `payment_methods`
- **Problem:** the request validates `description` and `is_active`, which the table does not have, and leaves `country`, `currency` and `icon` unvalidated although the admin form edits them.
- **Fix:** validate the columns the form sends (`country` as an ISO code, `currency` through the `Currency` enum) and drop the rules for missing columns.

## Step 19: Subscription plans

### Plan roles are not built
- **Where:** roles `frontend_user` and `frontend_user_plus`, `ReferenceDataSeeder::ROLE_LIMITS`, `DevelopmentDataSeeder`
- **Problem:** the frontend roles do not match the plans. Decided 2026-10-25: `frontend_user` after registration without a subscription, with the lowest limits; `frontend_user_s1` replaces `frontend_user_plus` for the cheaper plan; `frontend_user_s2` for the dearer one; perhaps `frontend_user_s3` later. The limit values are to be decided again.
- **Fix:** the roles with their permissions and their `role_limits` values; `subscription_plans.role_id`, activation assigns the plan's role and the end of a subscription `frontend_user`, both setting the role assignment time (D-047 point 10); the plan pages generate the limit lines from `role_limits` and the manual features lose any limit values (PRD Entity limits 4.6, A15–A16); the development seeder switches from `frontend_user_plus`. Assigning the role on activation and on the end of a subscription is the first domain handler (inside the payment's transaction, `architecture.md` Events): the dispatcher for domain handlers was deleted with the usage recording in step 10 and comes back with this handler.

### A manual plan role change is not atomic
- **Where:** `Admin\UserCrudController` (`store`, `update`), `ManageUserPermissions` (`assign-role`, `remove-role`), `PlanRoleApplicationService::recordIfChanged`
- **Problem:** the role changes first and `recordPlanRoleAssignment` runs afterwards, as a separate write outside one transaction and outside the owner's lock. If the process dies between the two writes, the new role applies but the quota window does not restart (D-047 point 2). A limited record created in the gap counts against the old window, which is harmless. Found in the review of PR #77.
- **Fix:** one application service method that changes the plan role and records the assignment time in one transaction through `TransactionBoundaryInterface`, under the owner's lock, used by subscription activation, the end of a subscription, the admin form and the console command. The admin form then has to stop saving roles through Backpack's `traitUpdate`.

### Typed GoPay request with real customer data
- **Where:** `app/Infrastructure/Providers/Payment/GoPayGateway.php`, `app/Domain/Payment/Services/SubscriptionPaymentService.php`
- **Problem:** `createPayment(array $paymentData)` is untyped and the amount is not checked against gateway limits; a `PaymentAmount` value object for the limits had no caller and was deleted with its `payment:limits` report (commit "Delete unused payment code", branch `ls-dead-code`). `SubscriptionPaymentService` also receives amounts in an array and casts the subscription amount to float. Without customer data the gateway sends a placeholder customer (`Test User`, `test@example.com`); `SubscriptionController` passes none.
- **Fix:** build `PaymentAmount` (`Money` checked against the gateway's limits) and a request DTO (amount as `PaymentAmount`, plan, customer, URLs) filled with the subscriber's real data.

### Subscription screens with placeholder content
- **Where:** `resources/views/frontend/subscriptions/{index,show,my-subscription}.blade.php`, `app/Http/Controllers/Frontend/SubscriptionController.php` (`index`, `show`, `mySubscription`), `app/Http/Controllers/Admin/SubscriptionCrudController.php`
- **Problem:** the checkout asks to agree to the terms of service and the privacy policy, but both links point to `#`. Every yearly plan carries a fixed "2 months free" badge, whatever its price. The admin list prints every amount with a "CZK" suffix, also for EUR and USD subscriptions. The plan pages read `SubscriptionPlan` models in the controller, and "My subscription" loads the subscription model with its plan, features and payments by the id `FindActiveSubscription` returned, formatting amounts and dates in Blade (kept so in phase 6a of step 11, decided 2026-11-14; `rules/architecture.md`, Persistence path).
- **Fix:** link real terms and privacy pages (Content), compute the saving from the monthly and yearly prices or drop the badge, format amounts with the subscription's currency through `MoneyFormatterInterface`; typed page DTOs for the plans and "My subscription" (plan, features, payment history as `Money` and dates) from Payment Queries, rendered through formatters.

## Step 20: System admin screens

### Cron task schedule computed in the model
- **Where:** `app/Models/CronTask.php` (`getCronExpression()`, `getNextRunDate()`), `app/Console/Commands/TestCronExpression.php`, `routes/console.php`
- **Problem:** the model computes the cron expression from `frequency` compared with string literals, with Czech comments and a broad catch returning `null`; `TestCronExpression` repeats it, and the scheduler builds the same schedule a third time from `CronTaskDTO`.
- **Fix:** one schedule definition in the System Domain (expression from `CronTaskDTO`), used by the scheduler, the list column and `TestCronExpression`.

### Cron task output grows without limit
- **Where:** `routes/console.php` (`appendOutputTo()` and the after-callback), `last_output` of `cron_tasks`
- **Problem:** each run appends to `storage/logs/cron-{id}.log`, which is never truncated, and the after-callback stores the whole file in `last_output` (`TEXT`, 64 KB). Once the log outgrows the column, MySQL strict mode refuses the update and `last_run` stops changing.
- **Fix:** store only the output of the last run (`sendOutputTo()`, or the run's own output as `RunCronTask` does), and rotate or drop the cumulative log.

### Cron task form ignores `depends`
- **Where:** `app/Http/Controllers/Admin/CronTaskCrudController.php` (`run_at`, `day_of_week`, `day_of_month`, `custom_expression`)
- **Problem:** `CrudField::__call()` keeps only its first argument, so `depends('frequency', 'weekly')` stores `frequency` and drops the value, and no field view reads the attribute. Every schedule field shows for every frequency.
- **Fix:** show the fields by the selected frequency through Backpack's CrudField JavaScript or an own field, then delete the calls.

### Archive policy form does not match the table
- **Where:** `app/Http/Controllers/Admin/ArchivePolicyCrudController.php`, `App\Models\ArchivePolicy`, `ArchivePolicyRequest`
- **Problem:** the list, detail and form use `is_active` and `description`, but the table has `enabled` and no description, and the model does not fill either. The screen shows every policy as inactive and without a description, and a save silently drops what the admin entered, so a policy cannot be switched off here.
- **Fix:** use `enabled` throughout, and either add `description` by migration or remove it from the screen and the translations.

### Tax form offers a description the table cannot store
- **Where:** `app/Infrastructure/Forms/Tax/TaxFormFields.php`, the `description` column in `app/Http/Controllers/Admin/TaxCrudController.php`
- **Problem:** the form and the list show `description`, but `taxes` has no such column, so what the admin types is dropped without a message and the column stays empty.
- **Fix:** add the column through a migration, or remove the field and the list column.

### Admin user form accepts a malformed email
- **Where:** `app/Http/Controllers/Admin/UserCrudController.php`, `Backpack\PermissionManager\app\Http\Requests\UserStoreCrudRequest`
- **Problem:** the controller validates through the package's requests, whose store rule for `email` is only `required|unique`, so `not-an-email` is stored as a user's address and that user cannot receive any mail.
- **Fix:** add the admin form request `App\Http\Requests\Admin\UserRequest` with an `email` rule and validate through it (`rules/admin.md`, `rules/http.md`).

### Admin list columns build display text by hand
- **Where:** `app/Http/Controllers/Admin/{PerformanceMetric,DatabaseMaintenanceLog}CrudController.php`; badge HTML and classes in `Status::getColorPreviewAttribute()` and `UserActivitySummary::getActivityBadgeClassAttribute()`; hardcoded labels also in the `Product`, `Invoice`, `Status`, `Subscription` and `ExpenseCategory` CRUD controllers
- **Problem:** amounts and sizes are formatted with `'€' . number_format(...)` and private `formatBytes()` helpers, ignoring the locale and the entity's currency. Labels and cell texts are hardcoded English (`->label('User')`, `'Anonymous'`, `'Pending'`). The maintenance results and performance metadata closures put MySQL output (`Msg_text`, storage engine, metadata values) into HTML unescaped. Models return badge HTML (`getColorPreviewAttribute()`).
- **Fix:** amounts through `MoneyFormatterInterface`, labels and texts as translation keys, every interpolated value through `e()` or a Blade column view, badge HTML out of the models into column views with the status as an enum (`rules/admin.md`, `rules/persistence.md`, `rules/translations.md`).

### Database dashboard shows invented metrics
- **Where:** `app/Http/Controllers/Admin/DatabaseDashboardController.php` (`generateSampleDataForMetricType()`, cleanup type `sample`, the `sample_metrics` count), `app/Console/Commands/CleanPerformanceMetrics.php`, `resources/views/admin/database/dashboard.blade.php`
- **Problem:** when no metric of a type is stored, the performance chart shows random values labelled as sample data. The `sample` cleanup and the `sample_metrics` count look for rows marked `metadata.generated`, which nothing writes since the unrouted sample generator was deleted (branch `ls-dead-code`).
- **Fix:** an empty state instead of random values; delete the `sample` cleanup type, the count and their translations. The dashboard's actions become use cases of the System context, so `DatabaseDashboardRequest` loses its `authorize()`, which asks the gate's `holds()` for `system.configure` until then; its five actions without a route are deleted.

### Maintenance commands query the database directly
- **Where:** `DatabaseMonitorCommand`, `DatabaseArchiveCommand`
- **Problem:** direct maintenance queries, SQL row arrays and broad catches.
- **Fix:** a maintenance application service over infrastructure ports.

### Admin interface has no Slovak texts
- **Where:** `vendor/backpack/crud/src/resources/lang` (no `sk`), admin locales in `config/backpack/crud.php` (`cs`, `de`, `sk`)
- **Problem:** the admin offers Slovak, but Backpack itself ships no Slovak texts, so buttons, pagination, filters and messages of every admin screen stay English in `sk`. Only the role and permission screens are covered, by `lang/vendor/backpack/sk/permissionmanager.php`.
- **Fix:** add `lang/vendor/backpack/sk/{base,crud}.php` with the keys of the package's `en` files, checked against the package after each Backpack upgrade; or drop Slovak from the admin locales.

## Step 21: Conversion to the standard

The code standard is defined in `.claude/rules/` (decided 2026-10-20). Each item below is a gap between that standard and the code; convert one bounded context at a time with `/convert-to-standard`, in the order of their dependencies: Shared, User, Party, Product, Invoice, Expense, Payment, Analytics, Content. A context conversion takes every item of this section that applies to it and the context's own items here, and raises the Larastan level as far as the converted code allows (step 23). The application layer was converted to use cases in step 11 (the permission gate, finished 2026-11-18 in branch `ls-system-gate`).

### PHP syntax and readability rollout
- **Where:** project PHP, maintained PHP examples and authored PHP expressions in Blade; enforcement in `phpcs.xml`, `pint.json` and `phpcs/Billex/`.
- **Status:** done for formatting, call chains and complex ternaries (2026-11-06). Mechanical formatting is complete across all standalone project PHP covered by the current PHP_CodeSniffer configuration, including application code, tests, migrations, routes, configuration and tooling. Significant-token comparisons preserve expressions, comments and literals, allowing only layout and trailing commas; repeated formatting passes are stable. `Billex.Functions.DisallowCallChaining` and `Billex.ControlStructures.ShortTernary` report nothing in standalone project PHP; `database/migrations` keeps only its `Blueprint` chains, which `phpcs.xml` excludes from the sniff. Raw Blade templates stay excluded from PHP_CodeSniffer; their PHP is covered through the compiled form by `BladeExpressionStyleTest`.
- **Arrow arguments:** `ComplexCallLayoutSniff` now retains the comma terminating an arrow function when locating following arguments. Rejection and fixer/convergence regressions cover typed, nested and named arrows, their siblings and internal commas. The observed call in `ProductWriteData::toArray()` remains correctly laid out. Subsequent mechanical inventories must use this corrected detector.
- **Call argument counting:** the existing Billex call-layout rule now enforces both the three-argument threshold and complex arguments through the same structural traversal. It replaces Slevomat `RequireMultiLineCall`, whose counting treated commas in match arms, closure bodies and anonymous-class bodies as argument separators. Regression coverage pins accepted brace contents, three real arguments, dynamic calls and constructors, including `self`, `parent`, `static` and anonymous classes. Pint preserves multiline anonymous-class constructor arguments, avoiding a conflict with its class-header formatter. No suppression or vendor edit is used.
- **Multiline condition enforcement:** repaired to inspect operator and grouping positions rather than total line counts, excluding boolean expressions owned by call arguments or other operand bodies. Regressions cover nested grouping, multiline calls, comments and PHPCBF → Pint convergence. All newly visible condition layouts have completed mechanical formatting.
- **Controlled test factory conversion:** supported `App\Models\…::factory()` chains in direct method statements now use named factory variables, retain the return of each state operation and preserve the terminal `create()`/`make()` result.
- **Controlled HTTP test conversion:** supported direct `$this` chains of client setup, an HTTP request and response assertions now use named client/response variables and retain each intermediate return. Direct assertion statements on existing response variables also retain each return where straight-line assignments establish the HTTP origin, including client and response aliases. Reassignments invalidate that origin; reference aliases, escaped bindings and uncertain control flow require separate review. Assignments and terminal content/JSON reads keep their original results and evaluation order. This does not change authorization checks or resolve structural-test debt.
- **Controlled test query and Storage conversion:** supported chains in `tests/` that start at `Storage::disk()`, a model query starter or `DB::table()` now use a named receiver. Verified setup calls reuse the builder, every other call retains its return, and different queries never share a builder. The conversion also covers statement lists inside branches, loops and closures, and it moves a chain out of a larger expression only when that expression evaluates nothing with a side effect before it.
- **Controlled test service and upload conversion:** supported chains in `tests/` that start at `app()`, `UploadedFile::fake()` or `$validator->errors()` now use a named receiver under the same execution limits. Every call retains its return.
- **Controlled test helper conversion:** supported chains in `tests/` that start at `now()`, `collect()`, `session()`, `response()`, `auth()`, `config()`, `cache()`, `request()`, `redirect()`, `Route::…` (including the `\Route` alias), `Validator::make()`, `Auth::guard()`, `Cache::tags()`, `Notification::route()`, `Http::fakeSequence()`, `Money::fromString()` or `Role::findByName()` now use a named receiver under the same limits. A root qualifies only if its return has no destructor.
- **Controlled Livewire test conversion:** chains in `tests/` rooted at the `Livewire` facade now use separate facade calls and one `Testable` variable. Chains on an existing `Testable` variable follow the same form, retaining what `instance()` and `viewData()` return.
- **Controlled test `$this` and test double conversion:** chains in `tests/` rooted at `$this`, Mockery and PHPUnit doubles and `QueuedCommand::dispatch()` now use separate statements. Console tests hold their command through `$this->pendingCommand()` and end with `run()`.
- **Controlled test factory conversion, remaining positions:** all chains in `tests/` rooted at a model factory now use named factory variables, including nested arguments, match arms, `??`, arrow functions, loops and data providers. The factory and its states move in front of the statement under the limits in [the enforcement design](design/DESIGN_PHP_STYLE.md); the terminal call stays where it runs.
- **Controlled test conversion of other variables:** chains in `tests/` on variables named by their role now use separate statements: `forceFill()` and `save()` on models, `fresh()`, relations, plucked collections, validator errors, response sessions, Money and date values, connection queries, PDO statements and reflection. `LaravelInvoiceReminderNotifierTest` still reads the public `Notification::$locale` by reflection, where `$notification->locale` would do, and `LaravelInvoiceReminderNotifierTest` and `PermissionCatalogCoverageTest` keep inline fully qualified names (`rules/php.md`).
- **Controlled test query conversion, remaining positions:** chains in `tests/` rooted at a model query starter, `DB::table()`, `Model::on()` or a named scope now use a named builder in front of the statement; the terminal call stays where it runs, so reads keep their order. `PageFeatureTest` still has Czech comments (`rules/php.md`).
- **Controlled test clock conversion:** chains in `tests/` rooted at `now()` now read `$now = CarbonImmutable::now()` in front of the statement, with a bare `now()` of the same statement merged into it, inside its loop iteration, and a `match` arm that needs statements as an `if` in front of the `match`. `InvoiceControllerGuestFeatureTest` keeps an inline `\Exception` (`rules/php.md`), and `SubscriptionPaymentServiceFeatureTest::verify_subscription_payment_requires_valid_payment` asserts only the row it created itself, so it pins no behaviour of the service (`rules/testing.md`).
- **Controlled test upload and Storage conversion:** chains in `tests/` rooted at `UploadedFile::fake()` and `Storage::disk()` now use `$fileFactory` and `$disk`, one per method. Where `uniqid()` or `route()` ran before the chain in the same statement, its value moved in front as well, so the order stays. `PageCrudControllerTest` still has Czech comments (`rules/php.md`).
- **Controlled test helper function conversion:** chains in `tests/` rooted at `app()`, `view()` and `auth()` now use a named receiver.
- **Controlled test static root conversion:** chains in `tests/` rooted at `Http::fakeSequence()`, `Route::middleware()` and `Role::findByName()` now use a named receiver, the role inside its loop iteration. `PermissionCatalogSyncTest` lacks `declare(strict_types=1)` and `final`.
- **Controlled test `new` conversion:** chains in `tests/` rooted at `new Finder` and `new DOMXPath` now use `$finder` and `$xpath`, the `Finder` set up in front of its `foreach`. `PermissionCatalogCoverageTest` still lacks `declare(strict_types=1)` and `final` and writes `new \ReflectionClass` inline (`rules/php.md`).
- **Controlled conversion of `bootstrap/app.php` and the PHPStan providers:** `bootstrap/app.php` now configures one `$builder` and has `declare(strict_types=1)`; `phpstan/DeadCode` reads the declaring class into a variable, still only for a public member. `BladeTemplateUsageProvider` still scans the Blade templates in two places.
- **Controlled conversion of `database/factories` and `database/seeders`:** both directories now use named receivers: `$faker`, `$unique` and `$optional` in factories, `$query` rebuilt per query in seeders, and `PageContentSeeder::categoryId()` for the category lookup. `InvoiceFactory` takes both dates from one `CarbonImmutable::now()`. Most factories and some seeders still lack `declare(strict_types=1)` and `final`, and `InvoiceFactory` has a Czech comment and `ExpenseFactory` an inline `\DateTimeInterface` (`rules/php.md`).
- **Controlled conversion of `app/Domain`, `app/Application`, `app/Livewire` and `app/Models`:** these directories hold no call chains; `PermissionLimitResolver` reads its promoted dependencies directly and lost its private accessors. `$locale` defaults and the `auth()` check in `Page` became nested `if` blocks, which keeps the short circuit. The conversion left the existing violations in place: `app/Application` still uses `app()`, `session()`, facades, `CarbonImmutable` and models (`rules/architecture.md`), `InvoiceNumber` reads `date('Y')`, `ProductList` and `ProductListSelect` build queries, `CronTask` uses inline `\Log` and `\Artisan`, and many of these files lack `declare(strict_types=1)` and `final` (`rules/php.md`).
- **Controlled conversion of `app/Console`, `app/Exceptions`, `app/Providers` and `app/Helpers`:** these directories hold no call chains. `ExceptionHandling` lost its `ModelNotFoundException` callback, which never ran because Laravel's `prepareException()` turns that exception into a 404 before the render callbacks. The conversion left the existing violations in place: `db:archive` opens a transaction with `DB::beginTransaction()` and has no `rollBack()`, so after a failure the `failed` status in `database_maintenance_logs` is written inside the open transaction and lost when the process ends, and its generic path deletes rows of other tables instead of archiving them; console commands use `DB::` and models directly (`rules/architecture.md`); most of these files lack `declare(strict_types=1)` and `final`, and `DatabaseArchiveCommand`, `CleanPerformanceMetrics`, `DatabaseOptimizeCommand` and `TestMailhog` catch `\Exception` and report only its message (`rules/php.md`). Fix `db:archive` with `DB::transaction()` and a regression test.
- **Controlled conversion of `app/Infrastructure`:** this directory holds no call chains. Backpack option closures became closures with statements that keep their returned result, and the complex ternaries holding chains in `InvoicePdfRenderer` and `CacheService` became `if`/`else`. `PaginatorAdapter` lost the `$options` its only caller never passed, `LaravelFileUploadService` names the disk `$storage` because `$disk` holds its name, and `EloquentSubscriptionWriteRepository` takes `starts_at` and `ends_at` from one `CarbonImmutable::now()`. The conversion left the existing violations in place: `EloquentAnalyticsRepository::getUserStats()` sums `payment_amount` across currencies and labels the sum CZK; `EloquentPageRepository::searchPublished()` interpolates `$locale` into `whereRaw()`, which today only the `{locale}` route constraint keeps to the four supported values (`rules/persistence.md`); repositories return models and collections (`rules/architecture.md`); `EloquentPageRepository` keeps commented-out code, and many of these files lack `declare(strict_types=1)` and `final` (`rules/php.md`).
- **Controlled conversion of the frontend and API controllers and `InvoiceTotalsResource`:** `app/Http/Controllers/Frontend`, `app/Http/Controllers/Api` and `app/Http/Resources` hold no call chains. `getLogContext()` keeps one variable per guard and its `check()` calls stay in place, and the `function_exists('backpack_auth')` guards went, since Backpack is a hard dependency. The conversion left the existing violations in place: `ProductController` uses `trans()` (`rules/translations.md`), `SubscriptionController` uses models (`rules/architecture.md`), API controllers build ad-hoc JSON arrays instead of resources (`rules/http.md`).
- **Controlled conversion of `app/Http/Middleware` and `app/Http/Requests`:** these directories hold no call chains. `authorize()` reads `$user = backpack_user(); return $user?->can('x') === true;`, so `StatusRequest` without a signed-in admin answers `false` instead of failing on `null`; the admin middleware keeps that case from reaching it. The three multi-line ternaries holding `Rule::exists()` became `if`/`else`, and the two `trans()` calls on rewritten lines became `__()`. The conversion left the existing violations in place: `RequireBackpackApiAccess` still puts the email into its denial warning, and `PageCategoryRequest::validateAtLeastOneLocale()` writes the submitted names and slugs at debug level; the middleware classes lack `declare(strict_types=1)` and `final`, `ProductRequest` is not `final`, and `SetLocale` catches `\Exception` and logs only its message (`rules/php.md`).
- **Controlled conversion of the admin controllers without CRUD configuration:** `AdminController`, `CacheManagementController`, `DatabaseDashboardController`, `DocumentPartyCrudController` and `InvoiceItemsController` hold no call chains. Each method reads the time once as `CarbonImmutable`, so `getHealthTrend()` no longer needs `copy()`. The conversion left the existing violations in place: `getHealthTrend()` passes grouped collections to `calculateHealthScore()`, which fails on `->metric_value`, so the trend is empty as soon as a day has a metric; `calculateSpaceSavings()` returns no `bytes` for a task without results, and the dashboard view answers 500 on it; `duration_seconds` comes out negative because `diffInSeconds()` is signed; `getDashboardStats()` sums `payment_amount` across currencies. The `table` of the `DatabaseDashboardController` commands is not validated against an allowlist (`rules/http.md`). These controllers query models directly (`rules/http.md`, `rules/architecture.md`), `DatabaseDashboardController` lacks `declare(strict_types=1)` and `final` and uses inline `\Artisan` (`rules/php.md`).
- **Controlled conversion of the CRUD admin controllers, without the CRUD chains:** chains in `app/Http/Controllers/Admin` outside a `CRUD::` chain and its later links now use separate statements, `app()->getLocale()` inside a closure stays in that closure, and `backpack_user()` stays inside its loop iteration. The `CRUD::` chains follow in the screen conversions below. The conversion left the existing violations in place: `trans()` remains in `SubscriptionPlanCrudController` (`rules/translations.md`); `PageCrudController` and `PageCategoryCrudController` call an inline `\Str::limit()` (`rules/php.md`).
- **Controlled conversion of `routes/web.php`, `routes/api.php` and `routes/backpack/custom.php`:** these files hold no call chains and have `declare(strict_types=1)`; the registration order of all routes is unchanged. The conversion left the existing violations in place: `withRouting()` and `App\Providers\RouteServiceProvider` both load the route files, so `routes/web.php` runs three times per boot; `GET /api/user` returns the user directly instead of through a `JsonResource` (`rules/http.md`); `withoutMiddleware(['auth'])` on the three guest routes has no effect, because they are outside the `auth` group; the comment on the guest invoice route is a history note (`rules/php.md`).
- **Controlled conversion of `routes/console.php`:** `routes/` holds no call chains or complex ternaries, and the file has `declare(strict_types=1)`. `run_at` has a `datetime` cast, so the unreachable `is_string()` branches went; a failed load now logs a fixed English message with the exception. The conversion left the existing violations in place: the file reads the `CronTask` model and compares `frequency` with string literals instead of an enum (`rules/architecture.md`, `rules/php.md`); a `run_at` that cannot be parsed stops the loop, so that task stays registered without its output log and the tasks after it are not scheduled.
- **Controlled conversion of `database/migrations`:** queries, permission calls and helpers no longer chain, and the SQL, schema and rows of every step up and down are unchanged. `Blueprint` chains stay and the directory is excluded from the sniff, because Larastan reads model columns from them ([the enforcement design](design/DESIGN_PHP_STYLE.md)). The conversion left the existing violations in place: `2026_10_29_000001_add_expense_operation_permissions` fails with `PermissionDoesNotExist` when a user holds the old `can_create_edit_expense` permission directly, because `givePermissionTo()` on the user resolves the `web` guard while the new permissions belong to `backpack`; a rebuilt database has no such user. Three files define `$table->enum()` columns (`create_entity_limit_tables`, `create_subscription_tables`, `create_database_maintenance_tables`, 8 columns) where `rules/persistence.md` stores enum-backed values as `string`, and three permission migrations have an empty `down()` where the same rule asks for a reversible one.
- **Controlled conversion of the `CRUD::` chains in the system admin screens:** `ArchivePolicy`, `ArtisanCommand`, `ArtisanCommandCategory`, `CronTask`, `DatabaseMaintenanceLog`, `MysqlOptimizationLog` and `PerformanceMetric` CRUD controllers hold no call chains; panel definitions, rendered pages, SQL and stored rows are unchanged apart from the retention suffix, now a translation key. The conversion left the existing violations in place, on lines it did not change: no `declare(strict_types=1)` and non-final controllers apart from `DatabaseMaintenanceLog`, untyped closure parameters in closures passed to `function()`, hardcoded English and `number_format()` in those closures (see [Admin list columns](#admin-list-columns-build-display-text-by-hand)).
- **Controlled conversion of the `CRUD::` chains in the content and user screens:** `Page`, `PageCategory` and `User` CRUD controllers hold no call chains; panel definitions, rendered pages, active filters, SQL and stored rows are unchanged. The conversion left the existing violations in place, on lines it did not change: no `declare(strict_types=1)` and non-final controllers, untyped closure parameters, `\Str::limit` written inline, and hardcoded symbols and `number_format()` in closures (see [Admin list columns](#admin-list-columns-build-display-text-by-hand)).
- **Controlled conversion of the `CRUD::` chains in the party, document, product, status and subscription screens:** `BankAccount`, `Business`, `Contact`, `ConfiguresPartyCrud`, `Expense`, `ExpenseCategory`, `Invoice`, `PaymentMethod`, `Product`, `ProductCategory`, `Status`, `StatusCategory`, `Subscription` and `Tax` hold no call chains; panel definitions, rendered pages, active filters, SQL and stored rows are unchanged, and `app/Http` holds no chain. The commented-out `disk`, `prefix` and `uploadRoute` links of the product image are gone, because they had no statement to stay with.
- **Controlled conversion of the ternaries in developer tools:** the console commands, `config/database.php`, `phpstan/DeadCode` and the architecture and provider tests hold no complex ternary; thresholds, command output, configuration values and dead-code results are unchanged. `mysql` and `mariadb` share one `$mysqlOptions`, and a dead first assignment of `$full` is gone from `FrontendControllersEloquentUsageTest`. The conversion left the existing violations in place: `payment:adapter-guard`, which CI runs, reports "No gateways registered" and exits 0, because only the constructor of `SubscriptionPaymentService` registers GoPay and the command never resolves that service, so it checks nothing; it also requires `verifyPayment`, which `PaymentGatewayInterface` does not declare. These commands and tests are now `final` with `declare(strict_types=1)`, and `config/database.php` declares `strict_types` too.
- **Controlled conversion of the ternaries in the party and admin screens:** the party Livewire components, `BankAccountInput`, the party mappers, `BusinessArchive`, `RequireBackpackAccess` and the `BankAccount`, `ConfiguresPartyCrud` and `DatabaseMaintenanceLog` admin screens hold no complex ternary; each branch runs under its original condition. The conversion left the existing violations in place, on lines it did not change or kept in the same form: `DatabaseMaintenanceLogCrudController` compares `status` with the string `'running'` instead of an enum (`rules/php.md`).
- **Controlled Blade conversion of the current locale:** templates read `App::getLocale()` instead of `app()->getLocale()`; compiled output differs only in this call and in the generated `@once` ids, and the PDF snapshots are unchanged.
- **Controlled Blade conversion of route and permission checks:** `layouts/navigation`, `menu_items` and the admin list buttons read `Request::routeIs()` and one `$user` per template; compiled output differs only in these calls and the assignment, and the HTML of both versions is identical across permissions, routes and guests.
- **Controlled Blade conversion of the remaining chains and ternaries:** authored Blade expressions hold no call chain or complex ternary; the sniffs over the compiled templates report only compiler output (`render`, `@auth`, `@can`, `@js`, `@livewire`). The cancel link of the party forms comes from `BusinessForm` and `ContactForm`. `database/dashboard` no longer has a line break in its date format, which the browser collapsed anyway. Rendered pages of both versions are identical across users, permissions and record states.
- **Blade coverage in the test suite:** `BladeExpressionStyleTest` compiles every template and fails on a chain or complex ternary that the compiler did not write. The rollout's chain and ternary conversion is complete for standalone PHP and Blade; `strict_types`, `final` and the frontend standard in Blade keep their own items.
- **One receiver per method in tests:** the conversions above no longer initialise the same receiver twice in one function scope or number the generated variables. Repeated factories, disks, collections, helpers and error bags merge under the conditions in [the enforcement design](design/DESIGN_PHP_STYLE.md); a second initialisation that is needed reuses the name, or takes a name from context. `$now…` variables hold `CarbonImmutable::now()`. Hand-written numbered names (`$component2`, `$view1`, `$product1`, …) remain in 62 files (2026-11-06). `CRUD::column()`, `CRUD::field()` and `CRUD::filter()` keep the receiver form of [the enforcement design](design/DESIGN_PHP_STYLE.md), without an exception to the rule (decided 2026-11-06).
- **Separate User conversion:** 7 of the 24 files in `app/Domain/User` still lack `declare(strict_types=1)` (2026-11-18); the Application and persistence classes of User are converted. Convert the rest with the User context.
- **Separate Party/Product conversion:** older Product classes lack `strict_types`/`final` and repeat implementation details in docblocks (`rules/php.md`); the repository ports keep the `Dto` infix, `EloquentProductDtoRepository` is not named after the entity, `ProductDTO` has snake_case properties and array relations, and `ProductFormDataService` returns arrays (`rules/persistence.md`, `rules/php.md`). `EloquentProductMapper` supplies a currency default instead of only mapping stored values; Product observers publish cache-related events, and `ProductCategoryObserver` obtains the actor through `auth()` (`rules/architecture.md`, Events). Convert Product to Domain repository ports and typed mapper boundaries, assign defaults in the owning use case/domain input, and publish events from use cases with explicit ownership; move cache invalidation to after-commit listeners.
- **Fix:** the agreed standard in `rules/php.md` and [the enforcement design](design/DESIGN_PHP_STYLE.md). What remains is renaming the hand-written numbered names by context, under the second-initialisation rule of `rules/php.md`. This rollout does not resolve the architectural and typing items below.

### `strict_types` and `final`
- **Where:** all of `app/`, `tests/`, `database/` (505 of 1100 files declare `strict_types`, 411 classes are `final`, 2026-11-06); files touched by a branch keep their old shape, e.g. `app/Http/Controllers/Frontend/ProfileController.php` (neither), `app/Http/Middleware/RequireFrontendApiAccess.php` and `RequireBackpackApiAccess.php` (neither; their checks moved to the gate in `ls-grants`)
- **Fix:** add `declare(strict_types=1)` and `final` (except abstract bases and Eloquent models, `rules/php.md`); the test suite catches the coercions this exposes.

### Transaction boundaries
- **Where:** `SubscriptionPaymentService::processSubscriptionPayment()`, `app/Infrastructure/Persistence/Eloquent/Shared/Database/EloquentTransactionBoundary.php` (not `final`)
- **Problem:** a Domain service opens its own transaction; the gate and the Commands calling outside the database already open theirs through `TransactionBoundaryInterface`.
- **Fix:** per `rules/application.md`: the gate opens one transaction around every Command, and a Command calling outside the database opens it through the port around its writes only; Domain services and repositories open none.

### Two sets of repositories
- **Where:** 19 repository contracts in `app/Application/*/Contracts` returning Eloquent models (e.g. `InvoiceReadRepositoryInterface`), next to the Domain `*Dto*RepositoryInterface` ports
- **Problem:** Application, controllers and Livewire work with models; the same entity has two persistence paths.
- **Fix:** move every use to the Domain ports, delete the Application repositories and their implementations. Then, per `rules/persistence.md` (decided 2026-10-20): ports without `Dto` and always with `Interface` (`ClientReadRepositoryInterface`; today e.g. `BankDtoRepository`); a read port for every read entity, a write port only where the Domain writes (unsplit today: `BankDtoRepository`); one `Eloquent<Entity>Repository` per entity implementing both (merge `EloquentPaymentDto{Read,Write}Repository`, `EloquentSubscriptionDto{Read,Write}Repository`).

### Framework and model dependencies outside Infrastructure
- **Where:** `App\Models` in 10 Application files and 29 of 57 controllers (2026-11-18); `Illuminate\Http` in 1 Application file; facades/helpers in Application files (e.g. `Cache` in `TemporaryInvoiceStore`, whose tests have to boot Laravel); `config()` in `app/Domain/Shared/File/Config/FileUploadConfig.php`; `app/Domain/Payment/Services/SubscriptionPaymentService.php` imports `GoPayGateway`; `app/Application/Invoice/Form/InvoiceCreateFieldSetFactory.php` builds the invoice form from the Infrastructure trait `InvoiceFormFields`, which translates its labels with `__()`
- **Fix:** per `rules/architecture.md` (dependency table).

### Dependencies taken from the container
- **Status:** in progress: since 2026-11-20 `rules/php.md` requires dependencies in the signature, and `ContainerLookupRule` (`phpstan/Rules/`) reports `app()`, `resolve()`, `App::make()`, `App::call()` and `Container::getInstance()` outside `app/Providers`. Every class with an injection point is converted; the files below have none and are listed in `phpstan.neon`.
- **Where:** `Admin\InvoiceRequest::rules()` (Backpack calls `rules()` directly, in `setRequiredFields()` on a request it creates with `new`), the model trait `HasFileUploads` (used by `Product`, `Expense`, `Page`), the queued notifications `InvoiceDueReminder`, `InvoiceOverdueReminder` and `InvoiceUpcomingDueReminder` (Laravel calls `toMail()` directly), `routes/console.php` (the schedule is built when the file loads, outside any container call)
- **Problem:** the dependency is not in the signature: a test has to boot the container instead of passing a fake, and the reader does not see what the code needs.
- **Fix:** the admin invoice request loses its dependencies with "Validation in several places", when the payment method and tax rate checks move out of `rules()`; the model logic moves out of `HasFileUploads` ("Business logic in models", "Product images are stored inside the transaction"); a notification receives prepared data from `LaravelInvoiceReminderNotifier`, which gets `InvoiceReminderMailFactory` injected; the schedule moves to a class the container builds. Each file leaves the list in `phpstan.neon` with its conversion.

### Interfaces without a port
- **Where:** 11 of 12 `*ServiceInterface` contracts in Domain and Application have a single implementation; 1 test file mocks them (2026-11-18)
- **Fix:** delete the interfaces and their bindings, make the services `final`, rewrite the mocking tests to run the real service with fakes at the ports; refresh the provider snapshot.

### Data class names and properties
- **Status:** in progress: the print, QR and reminder DTOs were renamed in step 7; the rest is under "Other conversion work".
- **Where:** `app/*/*/DTO/`, `app/Domain/Shared/Money/Enum`, `app/Domain/Invoice/Notifications/Enums`, `app/Application/Shared/Form/Enum`
- **Problem:** suffixes `DTO`/`Data`/`Payload`/`ResultDTO` mixed; 167 snake_case vs 75 camelCase properties, mixed within one class.
- **Implemented in step 7:** `PrintableInvoiceDTO`, `PrintableInvoiceItemDTO` and `PartyPrintDTO` exist and are used. Their rename is complete.
- **Also implemented in step 7:** `QrPaymentDTO` and `InvoiceReminderDTO` replace the remaining payload names and all consumers. QR carries `BankAccount` and emits decimal amounts without floats; reminders use stored names, typed identity and Money, with regressions across all six variants and four locales.
- **Other conversion work:** `SubscriptionPaymentResultDTO` → `SubscriptionPaymentResult`; replace the untyped `InvoiceMutationPayload` wrapper and the validated array of the guest invoice (`CreateGuestInvoice`, the ad-hoc session keys and JSON of `InvoiceController::storeGuest`, `TemporaryInvoiceStore`, `TemporaryInvoicePrintDataFactory`) with typed data; rename remaining `Enum/` directories to `Enums/`; camelCase properties with `final readonly class`; the `currency` strings of `InvoiceDTO`, `PrintableInvoiceDTO`, `ProductDTO`, `ProductWriteData`, `SubscriptionDTO`, `SubscriptionPlanDTO`, `PaymentDTO`, `PaymentWriteData` and `SubscriptionPaymentResultDTO` as `Currency` (or with the amount as `Money`), and the `?? 'CZK'` defaults beside them in `ProductCreationValidator`, `SubscriptionPaymentService`, `InvoicePrintDataBuilder`, `GoPayGateway` and `ProductListSelect` deleted. These general context conversions are not additional acceptance requirements of the entity replacement.

### Business logic in models
- **Where:** `app/Models/Invoice.php` (`subtotal_money` / `total_tax_money` accessors computing totals through `InvoiceTotals`, due date, `overdue` and `paid` for the lists, logging, `HasRoles`), `HasRoles` also on `PaymentMethod`, `$guarded` in 6 models; `User::invoices()` was corrected to use `invoices.user_id` in the business/contact branch; invoices from 2025 in the development database keep their items only as JSON in `invoice_text`
- **Fix:** logic into Domain, totals built by `EloquentInvoiceMapper` once Application no longer receives models, `$fillable`, `User::invoices()` as `hasMany` on `user_id` (`rules/persistence.md`).

### Validation in several places
- **Where:** frontend and admin form requests of the same entity with diverging rules (e.g. `InvoiceRequest` vs `Admin\InvoiceRequest`), `app/Domain/*/Validation/`
- **Fix:** one rule set per entity in form requests, business validation in value objects (`rules/http.md`).

### Passwords are checked only on the profile
- **Where:** `app/Livewire/User/RegistrationForm.php`, `app/Http/Requests/Admin/UserRequest.php`, `app/Http/Requests/PasswordUpdateRequest.php`, `UserWriteData::$password`, `EloquentUserMapper::toAttributes()`, `UserPassword`
- **Problem:** only the profile password change builds `UserPassword`, which refuses the common passwords. Registration checks only `Password::min(8)` and the admin user form has no length rule at all; both pass the plain password through `UserWriteData` to the model's hashed cast, so `password123` is accepted there. Found 2026-11-19 while fixing the 500 of the profile password change.
- **Fix:** every new password is built through `UserPassword` (registration and the admin user write path as well), which gets the length rule beside the common list (today it checks only the empty string and the common list), so all three forms share one rule; its `CommonPasswordException` is already rendered as an error of the `password` field. `Password::min(8)` leaves the registration and `PasswordUpdateRequest`, so the length has one source. A test per form.

### Directory layout
- **Where:** `app/Infrastructure/Services/`, `app/Infrastructure/Providers/Payment/` (→ `Gateways/Payment/`), `app/Infrastructure/Forms/Products/` (→ `Product/`), `app/Application/Invoice/Services/Specialized/`, `app/Application/Content/Services/`, `app/Application/Shared/Geography/Services/` and `app/Application/Shared/Money/Services/` (`PageApplicationService`, `CountryApplicationService`, `CurrencyApplicationService`, readers outside the gate), `app/Application/Party/Services/` (`CompanyRegistryApplicationService`, a reader outside the gate; `PartyLogoApplicationService`, logo files that delivery stores before the Command, like the product image below), `app/Domain/Payment/Providers/QrPayment/`; contracts without the `Interface` suffix: `Locale`, `BankDtoRepository`, `ApiStatisticsRepository`, `Str`, `Config`, `DomainEvent`
- **Fix:** per `rules/architecture.md` (Directory layout, Ports and interfaces); framework wrappers in `Domain/Shared` that are not ports (`Str`, `Config`, `Console`) are removed.

### Error handling
- **Where:** 121 `catch (\Exception …)` in 53 files, mostly `Log::error($e->getMessage())` plus a generic message; failures returned as `false` or `['success' => false]`; only 4 domain exceptions
- **Fix:** context exceptions, mapping in `app/Exceptions/ExceptionHandling.php` (`rules/php.md`, Errors). `CreateGuestInvoiceHandler` still catches `Exception` around the QR code, because the QR providers throw untyped exceptions; it narrows with them. The tests that pinned the catch-all responses (`exception_returns_500` and the like) were deleted with the `tests/Unit` clean-up; the conversion tests the mapped responses instead.

### Tests that pin structure instead of behaviour
- **Where:** `tests/Feature` only (`tests/Unit` was cleaned up 2026-10-25, branch `ls-unit-tests`): 27 `class_exists`/`method_exists` calls, 23 files using reflection, 24 `$fillable`/`$casts` references (e.g. `tests/Feature/Models/*`)
- **Fix:** delete structural tests and convert the rest with each context (step 21), use `PermissionCatalog` and migrations (`rules/testing.md`). The business/contact branch removes the obsolete Party interface-presence test and permission-schema fallbacks; both shared HTTP fixtures now use the catalog.

### Translations
- **Where:** 44 `trans()` calls in `app/` and `resources/views`; 9 form requests with `messages()`; `lang/*/admin.php` sections `dashboard`, `user_activity`, `user`, `auth`, `general`; `lang/*/emails.php` calls `config()` and `date()`
- **Problem:** the structure and every literal key are in place (`rules/translations.md`, `TranslationKeysExistTest`), but some code still uses `trans()`, some requests repeat standard messages, the admin dashboard and user activity texts sit in `admin.php` beside the menu, and the email texts compute the app name and year inside the language file. Keys that no code references were never counted after the restructuring.
- **Fix:** `__()` on the remaining calls; drop `messages()` that repeat `lang/*/validation.php`; move the dashboard and user activity texts to `lang/*/admin/dashboard.php` (or their entity files); pass the app name and year to the email texts as parameters; delete unreferenced keys file by file. Convert with `/convert-to-standard` context by context.

### Comments
- **Where:** history and migration notes ("Wave 2", "Phase 1", "legacy compatibility", "Removed …"), Czech comments (including `bootstrap/app.php`), the commented-out invoice lookup with a Czech `TODO` in `InvoiceFrontendActionsApplicationService::orchestrate()`, `TODO`s without an item here
- **Fix:** per `rules/php.md` (Comments, Readability), with each affected context's conversion: English explanations of non-obvious reasons, remove commented-out code and unused timing/counters.

### Move cache invalidation from observers to domain event handlers
- **Where:** `app/Infrastructure/Persistence/Eloquent/{Invoice,Product}/Observers`, `UserDataChanged` with `InvalidateUserCache`
- **Problem:** events are not yet used as `.claude/rules/architecture.md` (Events) requires. Cache invalidation runs through observers → `UserDataChanged`. Invoices, expenses and products publish only their `*Created` events; the update, delete and set-as-default events nobody published were deleted (commit "Delete unpublished domain events and unread event accessors", branch `ls-dead-code`) and are added back with their publishers. The Party context already publishes its events (created, updated, archived, restored, default business changed), and the owner's dashboard cache listens to them after the commit.
- **Fix:** add and publish the entity events from the domain services, let the cache invalidation handle them as Laravel listeners (after the commit, `rules/architecture.md` Events), then remove the observers.

### Format invoice amounts through a presenter
- **Where:** `resources/views/frontend/invoices/show.blade.php`
- **Problem:** amounts are formatted in Blade with `number_format(..., 2, ',', ' ')` and fallback logic; the Czech format is used for every locale.
- **Fix:** an invoice detail presenter using `MoneyFormatterInterface`, like `app/Application/Analytics/Presenters/*`.

### Products have no unit field
- **Where:** `app/Infrastructure/Forms/Products/ProductFormFields.php`, frontend and admin product forms and requests
- **Problem:** `products.unit` exists and is copied into the invoice item when a product is picked, but no form sets it, so every product counts in pieces.
- **Fix:** a unit select from `<x-unit-options />` (frontend) and the same option list in the admin, validated with `Rule::enum(Unit::class)`.

### Product images are stored inside the transaction
- **Where:** `App\Models\Product` (image mutator through `HasFileUploads`), `CreateProduct`, `UpdateProduct`
- **Problem:** the model writes the uploaded file while it is saved, inside the transaction the gate opens; a rollback leaves the file on disk, and neither Command declares `CallsOutsideDatabaseInterface` (`rules/application.md`). Found in the branch `ls-use-case-gate`.
- **Fix:** store the file in the handler outside the transaction, as `DeleteProduct` removes it after the commit, pass the stored path in `ProductWriteData`, and mark both Commands `CallsOutsideDatabaseInterface`.

### Product prices are not `Money` on the write path
- **Where:** `app/Domain/Product/DTO/ProductWriteData.php`, `app/Domain/Product/Validation/ProductCreationValidator.php`, `app/Domain/Product/ValueObjects/ProductPrice.php`, `app/Http/Requests/ProductRequest.php`
- **Problem:** `ProductDTO::$price` is `Money`, but `ProductWriteData::$price` is `string|float|null`, the validator casts it to `float` and `ProductPrice` holds a `float`. `fromArray` also accepts `price_money` and `amount`, which only its tests send. A price `12.349` passes `required|numeric` and is stored truncated as `12.34`, while the float `12.345` is rounded to `12.35`.
- **Fix:** `ProductWriteData::$price` as `?Money`, built at the boundary from one input shape; the validator checks the price through `Money` and `ProductPrice` is deleted with its test; the requests limit the decimal places.

## Step 22: Frontend, API and routes

### Permissions that nothing checks
- **Where:** `PermissionCatalog` (`invoice.delete` in the `web` guard)
- **Problem:** roles are granted this permission but no code checks it; no frontend route deletes an invoice. Found 2026-11-08 in step 9; `subscription.view` and `subscription.create` are checked since phase 6a of step 11.
- **Fix:** check the permission where its action exists, or remove it with a migration if the action is not planned.

### A zero id in the URL ends with a 500
- **Where:** routes with `whereNumber` whose controller builds an id value object (`ProductId::fromInt`, `InvoiceId`, `BusinessId`, `ContactId`, `SubscriptionId`, `SubscriptionPlanId`, …) in `routes/web.php` and `app/Http/Controllers/Frontend`
- **Problem:** `whereNumber` accepts `0`, the value object throws `InvalidArgumentException` for it, and `ExceptionHandling` does not map it, so `/…/0` answers 500 instead of 404. Found 2026-11-14 in the review of phase 6a of step 11.
- **Fix:** one rule for every id parameter (a positive-number route pattern, or a specific exception of the id value objects mapped to 404), with a test per route group.

### Page search reads its query unvalidated
- **Where:** `Frontend\PageController::search()`, route `frontend.pages.search`
- **Problem:** the action reads `q` straight from the request, so `?q[]=x` passes an array to `strlen()` and answers 500 instead of a validation error. Found 2026-11-18 in the review of phase 7 of step 11.
- **Fix:** a form request with `q` as a nullable string of limited length (`rules/http.md`, Form requests), with a test of the array input.

### Frontend
- **Where:** `resources/views` and `resources/js`: inline scripts, `@php` logic, `window.*` globals, field metadata and formatting outside presenters.
- **Fix:** per `rules/frontend.md`. The business/contact branch uses the shared Livewire party picker and removes the old client/supplier prefill scripts, endpoints, dead selectors and model fallbacks from invoice forms. General invoice/item JS and field-rendering helpers still need conversion. Dashboard scripts still expose globals and initialize from Blade; move them to modules or Alpine and pass translated data through components.

### Alpine is bundled next to Livewire's own
- **Where:** `resources/js/app.js`, `package.json` (`alpinejs`, `@alpinejs/focus`), `@livewireScripts` in `resources/views/layouts/app.blade.php` and `frontend.blade.php`
- **Problem:** both layouts load Livewire's script, which brings its own Alpine, on every page; `app.js` bundles a second Alpine with the focus plugin and starts it only when `window.Livewire` is missing, which depends on the order the two scripts run. Every page downloads Alpine twice. Found 2026-10-21, still so on 2026-11-18.
- **Fix:** `@livewireScriptConfig` in the layouts and Alpine taken from Livewire's ESM build in `app.js`, then `alpinejs` and `@alpinejs/focus` removed from `package.json`; check the pages without a Livewire component.

### Error pages are Laravel's defaults
- **Where:** `resources/views/errors/` does not exist; Backpack ships its own error views in `vendor/backpack/crud/src/resources/views/ui/errors/`
- **Problem:** the frontend shows Laravel's English error pages without the Billex layout in every locale, including the 429 of the creation rate limits (D-047).
- **Fix (decided 2026-11-07):** own error pages in the frontend layout, in four locales (`add-translation`), for at least 403, 404, 419, 429, 500 and 503; decide whether the admin keeps Backpack's error views.

### Dates outside the PDFs are formatted by hand
- **Where:** `app/Infrastructure/Shared/Support/DateHelper.php` (formats for cs and en only), 15 views in `resources/views` (`->format('d.m.Y')`, `DateHelper::format()`, e.g. `frontend/invoices/show.blade.php`, the remaining Livewire invoice widgets)
- **Problem:** the PDFs format dates through `DateFormatterInterface` in their language, but the frontend, the admin and the reminder emails print the Czech format for every locale.
- **Fix:** dates through `DateFormatterInterface` in the page locale: a `<x-date>` Blade component for remaining views; delete `DateHelper`. Reminder emails and the common invoice list already use the formatter in their presenter/mail factory.

### API responses
- **Where:** `app/Http/Controllers/Api/*` (114 `response()->json` calls in controllers), `resources/js/*` consumers
- **Problem:** whole objects are serialised (`response()->json($client)`), error bodies differ (`{"error"}`, `{"error", "code"}`), status codes are wrong (another user's client → 403 with a "not found" message, any exception → 404), every action repeats `try/catch` and an auth check the middleware already does.
- **Existing test contract:** `InvoiceControllerFeatureTest::user_cannot_access_foreign_invoice` still expects 403. Convert the ownership check and its response test together to 404 in the API/Invoice conversion (`rules/application.md`, `rules/http.md`); syntax-only changes preserve the current expectation.
- **Fix (decided 2026-10-20):** `JsonResource` per DTO with snake_case keys and `{"data"}` wrapping, standard error format through `ExceptionHandling` (`rules/http.md`, API responses). Endpoints that only feed vanilla-JS form prefill (`/api/client/{id}`, `/api/client/default`, `/api/supplier/…`, `/api/session-refresh`) are removed when those forms move to Livewire instead of being converted.

### Route names and URLs
- **Where:** `routes/web.php`, `routes/api.php`, every `route('…')` call in `app/`, `resources/views/`, `resources/js/` and `tests/`
- **Problem:** index routes are plural without an action, the others singular (`frontend.clients` vs `frontend.client.create`); API names follow no pattern (`api.client`, `api.client.default`); URLs are singular (`/client`, `/invoice/{id}/edit`); custom actions vary (`mark-as-paid`, `set-template`, `save-to-contacts`).
- **Fix (decided 2026-10-20):** plural URLs and `<area>.<resource plural>.<action>` names through `Route::resource()` / `apiResource()`, custom actions as kebab-case verbs (`rules/http.md`, Routes); update all references.

## Step 23: After the conversion

### Larastan level
- **Where:** `phpstan.neon`
- **Problem:** Larastan runs at level 2 (2026-11-11, branch `ls-larastan-level`: generic relation types on the models, stubs for Backpack's fluent column and field API and the Spatie permission relations in `phpstan/stubs/`, columns written out in the migrations). Level 3 reports 43 more errors, level 6 978 (2026-11-11). One level applies to the whole codebase. Array value types are not checked yet: `phpcs.xml` leaves out Slevomat's `MissingTraversableTypeHintSpecification` (about 1150 signatures without `list<…>` / `array<string, …>`), which Larastan reports from level 6.
- **Fix:** raise the level once the whole codebase passes the next one: levels 2–6 while the contexts are converted (step 21), 7–10 (`max`) after that, one level per branch. No baseline file and no ignore comments.

### Architecture guards are migration scaffolding
- **Where:** `config/ddd_guard.php` (allowlist for the deleted `app/Services`, scan path `app/Policies`, Bitbucket detection), `ddd:legacy-report`, `architecture/baselines/legacy_report_latest.json` and `provider_snapshot.json` (README still describes a throwaway container)
- **Fix:** after the conversion, replace with Larastan and architecture tests of the dependency table in `rules/architecture.md`; delete the baselines and their commands.

## Step 24: Before production

### Entity limit counts can deadlock across owners
- **Where:** `EloquentLimitedEntityCounter::countActiveForUpdate` and `countCreatedSinceForUpdate`, `EloquentTransactionBoundary`
- **Problem:** under REPEATABLE READ the locking `COUNT` takes next-key and gap locks on the `user_id` index, and these reach past one owner. Two neighbouring owners without rows who create their first record at the same time can each hold the gap the other's `INSERT` needs. MySQL then ends one transaction with error 1213, which nothing retries, so that user gets a 500. Found by reading the locking behaviour, not reproduced.
- **Fix:** reproduce it with two connections, then either retry the transaction on a deadlock or stop locking the entity rows and read the count so that it sees committed rows while the owner's `users` row is locked.

### The permission sync grants back revoked role defaults
- **Where:** `PermissionCatalogSync::sync()`, the migrations that add a permission or change a role's defaults (`.claude/skills/add-permission/SKILL.md`, step 3)
- **Problem:** every run gives each system role all its catalog defaults, so a default revoked through PermissionManager comes back with the next migration that runs the sync. Renames and removals do not run it (`PRD_PERMISSIONS.md`, A01). No default has been revoked so far.
- **Fix:** a new migration grants only the permission it adds; the full sync stays for a fresh database (A02).

### `backpack_pro()` override claims a PRO licence
- **Where:** `app/Helpers/BackpackHelpers.php` (`backpack_pro()` always returns `true`), Backpack's `src/Stats.php`
- **Problem:** the override lifts Backpack's PRO gates, which Billex's own filters need (`CrudFilter` refuses to work without PRO). Outside the `local` environment, Backpack sends usage stats to backpackforlaravel.com on about 1 in 100 admin requests, including `BACKPACK_PRO_VERSION => backpack_pro()`, so production would report a PRO licence it does not have. PRO-only operations (`FetchOperation`, `InlineCreateOperation`, `CloneOperation`, `BulkDeleteOperation`) would fail with a missing class instead of a clear error.
- **Fix:** decide before production: buy a PRO licence, or keep the gates closed and make the filters work without the override (e.g. own filter classes that do not extend `CrudFilter`'s PRO check).

### No error monitoring
- **Where:** `bootstrap/app.php` (`withExceptions`), `config/logging.php` (`LOG_STACK`), `phpunit.xml`, `vite.config.js`, the layouts `resources/views/layouts/frontend.blade.php` and `app.blade.php` and the Backpack theme layout
- **Problem:** errors outside a browser the developer is looking at — scheduled tasks in the `cron` container, queued jobs, GoPay and ARES calls, JavaScript in users' browsers — end up only in `laravel.log` or nowhere. Many controllers catch an exception and only log it, so it never reaches the exception handler.
- **Fix (decided 2026-10-25):** Sentry Cloud with the EU data region (chosen when the organisation is created, cannot be changed later), one project per environment or the `environment` tag. PHP: `sentry/sentry-laravel`, `Integration::handles($exceptions)` next to `ExceptionHandling::registerCallbacks()`, a `sentry` log channel at level `error` in `LOG_STACK` so logged-and-caught exceptions arrive without touching controllers, `send_default_pii` off and no SQL bindings (invoices hold clients' personal data), empty `SENTRY_LARAVEL_DSN` in `phpunit.xml`. Browser: `@sentry/browser` initialised first in `resources/js/app.js` and in the admin layout, no session replay until amounts and personal data are masked; inline `<script>` blocks in the layouts move into modules (`rules/frontend.md`) so no code runs before the SDK. Source maps: `build.sourcemap: 'hidden'` (Sentry matches them through the debug IDs the plugin injects), uploaded by `@sentry/vite-plugin` and deleted after the upload, never served; the plugin keeps them unless `sourcemaps.filesToDeleteAfterUpload` is set (`public/build/**/*.map`). The build that uploads them, in CI or on deploy, fails when `public/build` still contains a `.map` file.

### Erasing a user account
- **Where:** `users` (soft deletes), `UserCrudController`, the restricting `user_id` foreign keys of `businesses`, `contacts`, `products`, `expenses`, `invoices` and `subscriptions`; cascading `model_has_roles`, `model_has_permissions`
- **Problem:** there is no way to erase a user under GDPR. A database cascade would delete documents without the export and notice the law requires, so the foreign keys restrict and nothing disappears by accident. For the user's invoices, contacts and expenses Billex is a processor: the duty to archive them is the user's, and after the service ends Billex returns and deletes them (Art. 28(3)(g) GDPR). For the account, subscription and Billex's own invoices for it Billex is the controller and keeps them for 10 years as a legal obligation (Art. 17(3)(b)).
- **Fix (proposed 2026-10-25, to be reviewed by a lawyer, with the terms of service and the data processing agreement):** an application use case, not a cascade: the user requests erasure and receives an export of all their data (invoice PDFs, expenses, businesses, contacts); the account is blocked at once; after the announced period the use case deletes their business data, credentials, tokens, logs and settings in dependency order; only Billex's documents for the subscription stay, with the minimum data about the user as customer.

### Build refunds
- **Where:** `payments.refunded_amount`, statuses `refunded` and `partially_refunded` (`2026_10_25_000010_create_subscription_tables.php`, `lang/*/payments.php`), `app/Infrastructure/Providers/Payment/GoPayGateway.php`
- **Problem:** Billex cannot refund a payment. A refund flow that nothing called (`RefundState`, `RefundResultDTO`, the refund use cases, the gateway's refund call and `Payment::applyRefund()` with the logic in the model) was deleted (commit "Delete unused payment code", branch `ls-dead-code`); the column and the statuses remain.
- **Fix:** when refunds are needed: a `RefundState` value object computes the new state (accumulation, partial or full), the gateway refunds through the payment port, the repository only persists the result, returned as `RefundResult` (`rules/architecture.md`, Data classes).
