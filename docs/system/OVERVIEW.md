# System overview

Status: maintained map of the code on `main`, last checked 2026-11-21. It describes how Billex works today; how code must be written is in [the rules](../../.claude/rules/), why it was decided in [the ADRs](../adr/), and known gaps in [TECH_DEBT](../TECH_DEBT.md). When this page and the code disagree, the code is right and the page is fixed in the same pull request.

Read this page first, then the page of the context you change (table below). A context without a page is found through its provider, `app/Providers/<Context>ServiceProvider.php`.

## Bounded contexts

| Context | Owns | Page |
|---|---|---|
| Analytics | dashboards and statistics | — |
| Content | public CMS pages and their categories | — |
| Expense | expenses, the received documents | — |
| Invoice | issued invoices, their items, PDFs, payment reminders | — |
| Party | the user's businesses and their contacts, bank accounts, the parties stored on documents | [PARTY.md](PARTY.md) |
| Payment | subscriptions, payment gateways, QR payments, banks, payment methods | — |
| Product | products and product categories | — |
| System | admin system screens: caches, cron tasks, database maintenance | — |
| User | accounts, roles and permissions, entity limits, locale | [USER.md](USER.md) |
| Shared | money, tax, time, status, files, events and the use case gate contracts | — |

Every context uses User (permissions, limits) and Shared. Beyond those, Invoice uses Party, Product and Payment; Expense and Analytics use Party; Payment uses Party and Invoice; Product uses Invoice. How one context may use another is in `rules/architecture.md` (Bounded contexts).

## Entry points

| Entry | Routes and code | Reaches a use case through |
|---|---|---|
| Frontend pages | `routes/web.php`, `app/Http/Controllers/Frontend/` | the `allowed` middleware on the route, then `AuthorizingUseCaseRunnerInterface::run()` with `Actor::user()` |
| Livewire components | `app/Livewire/<Context>/` | the same gate; the action asks the use case's declaration on its first line |
| Admin (Backpack) | `routes/backpack/custom.php`, `app/Http/Controllers/Admin/` | `setup()` allows the operations whose permission the admin holds; owned records are written by Commands in overridden `store`/`update`, reference data by Backpack through the model |
| API | `routes/api.php`, `app/Http/Controllers/Api/` | the session of the frontend or admin guard and the `allowed` middleware |
| Console and schedule | `app/Console/Commands/`, `routes/console.php` | `Actor::system()`; every scheduled command is a cron task managed in the admin |

## Path of a request

1. The route names its use case in the `allowed` middleware, which refuses with 403 before validation (`rules/http.md`).
2. The form request validates the input; the controller builds the Command or Query from it.
3. The gate, `AuthorizingUseCaseRunner`, checks the permission and the owner, issues the scope, opens the transaction and calls the handler (`rules/permissions.md`, `rules/application.md`).
4. The handler works with domain services and ports; a use case that creates a limited record calls `EntityLimit` first ([USER.md](USER.md#entity-limits)).
5. The ports are implemented by Eloquent repositories and mappers in `app/Infrastructure/Persistence/Eloquent/<Context>/` (`rules/persistence.md`).
6. Domain events go through `EventPublisherInterface`; Laravel listeners run after the commit (`rules/architecture.md`, Events).

## Events

Wired in `EventServiceProvider`.

| Event | Published by | Reaction |
|---|---|---|
| Party events (`BusinessCreated`, `ContactArchived`, `DefaultBusinessChanged`, …) | the Party command handlers | `InvalidateOwnerDashboard` clears the owner's dashboard cache |
| `UserDataChanged` | `InvoiceObserver` | `InvalidateUserCache` clears the user's dashboard cache |
| `FormDataChanged` | `ProductObserver`, `ProductCategoryObserver`, `TaxObserver` | `InvalidateFormDataCache` clears cached form options |

The observers are a known gap: "Move cache invalidation from observers to domain event handlers" in [TECH_DEBT](../TECH_DEBT.md).
