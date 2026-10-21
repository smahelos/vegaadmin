# Technical Debt

Known gaps between the code and the intended architecture (see `CLAUDE.md`, `docs/adr/`). Most items are unfinished steps of the DDD migration (commits `a65aaffb` 2025-09-09 and `7e999b2a` 2025-10-12 in the original Bitbucket history): the target class exists, but the code was never switched over to it. Remove an item once it is resolved.

## Invoice

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

### Payment adapter guard checks nothing
- **Where:** `app/Console/Commands/PaymentAdapterGuardCommand.php`, `app/Domain/Payment/Services/GatewayRegistry.php`
- **Problem:** `php artisan payment:adapter-guard` reports "No gateways registered" in the console, so the CI step passes without validating any gateway.
- **Fix:** register the gateways (GoPay) where the guard can see them, or let the guard resolve the registry the same way the application does.

## Party / Admin

### Admin bank CRUD validates with the frontend request
- **Where:** `app/Http/Controllers/Admin/BankCrudController.php`
- **Problem:** uses `App\Http\Requests\BankRequest` (only `auth()->check()`) instead of `App\Http\Requests\Admin\BankRequest`.
- **Fix:** switch to the admin request; resolve together with the entity limit rules for admins.

### Unused frontend form requests with nonexistent permissions
- **Where:** `app/Http/Requests/{TaxRequest,StatusRequest,StatusCategoryRequest,SubscriptionPlanFeatureRequest}.php`, non-profile branch of `app/Http/Requests/UserRequest.php`
- **Problem:** no route uses these requests (the admin uses the `Admin\` variants); they check `frontend.can_create_edit_{tax,status,subscription_plan_feature,user}`, which are not in `PermissionCatalog`. `PermissionCatalogCoverageTest` allowlists them as unreachable.
- **Fix:** delete the unused requests and the unreachable branch, then drop the allowlist.

### Admin list columns build display text by hand
- **Where:** `app/Http/Controllers/Admin/{EntityUsage,EntityLimit,PerformanceMetric,DatabaseMaintenanceLog}CrudController.php`; badge accessors in `app/Models/{ArchivePolicy,DatabaseHealthAlert,DatabaseHealthMetric,DatabaseMaintenanceLog,MysqlOptimizationLog}.php`; hardcoded labels also in the `Client`, `Supplier`, `Product`, `Invoice`, `Status`, `Subscription` and `ExpenseCategory` CRUD controllers
- **Problem:** amounts and sizes are formatted with `'€' . number_format(...)` and private `formatBytes()` helpers, ignoring the locale and the entity's currency. Labels and cell texts are hardcoded English (`->label('User')`, `'Anonymous'`, `'Pending'`). The maintenance results and performance metadata closures put MySQL output (`Msg_text`, storage engine, metadata values) into HTML unescaped. Models return badge HTML with English text (`getStatusBadgeAttribute()` and similar).
- **Fix:** amounts through `MoneyFormatterInterface`, labels and texts as translation keys, every interpolated value through `e()` or a Blade column view, badge HTML out of the models into column views with the status as an enum (`rules/admin.md`, `rules/persistence.md`, `rules/translations.md`).

## Product

### Product SKU
- **Where:** `app/Domain/Product/ValueObjects/ProductSku.php`
- **Problem:** value object prepared for a feature that does not exist yet (no `sku` column).
- **Fix:** use it when SKU is added, or delete it.

## Cross-cutting

### Move cache invalidation from observers to domain event handlers
- **Where:** `app/Infrastructure/Persistence/Eloquent/*/Observers`, `app/Domain/Analytics/Events/AnalyticsDataChanged.php`, `app/Domain/Analytics/Events/Handlers/InvalidateAnalyticsCache.php`
- **Problem:** the long-term step of `docs/DOMAIN_EVENTS_MIGRATION.md` is unfinished. Cache invalidation runs through observers → `UserDataChanged`; `AnalyticsDataChanged` is never published, so its registered handler never runs. Events `ClientDeleted`, `ClientSetAsDefault`, `SupplierDeleted`, `SupplierSetAsDefault`, `SupplierUpdated`, `ProductDeleted`, `ProductSetAsDefault`, `ProductUpdated` exist but are never published.
- **Fix:** publish the entity events from the domain services, let `InvalidateAnalyticsCache` handle them, then remove the observers and `AnalyticsDataChanged`.

### Resilience of external calls
- **Where:** `app/Http/Controllers/Api/AresLookupController.php`, `app/Infrastructure/Shared/Http/Services/HttpClientService.php`, `app/Domain/Analytics/Services/BaseAnalyticsExternalService.php`, `config/analytics.php`
- **Problem:** ARES is called with `Http::get()` directly from a controller without a timeout; exchange rates and countries go through `HttpClientService` without timeouts. A circuit breaker exists only in the unused Analytics base class.
- **Fix:** timeouts and a circuit breaker in `Shared` around `HttpClientService`; move the ARES call into a service.

### Review the remaining Backpack overrides before the v7 upgrade
- **Where:** `resources/views/vendor/backpack/*`
- **Problem:** no template copied from Backpack PRO (commit `a007295e`) is used any more; Billex's own field, column and filter types live in `resources/views/admin/{fields,columns,filters}`. What stays in `resources/views/vendor/backpack/` overrides views of the installed packages and would shadow their Backpack 7 versions: `theme-tabler/layouts/_vertical/sidebar_content_top` (adds the language switcher), `ui/dashboard`, `ui/inc/menu_items`.
- **Fix:** before upgrading to Backpack 7, compare each override with the v7 package view and port the Billex changes onto it (or drop the override); check Billex's own types in `resources/views/admin/*` against the v7 field/column/filter API.

### Laravel pinned below 12.69.3 until Backpack 7
- **Where:** `composer.json` (`laravel/framework`), `tests/Feature/Http/Middleware/AuthenticateSessionFeatureTest.php`
- **Problem:** Laravel 12.69.3+ stores an HMAC of the password hash in the session on login; Backpack 6's `AuthenticateSession` compares it with the raw hash and logs every admin out right after login. The fix (Laravel-Backpack/CRUD#6086) exists only in Backpack 7. While pinned, Billex gets no newer Laravel releases, security fixes included.
- **Fix:** upgrade to Backpack 7, then restore the `^12.0` constraint; `AuthenticateSessionFeatureTest` must keep passing. If a Laravel security advisory needs ≥12.69.3 before that, extend Backpack's `AuthenticateSession` with the HMAC check from #6086 instead.
