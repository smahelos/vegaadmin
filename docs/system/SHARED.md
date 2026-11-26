# Shared kernel

Status: maintained map of the code on `main`, last checked 2026-11-25. Start with [the system overview](OVERVIEW.md). What may live in Shared and how its ports are used is in `rules/architecture.md`; money and VAT in `rules/domain.md`; the use case contracts in `rules/application.md` and `rules/permissions.md`.

What every context uses: the ports to the framework, money, VAT rates, countries, units, pagination, file uploads, the statuses of expenses, and the contracts of the use case gate. Shared has no use cases of its own. Composition root: `SharedServiceProvider`, which binds every port below unless the row names another provider. Before adding a port, look here for one that exists.

## Ports

Contracts in `app/Domain/Shared/<Area>/Contracts/`, implementations in `app/Infrastructure/Shared/<Area>/` unless the row says otherwise.

| Port | Implementation | Used by |
|---|---|---|
| `ClockInterface` | `CarbonClock` | the current time in every context |
| `DateFormatterInterface` | `IcuDateFormatter` | dates shown to people (PDFs, reminder emails, lists); `DateHelper` is the older formatter still in views |
| `MoneyFormatterInterface` | `MoneyFormatter` (Domain, intl) | every amount shown to people |
| `NumberFormatterInterface` | `IcuNumberFormatter` | quantities and VAT rates shown to people (invoice PDFs, the invoice detail page) |
| `CurrencyNamesInterface`, `CountryNamesInterface` | `IntlCurrencyNames`, `IntlCountryNames` (ICU) | `CurrencyOptionsPresenter`, `CountryOptionsPresenter` |
| `CurrencyExchangeServiceInterface` | `CurrencyExchangeService` (Domain); `DeterministicCurrencyExchangeService` when `FX_DETERMINISTIC` is set | `CurrencyApplicationService` |
| `CountryServiceInterface` | `CountryService` (Domain) | `CountryApplicationService` |
| `HttpClientInterface` | `HttpClientService` | `CurrencyExchangeService`, `CountryService` |
| `CacheServiceInterface` | `CacheService` | `DashboardService`, `ProductFormDataService`, `TaxService`, `InvalidateFormDataCache`, the exchange rates and countries |
| `TransactionBoundaryInterface` | `EloquentTransactionBoundary` (`app/Infrastructure/Persistence/Eloquent/Shared/Database/`) | the gate, the handlers of `CallsOutsideDatabaseInterface` Commands and a few others that open their own (`CreateInvoiceHandler`, `CreateExpenseHandler`, …) |
| `EventPublisherInterface` | `LaravelEventPublisher`, which runs listeners after the commit | the Party Command handlers and the model observers ([overview](OVERVIEW.md#events)) |
| `LogInterface` | `LaravelLogger` | `CurrencyExchangeService`, `SubscriptionPaymentService`, `CreateGuestInvoiceHandler`, `LaravelEventPublisher` |
| `HashInterface` | `LaravelHash` | `UserPassword`, `ChangePasswordHandler` |
| `FileUploadServiceInterface`, `ImageProcessorInterface` | `LaravelFileUploadService`, `GdImageProcessor` | see [Files](#files) |
| `TaxDtoReadRepositoryInterface`, `TaxDtoWriteRepositoryInterface` | `EloquentTaxDtoRepository` over `EloquentTaxRepository` (`app/Infrastructure/Persistence/Eloquent/Shared/Tax/`) | the VAT rates of the invoice forms and `InvoiceItemInput`; the write port is unused ("Unused Shared ports") |
| `TaxServiceInterface` | `TaxService` (Domain, cached) | `Admin\ProductCrudController` |
| `TranslatorInterface`, `SessionInterface` | `LaravelTranslator`, `LaravelSessionStore` | nothing ("Unused Shared ports" in [TECH_DEBT](../TECH_DEBT.md)) |
| `Str`, `Config` | `LaravelStrAdapter`, `LaravelConfigAdapter` | `ProductCreationValidator`, `EntityLimit`; wrappers to remove ("Directory layout" in [TECH_DEBT](../TECH_DEBT.md)) |

The `Locale` port of the User context is implemented here too (`LaravelLocaleAdapter`) and bound in `UserServiceProvider` ([USER.md](USER.md)).

## Building blocks

| Block | Code | Rule |
|---|---|---|
| Money | `Money` (decimal string and `Currency`), `Currency` (the currencies a document can be in) | `rules/domain.md` |
| VAT rates | `TaxDTO`, `TaxWriteData`; the rates are rows of `taxes`, see [Reference data](#reference-data) | `rules/domain.md` |
| Units | `Unit`, stored on products and invoice items, labelled by `invoices.units.<value>` | `rules/translations.md` |
| Countries | `Country` (ISO 3166-1 alpha-2 from ICU) | — |
| Pagination | `PageRequest`, `PaginatedResult`; Livewire lists render them through the `Paginates` trait | `rules/frontend.md` |
| Notification recipient | `Recipient` | — |
| Form fields | `FieldDTO`, `FieldSetDTO`, `FieldType` (`app/Application/Shared/Form/`), used by `InvoiceCreateFieldSetFactory` | — |

## The use case gate

The contracts are in `app/Application/Shared/UseCase/`: `CommandInterface`, `QueryInterface`, `OwnedUseCase`, `Access`, `Actor`, `CallsOutsideDatabaseInterface` and `AuthorizingUseCaseRunnerInterface`. The proofs the gate issues to the ports are `ReadScope`, `WriteScope` and `AllOwnersScope` (`app/Domain/Shared/Authorization/ValueObjects/`). The gate itself, `AuthorizingUseCaseRunner` with `GrantStore`, is in `app/Infrastructure/Authorization/` and described in [USER.md](USER.md); the path of a request through it is in [the overview](OVERVIEW.md#path-of-a-request).

Livewire components share four traits in `app/Livewire/Shared/`: `ActsForUser` (the signed-in user and the gate on every action), `ThrottlesCreation` (the `throttle:create` bucket), `Sorts` (a sort key from an allow-list) and `Paginates`.

## Reference data

| Entity | Table | Admin | Code |
|---|---|---|---|
| VAT rate | `taxes` (`database/migrations/2026_10_25_000005_create_payment_reference_tables.php`) | `Admin\TaxCrudController`, `tax.*` | `App\Models\Tax`; `TaxObserver` (bound in `InvoiceServiceProvider`) publishes `FormDataChanged` |
| Status and status category | `statuses`, `status_categories` (`database/migrations/2026_10_25_000004_create_status_tables.php`) | `Admin\StatusCrudController`, `Admin\StatusCategoryCrudController`, `status.*` | `App\Models\Status`, `App\Models\StatusCategory`; filter options from `StatusFormFields` |

A fresh database gets both from `ReferenceDataSeeder`. Expenses store their status as a `status_id` of a `statuses` row, labelled by the model: "Status labels are translated in the model" in [TECH_DEBT](../TECH_DEBT.md). Invoices store their `InvoiceStatus` in `invoices.payment_status` ([INVOICE.md](INVOICE.md)).

## Files

`FileUploadServiceInterface::handleFileUpload()` validates a file by the context's limits in `config/file_upload.php` (`FileUploadConfig::forContext()`: `invoice_logo`, `party_logo`, `product_image`, …), stores it on a disk and makes a thumbnail through `ImageProcessorInterface`. Identical files are stored once (`FileHash`); `discardRolledBackUpload()` removes a file stored for a transaction that rolled back, unless another record holds it. Callers:

- `PartyLogoApplicationService`, `ExpenseAttachments` and `InvoiceRequestAssembler` (which wraps the request file in `IncomingFile` through `IncomingFileFactory`);
- the models `Product`, `Page` and `Expense` through the trait `HasFileUploads`, a known gap ("Business logic in models" in [TECH_DEBT](../TECH_DEBT.md)).

## Public API

`routes/api.php`, without sign-in, rate limited by `throttle:public_api`:

| Route | Code | Caller |
|---|---|---|
| `/api/currencies/exchange-rate` | `CurrencyController::getExchangeRate()` → `CurrencyApplicationService` | `resources/js/currency-manager.js` |
| `/api/currencies/convert` | `CurrencyController::convertCurrency()` | none outside the tests |
| `/api/countries`, `/api/countries/{code}` | `CountryController` → `CountryApplicationService` → `CountryService` | none outside the tests |

`CurrencyExchangeService` reads open.er-api.com, keeps the rates for an hour through `CacheServiceInterface` and retries with a circuit breaker set in `config/exchange.php`. `CountryService` reads restcountries.com; the country selects of the forms use `CountryOptionsPresenter` (ICU) instead. Known gaps: "Public country and conversion API without a caller", "Currency conversion of invoice items happens in the browser" and "Resilience of external calls" in [TECH_DEBT](../TECH_DEBT.md).

## Events

`FormDataChanged`, published by `ProductObserver`, `ProductCategoryObserver` and `TaxObserver`; `InvalidateFormDataCache` (`app/Domain/Shared/Listeners/`) clears the `form_data` cache tags. Wired in `EventServiceProvider`; the observers are "Move cache invalidation from observers to domain event handlers" in [TECH_DEBT](../TECH_DEBT.md).
