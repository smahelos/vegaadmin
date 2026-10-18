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

### Rewrite the Backpack PRO templates still in use
- **Where:** `resources/views/vendor/backpack/crud/*`, `public/packages/*`
- **Problem:** templates copied from Backpack PRO (not licensed, not in `composer.json`) in commit `a007295e` are still used: field `datetime_picker` (the filters, the select2 fields and the relationship field and column are already rewritten). As overrides of package views they would also shadow the Backpack 7 views. The unused copies were moved out of the repository (local `backup/`, gitignored).
- **Fix:** rewrite them as Billex's own templates on the free Backpack API before upgrading to Backpack 7; then review the remaining overrides (`columns/closure`, identical `inc/filters_navbar`, own `multilingual_*`, `image_with_preview`, `files_list`, theme/UI overrides).
