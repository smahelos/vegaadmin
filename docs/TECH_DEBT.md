# Technical Debt

Known gaps between the code and the intended architecture (see `CLAUDE.md`, `docs/adr/`). Most items are unfinished steps of the DDD migration (commits `a65aaffb` 2025-09-09 and `7e999b2a` 2025-10-12 in the original Bitbucket history): the target class exists, but the code was never switched over to it. Remove an item once it is resolved.

## Security

### Public statistics endpoints expose all users' revenue
- **Where:** `routes/api.php` (statistics routes in the public `web, throttle` group), `app/Infrastructure/Persistence/Eloquent/Analytics/Repositories/EloquentApiStatisticsRepository.php`
- **Problem:** `/api/monthly-revenue` and the other statistics routes need no login; `getMonthlyRevenueQuery()` and `getExpenseQuery()` have no user filter, so a guest gets revenue summed over every user.
- **Fix:** move the routes behind `api.require.frontend`, scope every query by the user passed in from Application (no `Auth::id()` in the repository).

### State changes over GET
- **Where:** `routes/web.php` (`client/{id}/set-default`, `supplier/{id}/set-default`, `invoices/delete/{token}`), `routes/backpack/custom.php` (`cron-task/{id}/run`, `cache/clear*` linked from the admin menu)
- **Problem:** GET requests change state without CSRF protection; a link or prefetch triggers them.
- **Fix:** `POST`/`PUT`/`DELETE` routes with forms (`rules/http.md`, Routes).

### Sorting from the URL without an allowlist
- **Where:** all components in `app/Livewire/` (`#[Url] $orderBy` / `$sortField` passed to `orderBy()`)
- **Problem:** any column name from the query string reaches the query.
- **Fix:** allowlist per component; resolved together with moving the queries into repositories.

### Middleware registered twice
- **Where:** `bootstrap/app.php`
- **Problem:** the `web` group re-appends cookie, session, error and CSRF middleware it already contains; `RefreshFrontendSession` and `SetLocale` are prepended to the group and applied again by alias in `routes/web.php`, the first time before the session starts.
- **Fix:** keep Laravel's group defaults, register each middleware once.

### Payment notification route
- **Where:** `routes/web.php` (`POST /payment/notify` in the `web` group), `app/Http/Controllers/Frontend/PaymentController.php::notify()`
- **Problem:** the route is not excluded from CSRF, so an external notification most likely ends with 419 (to verify); the whole request payload is logged.
- **Fix:** CSRF exception, gateway-verified handling as in `rules/payments.md`, log only the payment id.

### Exception messages shown to users
- **Where:** `app/Http/Controllers/Frontend/{ProductController,SupplierController,InvoiceController}.php` (flash messages and JSON `error`), `app/Http/Controllers/Api/{UserController,InvoiceController}.php` (JSON `error`), `app/Http/Controllers/Admin/CacheManagementController.php` (admin alert)
- **Problem:** the raw `$e->getMessage()` is appended to the error a user sees or returned in JSON, so SQL errors, column names, file paths and other internals reach the browser. The profile screen had the same flaw and was fixed in `ls-missing-translations`.
- **Fix:** a complete translated message for the user, the exception only in the log with structured context (`rules/php.md`, Errors); done together with "Error handling" below by mapping context exceptions in `app/Exceptions/ExceptionHandling.php`.

## Invoice

### Issued invoices can be changed and deleted
- **Where:** `InvoiceCrudApplicationService` (update, delete), frontend `InvoiceController` (edit, update, set-template, delete), `Admin\InvoiceCrudController` and `Admin\InvoiceRequest`, `InvoicePrintDataBuilder` (reads the current client and supplier), `InvoicePdfRenderer`, `app/Console/Commands/DatabaseArchiveCommand.php` and `archive_policies` (36 months), `Money` (no negative amounts)
- **Problem:** an issued invoice is a tax document kept unchanged for 10 years (§ 34 and § 35 ZDPH; corrections through a corrective tax document, § 42 and § 45 ZDPH; § 35 ZÚ; to confirm with an accountant after the 2025 amendment). Today an invoice has no draft/issued state (`draft` is a payment status), can be edited in the frontend and the admin and is deleted for good (no `deleted_at`); the admin edits `payment_amount` without the lines, so the amount can disagree with them. It stores only `client_id` and `supplier_id`, so a later change of the party changes every old invoice; the PDF is rendered again from the current template and logo on every download. `db:archive` moves invoices and items older than 36 months out of the main tables and deletes clients and suppliers by the same policy.
- **Fix (decided 2026-10-23, own branch after `ls-invoice-item-validation`, before the ČNB rates):** a document life cycle separate from the payment status (draft → issued → cancelled by a corrective document); issuing assigns the number, stores a snapshot of the parties, bank account, lines, totals, currency, payment method, template and logo, and stores the rendered PDF with its hash, which later downloads return. The Domain refuses changes to an issued invoice; only its payment status changes. Corrections and cancellations are corrective tax documents (credit or debit note) linked to the invoice, with their own PDF; a cancellation can copy the invoice into a new draft. The admin shows the amount read-only. Invoices, corrective documents and their PDFs are kept for 10 years, also when the user account is deleted; who issued or cancelled a document and when is recorded. Open decisions: numbering at issue, a number series for corrective documents, negative `Money` or a sign by document type, existing invoices, VAT payers vs non-payers; keep or delete `InvoiceCrudApplicationService::delete()`, `getForEdit()`, `softDelete()` and `restore()`, which nothing calls (deleting drafts).

### Modern and minimal PDFs always print "bank transfer"
- **Where:** `app/Application/Invoice/Services/InvoicePdfRenderer.php` (`'paymentMethod' => null`), `resources/views/pdfs/templates/{modern,minimal}.blade.php`
- **Problem:** the renderer never passes the invoice's payment method, so these two templates print the fallback "Bankovní převod" on every invoice, cash and card included. The default template looks the method up itself. An invoice is a legal document.
- **Fix:** pass the payment method label in the print data (`PrintableInvoiceData`) and let all templates print it; extend the PDF snapshot test with a non-transfer method.

### Status enum lists factory slugs and English labels
- **Where:** `app/Domain/Shared/Status/ValueObjects/StatusCode.php`, `StatusService::translateSlug()`, `App\Models\Status::getTranslatedNameAttribute()`
- **Problem:** the enum holds slugs that only test factories create (`paid-status`, `pending-payment`, `cancelled-status`) and `unpaid`, which no status row has; `StatusService` refuses to start when a database lacks any enum slug. `label()` returns hardcoded English. The model accessor translates the slug itself instead of going through `translateSlug()`.
- **Fix:** keep only the system statuses in the enum (seeded by a migration), labels from `statuses.statuses.<slug>`, one resolver used by the model, the invoice mapper and the dashboard.

### Compose `PrintableInvoiceData` from `PartyPrintData`
- **Where:** `app/Domain/Invoice/DTO/PrintableInvoiceData.php`, `app/Domain/Party/DTO/PartyPrintData.php`
- **Problem:** `PrintableInvoiceData` has ~40 flat `supplier_*` / `client_*` fields. `PartyPrintData` was created in the same commit to hold one party, but was never used.
- **Fix:** `supplier: PartyPrintData`, `client: PartyPrintData`; update `InvoicePrintDataBuilder`, `TemporaryInvoicePrintDataFactory`, `InvoicePdfRenderer`, the PDF templates and snapshots.

### Calculate due dates through `InvoiceDueDate`
- **Where:** `app/Domain/Invoice/ValueObjects/InvoiceDueDate.php`
- **Problem:** the due date (`issue_date + due_in`) is computed separately in `Invoice` model accessor, `InvoicePrintDataBuilder`, `TemporaryInvoicePrintDataFactory` and Livewire list ordering; `InvoiceFormApplicationService` hardcodes `now()->addDays(14)`.
- **Fix:** Domain/Application calculations use the value object.

### Currency conversion of invoice items happens in the browser
- **Where:** `resources/js/currency-manager.js` (`getExchangeRate()`, `convertAmount()`), `resources/js/invoice-item-manager.js`, `app/Domain/Shared/Money/Services/CurrencyExchangeService.php`, `InvoiceRequestAssembler`, `/api/currencies/{exchange-rate,convert}`
- **Problem:** the invoice form converts item prices in JavaScript with floats; the server checks only that every item is in the invoice currency (`InvoiceRequest`), stores the converted price as it arrives and keeps no rate or date with the invoice. The form also previews line and invoice totals in JavaScript floats; the stored amounts are computed on the server (`InvoiceTotals`). Rates come from the free ExchangeRate-API (`open.er-api.com`); when it fails, the service silently returns rates hardcoded in the class (EUR→CZK 25.29 against 24.27 on 2026-10-23), and when a rate is missing the browser uses 1:1. VAT in CZK on a foreign-currency invoice needs the ČNB rate (§ 4 odst. 5 ZDPH; to confirm with an accountant). `Money::convertTo()` exists but nothing calls it.
- **Fix (decided 2026-10-23, own branch after the issued-invoice immutability):** ČNB daily rates behind an exchange rate port; conversion on the server through `Money::convertTo()` when the invoice is stored; the rate, its date and source stored with the invoice and printed on it; an unavailable rate is an error shown to the user, never a fallback table or 1:1; `/api/currencies/convert` becomes the form's conversion or is deleted.

### VAT rates are typed into the database by hand
- **Where:** `taxes` table (read by `TaxDtoReadRepositoryInterface::getDphRatesForDropdown()`), `InvoiceRequest` (allowed item rates), invoice and product forms
- **Problem:** no migration or seeder creates the VAT rates, so a fresh install offers only 0 % and accepts nothing else; the development database has 12, 21 and 25 % (25 % is not a Czech rate).
- **Fix:** create the Czech rates as reference data in a migration (`rules/persistence.md`); decide with an accountant whether the rates depend on the supplier's country.

### Format invoice amounts through a presenter
- **Where:** `resources/views/frontend/invoices/show.blade.php`
- **Problem:** amounts are formatted in Blade with `number_format(..., 2, ',', ' ')` and fallback logic; the Czech format is used for every locale.
- **Fix:** an invoice detail presenter using `MoneyFormatterInterface`, like `app/Application/Analytics/Presenters/*`.

## Payment

### GoPay is called inside the database transaction
- **Where:** `app/Domain/Payment/Services/SubscriptionPaymentService.php::processSubscriptionPayment()`
- **Problem:** the transaction wraps `createPayment()` of the gateway. When a later step fails, the rollback deletes the local payment record, but the payment created at GoPay stays and its notification finds no record; the transaction also holds its locks for the whole HTTP call.
- **Fix:** store the pending payment in one transaction, call the gateway outside it, then store the gateway id and status in a second transaction; a failed gateway call marks the payment failed. Move the use case to Application (`rules/application.md`).

### Move refund logic into `RefundState`
- **Where:** `app/Models/Payment.php::applyRefund()`, `app/Domain/Payment/ValueObjects/RefundState.php`
- **Problem:** refund business logic (accumulation, partial/full status) lives in the Eloquent model; the value object is unused.
- **Fix:** `RefundState` computes the new state, the repository only persists it.

### Typed GoPay request with real customer data
- **Where:** `app/Infrastructure/Providers/Payment/GoPayGateway.php`, `app/Domain/Payment/ValueObjects/PaymentAmount.php`
- **Problem:** `createPayment(array $paymentData)` is untyped and the amount is not checked against gateway limits. Without customer data the gateway sends a placeholder customer (`Test User`, `test@example.com`); `SubscriptionController` passes none.
- **Fix:** a request DTO (amount as `PaymentAmount`, plan, customer, URLs) filled with the subscriber's real data. Do this with the subscription plans work.

### Validate the variable symbol in QR payments
- **Where:** `app/Domain/Payment/DTO/QrPaymentPayload.php`
- **Problem:** the variable symbol is not validated (Czech QR standard: max 10 digits); constant and specific symbols are not supported.
- **Fix:** a `VariableSymbol` value object in the payload; add CS/SS if needed.

### Validate bank accounts through `BankAccount`
- **Where:** `app/Application/Party/Services/PartyInputValidationService.php::isValidIBANFormat()`, `app/Domain/Payment/ValueObjects/BankAccount.php`
- **Problem:** IBAN is validated with a simplified regex (no mod-97 checksum); QR providers format accounts themselves.
- **Fix:** validate and convert through the value object. It is stricter, so check existing supplier data first.

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

### Inactive banks are still offered
- **Where:** `banks.active`, `app/Infrastructure/Persistence/Eloquent/Payment/Repositories/EloquentBankRepository.php` (`getAllBanks()`)
- **Problem:** the admin can mark a bank inactive, but the bank list for supplier forms filters only by country, so the flag changes nothing.
- **Fix:** list only active banks for the supplier forms (the admin list keeps all), or drop the column.

### Expense detail has no attachment links
- **Where:** `app/Http/Controllers/Admin/ExpenseCrudController.php` (`setupShowOperation()`)
- **Problem:** the detail reuses the list column, which prints attachment file names joined by commas and cut to 50 characters, with no way to open a file. A renderer with links was present only as commented-out code since the first commit (2025-05-28) and was removed.
- **Fix:** a show column listing each attachment as a link (file names escaped, URLs from the storage disk, not concatenated paths), with an HTTP test of the detail page.

### Unreachable branch of the frontend user request
- **Where:** non-profile branch of `app/Http/Requests/UserRequest.php`
- **Problem:** no route reaches it; it checks `frontend.can_create_edit_user`, which is not in `PermissionCatalog`. `PermissionCatalogCoverageTest` allowlists it as unreachable.
- **Fix:** delete the branch, then drop the allowlist.

### Admin list columns build display text by hand
- **Where:** `app/Http/Controllers/Admin/{EntityUsage,EntityLimit,PerformanceMetric,DatabaseMaintenanceLog}CrudController.php`; badge accessors in `app/Models/{ArchivePolicy,DatabaseHealthAlert,DatabaseHealthMetric,DatabaseMaintenanceLog,MysqlOptimizationLog}.php`; hardcoded labels also in the `Client`, `Supplier`, `Product`, `Invoice`, `Status`, `Subscription` and `ExpenseCategory` CRUD controllers
- **Problem:** amounts and sizes are formatted with `'€' . number_format(...)` and private `formatBytes()` helpers, ignoring the locale and the entity's currency. Labels and cell texts are hardcoded English (`->label('User')`, `'Anonymous'`, `'Pending'`). The maintenance results and performance metadata closures put MySQL output (`Msg_text`, storage engine, metadata values) into HTML unescaped. Models return badge HTML with English text (`getStatusBadgeAttribute()` and similar).
- **Fix:** amounts through `MoneyFormatterInterface`, labels and texts as translation keys, every interpolated value through `e()` or a Blade column view, badge HTML out of the models into column views with the status as an enum (`rules/admin.md`, `rules/persistence.md`, `rules/translations.md`).

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

### Product image is deleted before the product
- **Where:** `app/Application/Product/Services/ProductApplicationService.php::deleteProduct()`
- **Problem:** the image file is deleted before the transaction that deletes the product; when the delete fails, the product stays without its image.
- **Fix:** delete the file after the transaction commits.

### Products have no unit field
- **Where:** `app/Infrastructure/Forms/Products/ProductFormFields.php`, frontend and admin product forms and requests
- **Problem:** `products.unit` exists and is copied into the invoice item when a product is picked, but no form sets it, so every product counts in pieces.
- **Fix:** a unit select from `<x-unit-options />` (frontend) and the same option list in the admin, validated with `Rule::enum(Unit::class)`.

### Product SKU
- **Where:** `app/Domain/Product/ValueObjects/ProductSku.php`
- **Problem:** value object prepared for a feature that does not exist yet (no `sku` column).
- **Fix:** use it when SKU is added, or delete it.

## Entity limits

### UELS dashboard bulk reset fails
- **Where:** `app/Http/Controllers/Admin/UELSDashboardController.php::bulkResetUsage()`
- **Problem:** the action passes a `User` model to `UniversalLimitService::resetUsage(?int $userId, …)` and ends with a `TypeError` (500). Its validation marks both checkboxes `accepted`, so resetting only anonymous usage or only users cannot be submitted at all.
- **Fix:** pass the user id, make both options optional (at least one required), cover the action with a feature test. With the subscription plans work.

### Dead reset actions and hardcoded units in the usage CRUD
- **Where:** `app/Http/Controllers/Admin/EntityUsageCrudController.php` (`resetUsage()`, `bulkResetUsage()`, `formatBytes()`), `app/Http/Requests/Admin/EntityUsageRequest.php`, `resources/views/admin/uels-dashboard.blade.php`, `resources/js/uels-manager.js`
- **Problem:** no route reaches the two reset actions, and the CRUD has no create or update operation, so `EntityUsageRequest` validates nothing; both still carry English flash messages. Value-based limits print a fixed `€` and sizes `KB`, the widget title in `uels-manager.js` prints the raw entity type and period slugs.
- **Fix:** delete the unreachable actions and request, format amounts through `MoneyFormatterInterface` with the limit's currency, pass translated labels to the widget script.

## Cross-cutting

### Move cache invalidation from observers to domain event handlers
- **Where:** `app/Infrastructure/Persistence/Eloquent/*/Observers`, `app/Domain/Analytics/Events/AnalyticsDataChanged.php`, `app/Domain/Analytics/Events/Handlers/InvalidateAnalyticsCache.php`
- **Problem:** events are not yet used as `.claude/rules/architecture.md` (Events) requires. Cache invalidation runs through observers → `UserDataChanged`; `AnalyticsDataChanged` is never published, so its registered handler never runs. Events `ClientDeleted`, `ClientSetAsDefault`, `SupplierDeleted`, `SupplierSetAsDefault`, `SupplierUpdated`, `ProductDeleted`, `ProductSetAsDefault`, `ProductUpdated` exist but are never published.
- **Fix:** publish the entity events from the domain services, let `InvalidateAnalyticsCache` handle them, then remove the observers and `AnalyticsDataChanged`.

### Domain events are published inside the transaction
- **Where:** `app/Infrastructure/Shared/Events/LaravelEventPublisher.php`; `InvoiceService::createInvoice()` (`InvoiceCreated`, inside the transaction of `InvoiceCrudApplicationService`), `ProductService` and `InvoicePartyService` (`ProductCreated`, `ClientCreated`, `SupplierCreated`, inside the `DB::transaction()` of `ProductApplicationService` and `PartyApplicationService`), the Eloquent observers (`UserDataChanged`, `FormDataChanged` on every save)
- **Problem:** `rules/application.md` publishes events after the commit; `LaravelEventPublisher` dispatches at once, so handlers run inside the transaction. Effects outside the database (cache, mail, queued jobs; `config/queue.php` has `after_commit` off) stay when it rolls back, and a cache cleared before the commit can be filled again with the old data by a concurrent request. Handlers that write to the same database (UELS usage counters) roll back with it.
- **Fix (decided 2026-10-23, own branch):** the publisher defers dispatching until the surrounding transaction commits (`afterCommit()` of the connection in the Infrastructure adapter; outside a transaction it dispatches at once), which covers the observers too; reword `rules/application.md` to "domain events are delivered after the commit". Test that a rolled-back use case dispatches nothing.

### Run tests on MySQL instead of in-memory SQLite
- **Where:** `phpunit.xml`, `config/database.php` (`testing` connection), `tests/TestCase.php`, `tests/Traits/RefreshDatabaseWithData.php`, `.github/workflows/*`, SQLite branches in `app/Infrastructure/Persistence/Eloquent/Analytics/Repositories/EloquentAnalyticsReadRepository.php`, `app/Console/Commands/Database{Optimize,Monitor}Command.php` and 10 migrations (including `2025_07_23_054624_add_basic_foreign_keys_for_testing.php`)
- **Problem:** the application runs on MySQL, tests on in-memory SQLite. MySQL-specific queries (`DATE_FORMAT`, `STR_TO_DATE`, …) are never tested, SQLite does not enforce strict `sql_mode` or column types, and production code and migrations carry `driver === 'sqlite'` branches that exist only for the tests. `TestCase` resets `RefreshDatabaseState` so that every test migrates all migrations again, and `RefreshDatabaseWithData` runs `migrate:fresh` + seed before every test.
- **Fix (decided 2026-10-20):** tests run only on MySQL — a separate test database in the existing MySQL container, next to the local development database, and a MySQL service in CI. Squash the migrations into a clean MySQL schema first (no production yet). Migrate and seed once, one transaction per test (`RefreshDatabase`); remove the `TestCase` reset, `RefreshDatabaseWithData` and every SQLite branch; fix tests that rely on fixed IDs or row order. The test user needs `CREATE DATABASE` for `--parallel` (`<database>_test_N`).

### `ddd:guard` snapshot test fails now and then in parallel runs
- **Where:** `tests/Feature/Console/DddGuardDiJsonSnapshotTest.php`
- **Problem:** in `php artisan test --parallel` the `Artisan::call('ddd:guard', …)` call occasionally throws (seen 2026-10-22); the test passes alone and in the next full runs.
- **Fix:** find what the command shares with other parallel processes (scanned files, cache, output buffer) and isolate it, or drop the test together with the guards (Architecture guards are migration scaffolding).

### Resilience of external calls
- **Where:** `app/Http/Controllers/Api/AresLookupController.php`, `app/Infrastructure/Shared/Http/Services/HttpClientService.php`, `app/Domain/Analytics/Services/BaseAnalyticsExternalService.php`, `config/analytics.php`
- **Problem:** ARES is called with `Http::get()` directly from a controller without a timeout; exchange rates and countries go through `HttpClientService` without timeouts. A circuit breaker exists only in the unused Analytics base class.
- **Fix:** timeouts and a circuit breaker in `Shared` around `HttpClientService`; move the ARES call into a service.

## Conversion to the standard

The code standard is defined in `.claude/rules/` (decided 2026-10-20). Each item below is a gap between that standard and the code; convert one bounded context at a time.

### Tooling
- **Where:** project root, `.github/workflows/`
- **Problem:** the codebase is not formatted by Pint (`pint.json` exists since 2026-10-23); there is no static analysis.
- **Decided 2026-10-23:** syntax and formatting are checked by linters (Pint, Larastan), not by AI reviewing or rewriting code style.
- **Fix:** format the whole codebase in one commit; install Larastan without a baseline file, start at the highest level that can be made clean, raise it as contexts are converted (target: max); run both in CI.

### `strict_types` and `final`
- **Where:** all of `app/`, `tests/`, `database/` (11 of 1227 files declare `strict_types`, 11 classes are `final`); files touched by a branch keep their old shape, e.g. `app/Http/Controllers/Frontend/ProfileController.php` (no constructor promotion, untyped properties)
- **Fix:** add `declare(strict_types=1)` and `final` (except abstract bases and Eloquent models, `rules/php.md`); the test suite catches the coercions this exposes.

### Transaction boundaries
- **Where:** `ProductApplicationService`, `PartyApplicationService` (`DB::transaction()`), `app/Domain/User/Services/UserService.php` (`create()`, `updateById()`), `SubscriptionPaymentService`, `app/Infrastructure/Persistence/Eloquent/Shared/Database/EloquentTransactionBoundary.php` (not `final`)
- **Problem:** use cases open transactions through the facade instead of `TransactionBoundaryInterface`; Domain services open transactions themselves, `UserService` around a single write.
- **Fix:** per `rules/application.md`: the application service opens the transaction through the port when the use case writes more than one record; single writes need none.

### Two sets of repositories
- **Where:** 19 repository contracts in `app/Application/*/Contracts` returning Eloquent models (e.g. `InvoiceReadRepositoryInterface`), next to the Domain `*Dto*RepositoryInterface` ports
- **Problem:** Application, controllers and Livewire work with models; the same entity has two persistence paths.
- **Fix:** move every use to the Domain ports, delete the Application repositories and their implementations. Then, per `rules/persistence.md` (decided 2026-10-20): ports without `Dto` and always with `Interface` (`ClientReadRepositoryInterface`; today 24 ports incl. `BankDtoRepository`, `AnalyticsDtoReadRepository`); a read port for every read entity, a write port only where the Domain writes (unsplit today: `StatusDtoRepositoryInterface`, `StatusCategoryDtoRepositoryInterface`, `BankDtoRepository`, `ArtisanCommandsDtoRepositoryInterface`, `AnalyticsDtoReadRepository`); one `Eloquent<Entity>Repository` per entity implementing both (merge `EloquentPaymentDto{Read,Write}Repository`, `EloquentSubscriptionDto{Read,Write}Repository`).

### Framework and model dependencies outside Infrastructure
- **Where:** `App\Models` in 37 Application files, 39 of 55 controllers and all 10 Livewire components; `Illuminate\Http` in 7 Application files; facades/helpers in 13 Application files; `app/Domain/Payment/Services/SubscriptionPaymentService.php` imports `GoPayGateway`
- **Fix:** per `rules/architecture.md` (dependency table); Livewire components call application services.

### Interfaces without a port
- **Where:** 44 of 45 `*ServiceInterface` contracts in Domain and Application have a single implementation; 17 test files mock them
- **Fix:** delete the interfaces and their bindings, make the services `final`, rewrite the mocking tests to run the real service with fakes at the ports; refresh the provider snapshot.

### Data class names and properties
- **Where:** `app/*/*/DTO/`, `app/Domain/Shared/Money/Enum`, `app/Domain/Invoice/Notifications/Enums`, `app/Application/Shared/Form/Enum`
- **Problem:** suffixes `DTO`/`Data`/`Payload`/`ResultDTO` mixed; 167 snake_case vs 75 camelCase properties, mixed within one class.
- **Fix:** `PrintableInvoiceData` → `PrintableInvoiceDTO`, `PrintableInvoiceItemData` → `PrintableInvoiceItemDTO`, `PartyPrintData` → `PartyPrintDTO`, `QrPaymentPayload` → `QrPaymentDTO`, `InvoiceReminderPayload` → `InvoiceReminderDTO`, `RefundResultDTO` → `RefundResult`, `SubscriptionPaymentResultDTO` → `SubscriptionPaymentResult`; replace `InvoiceMutationPayload` with `InvoiceWriteData`; move `InvoiceActionStatus` to `Enums/`, rename every `Enum/` directory to `Enums/`; camelCase properties with `final readonly class`.

### Value object accessors
- **Where:** all 21 classes in `app/Domain/*/ValueObjects` and `app/Domain/Shared/*/ValueObjects`
- **Problem:** private state read through four naming styles: `getValue()` (46 calls), `toInt()` (`UserId`, `PartyId`; 83 calls), `value()` (`ProductSku`), `get<Part>()` (`Money::getAmount()`, `InvoiceNumber::getNumber()`, `BankAccount::getIban()`, …).
- **Fix (decided 2026-10-20):** `final readonly class` with public readonly properties for held state (`->value`, `->amount`, `->iban`), computed values as methods without `get` (`formattedIban()`, `year()`), per `rules/php.md`.

### Business logic and broken relations in models
- **Where:** `app/Models/Invoice.php` (`subtotal_money` / `total_tax_money` accessors computing totals through `InvoiceTotals`, due date, logging, `HasRoles`, `belongsToMany(...)` to `clients`/`suppliers`/`statuses` tables), `$guarded` in 6 models; invoices from 2025 in the development database keep their items only as JSON in `invoice_text`
- **Fix:** logic into Domain, totals built by `EloquentInvoiceMapper` once Application no longer receives models, `$fillable` and typed relations (`rules/persistence.md`).

### Validation in several places
- **Where:** frontend and admin form requests of the same entity with diverging rules (e.g. `InvoiceRequest` vs `Admin\InvoiceRequest`), `app/Application/*/Services/*InputValidationService.php`, `app/Domain/*/Validation/`; entity limits checked in requests, controllers and observers
- **Fix:** one rule set per entity in form requests, business validation in value objects, limit check once in the application service (`rules/http.md`, `rules/permissions.md`).

### Client and supplier duplicates
- **Where:** frontend `ClientController`/`SupplierController`, their requests, Livewire lists, views, `resources/js/{client,supplier}-form-data.js` (~85 % identical)
- **Fix:** one Party implementation parameterised by party type.

### Directory layout
- **Where:** `app/Infrastructure/Interfaces/`, `app/Infrastructure/Services/`, `app/Infrastructure/Providers/Payment/` (→ `Gateways/Payment/`), `app/Infrastructure/Forms/Products/` (→ `Product/`), `app/Application/Invoice/Services/Specialized/`, `app/Domain/Payment/Providers/QrPayment/`; contracts without the `Interface` suffix: `AnalyticsDtoReadRepository`, `Locale`, `BankDtoRepository`, `AnalyticsReadRepository`, `ApiStatisticsRepository`, `BankRepository`, `DomainEvent`, `Str`, `Config`
- **Fix:** per `rules/architecture.md` (Directory layout, Ports and interfaces); framework wrappers in `Domain/Shared` that are not ports (`Str`, `Config`, `Console`) are removed.

### Error handling
- **Where:** 121 `catch (\Exception …)` in 53 files, mostly `Log::error($e->getMessage())` plus a generic message; failures returned as `false` or `['success' => false]`; only 4 domain exceptions
- **Fix:** context exceptions, mapping in `app/Exceptions/ExceptionHandling.php` (`rules/php.md`, Errors).

### Tests that pin structure instead of behaviour
- **Where:** 235 `class_exists`/`method_exists` assertions, 189 files using reflection, 44 `$fillable`/`$casts` assertions (e.g. `tests/Unit/Models/*`); 107 tests in `tests/Unit` extending `Tests\TestCase`; `CreatesFrontendTestEnvironment` creating permission tables and hand-written permission lists; 43 tests named `test_*` or `@test`
- **Fix:** delete structural tests, move framework tests to `tests/Feature`, use `PermissionCatalog` and migrations (`rules/testing.md`).

### Frontend
- **Where:** `resources/views` (25 files with inline `<script>`, 100 `style=""` attributes, 103 `@php` blocks, `number_format` for money), `resources/js` (6.5k lines of page scripts and `window.*` globals beside Livewire/Alpine)
- **Fix:** per `rules/frontend.md`.

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

### Client mapper maps invoices as clients
- **Where:** `app/Infrastructure/Persistence/Eloquent/Party/Mappers/EloquentClientMapper.php::toClientDTO()`
- **Problem:** loaded `invoices` are passed to `toClientDTO()` itself.
- **Fix:** map invoices with the invoice mapper (or drop them from `ClientDTO`).
