# Technical Debt

Known gaps between the code and the intended architecture (see `CLAUDE.md`, `docs/adr/`). Most items are unfinished steps of the DDD migration (commits `a65aaffb` 2025-09-09 and `7e999b2a` 2025-10-12 in the original Bitbucket history): the target class exists, but the code was never switched over to it. Remove an item once it is resolved.

## Security

### Public statistics endpoints expose all users' revenue
- **Where:** `routes/api.php` (statistics routes in the public `web, throttle` group), `app/Infrastructure/Persistence/Eloquent/Analytics/Repositories/EloquentApiStatisticsRepository.php`
- **Problem:** `/api/monthly-revenue` and the other statistics routes need no login; `getMonthlyRevenueQuery()` and `getExpenseQuery()` have no user filter, so a guest gets revenue summed over every user.
- **Fix:** move the routes behind `api.require.frontend`, scope every query by the user passed in from Application (no `Auth::id()` in the repository).

### State changes over GET
- **Where:** `routes/web.php` (`client/{id}/set-default`, `supplier/{id}/set-default`, `invoices/delete/{token}`), `routes/backpack/custom.php` (`cron-task/{id}/run`)
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

## Invoice

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
- **Problem:** the due date (`issue_date + due_in`) is computed separately in `Invoice` model accessor, `InvoicePrintDataBuilder`, `TemporaryInvoicePrintDataFactory` and Livewire list ordering; `InvoiceFormApplicationService` and `InvoiceCrudApplicationService` hardcode `now()->addDays(14)`.
- **Fix:** Domain/Application calculations use the value object.

### Inconsistent default tax rate
- **Where:** `InvoiceCrudApplicationService::processProductsAndCalculateTotals()` vs `InvoiceProductWriteData::fromArray()`
- **Problem:** invoice totals assume 0 % when `tax_rate` is missing, stored product rows assume 21 %. Totals and rows can disagree.
- **Fix:** one rule in the Domain; totals computed from `InvoiceProductWriteData`.

### Format invoice amounts through a presenter
- **Where:** `resources/views/frontend/invoices/show.blade.php`
- **Problem:** amounts are formatted in Blade with `number_format(..., 2, ',', ' ')` and fallback logic; the Czech format is used for every locale.
- **Fix:** an invoice detail presenter using `MoneyFormatterInterface`, like `app/Application/Analytics/Presenters/*`.

## Payment

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

## Party / Admin

### Inactive banks are still offered
- **Where:** `banks.active`, `app/Infrastructure/Persistence/Eloquent/Payment/Repositories/EloquentBankRepository.php` (`getAllBanks()`)
- **Problem:** the admin can mark a bank inactive, but the bank list for supplier forms filters only by country, so the flag changes nothing.
- **Fix:** list only active banks for the supplier forms (the admin list keeps all), or drop the column.

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

## Product

### Product SKU
- **Where:** `app/Domain/Product/ValueObjects/ProductSku.php`
- **Problem:** value object prepared for a feature that does not exist yet (no `sku` column).
- **Fix:** use it when SKU is added, or delete it.

## Cross-cutting

### Move cache invalidation from observers to domain event handlers
- **Where:** `app/Infrastructure/Persistence/Eloquent/*/Observers`, `app/Domain/Analytics/Events/AnalyticsDataChanged.php`, `app/Domain/Analytics/Events/Handlers/InvalidateAnalyticsCache.php`
- **Problem:** events are not yet used as `.claude/rules/architecture.md` (Events) requires. Cache invalidation runs through observers → `UserDataChanged`; `AnalyticsDataChanged` is never published, so its registered handler never runs. Events `ClientDeleted`, `ClientSetAsDefault`, `SupplierDeleted`, `SupplierSetAsDefault`, `SupplierUpdated`, `ProductDeleted`, `ProductSetAsDefault`, `ProductUpdated` exist but are never published.
- **Fix:** publish the entity events from the domain services, let `InvalidateAnalyticsCache` handle them, then remove the observers and `AnalyticsDataChanged`.

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
- **Problem:** Pint has no `pint.json` and the codebase is not formatted by it (without explicit paths it even aborts on the unreadable `data/redis` directory); there is no static analysis.
- **Fix:** add `pint.json` (excluding `data/`, `storage/`, `vendor/`, `node_modules/`) and format the whole codebase in one commit; install Larastan without a baseline file, start at the highest level that can be made clean, raise it as contexts are converted (target: max); run both in CI.

### `strict_types` and `final`
- **Where:** all of `app/`, `tests/`, `database/` (0 files declare `strict_types`, 9 classes are `final`)
- **Fix:** add `declare(strict_types=1)` and `final` (except abstract bases and Eloquent models, `rules/php.md`); the test suite catches the coercions this exposes.

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
- **Where:** `app/Models/Invoice.php` (totals, `syncProductsFromJson()`, due date, logging, `HasRoles`, `belongsToMany(...)` to `clients`/`suppliers`/`statuses` tables), `invoices.invoice_text` holding a JSON copy of `invoice_products`, `$guarded` in 6 models
- **Fix:** logic into Domain, one storage for line items, `$fillable` and typed relations (`rules/persistence.md`).

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
- **Where:** `lang/*` (23 files, 2076 keys in cs), 623 `trans()` calls; `translations:check … --compare=cs` reports 15 missing keys/files in de, 13 in en, 14 in sk (e.g. `admin.database.*`, `clients.messages.limit_exceeded`); `lang/en/{auth,pagination,passwords}.php` missing in cs/de/sk; `lang/{cs,de,sk}/ddd_guard.php` translates developer-tool output; `lang/*/emails.php` calls `config()`
- **Problem:**
  - Texts are split by entity and by screen area at once: `admin.php` holds 878 keys in 35 sections, 14 of them mirror a context file (`admin.clients` ↔ `clients.php`, `admin.products`, `admin.invoices`, `admin.suppliers`, `admin.pages`, `admin.subscriptions`, …). The same field gets two texts (`admin.clients.street` "Ulice" vs `clients.fields.street` "Ulice a číslo").
  - 137 keys used in code do not exist, so users see the key (e.g. `admin.products.*` 14, `general.entity_limits.*` 11, `admin.validation.*` 10, `admin.status_categories.*` 10, `admin.product_categories.*` 8 used by `ProductCategoryRequest`). About 665 keys are never referenced literally (an estimate: some keys are built dynamically).
  - 48 form requests define `messages()`, mostly repeating Laravel's standard messages already translated in `lang/*/validation.php` ("Název je povinný" 7×).
  - File names mix singular and plural (`subscription.php`, `tax.php` vs `suppliers.php`, `payment_methods.php`); `payment_statuses.php` beside `statuses.php`.
- **Fix (decided 2026-10-22):**
  - One file per bounded context or entity, read by frontend and admin alike, plural names; the same sections in each: `entity` (singular/plural), `fields` (labels, columns and validation attributes), `hints`, `placeholders`, `actions`, `messages`, `validation` (only rules the standard messages cannot express), `statuses` (enum labels). A sub-entity is a section of its parent (`products.categories.*`) or its own file when large.
  - `admin.php` only for admin screens that are not entities (menu, dashboard, database maintenance, cache, UELS dashboard); large parts in `lang/*/admin/<area>.php` (`__('admin/database.title')`).
  - `general.php` only for interface words (save, cancel, yes/no, created/updated, pagination, empty states). Field labels stay in the entity file even when the word repeats across entities.
  - Requests name fields through `attributes()` from `<entity>.fields.*` and drop `messages()` that repeat the standard ones.
  - `__()` only, all keys in all locales, developer output in plain English, no `config()` in language files (pass values as parameters).
  - A test that every key used in code exists in cs; refine `rules/translations.md` ("a file per bounded context or screen area" lets screen-area files take over entity texts).
  - Convert context by context with `/convert-to-standard`; fix the missing keys first, users see them.

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
