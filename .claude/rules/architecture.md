---
paths:
  - "app/**/*.php"
  - "bootstrap/providers.php"
  - "config/ddd_guard.php"
  - "architecture/**"
---

# Architecture

Code examples of every layer in its target shape: `references/layers.md` — read it before adding a repository, mapper, application service, data class or controller action.

## Layers and allowed dependencies

| Layer | May depend on | Never on |
|---|---|---|
| `app/Domain/<Context>` | Domain of its own context, `Domain/Shared`, Domain contracts/DTOs/value objects of another context | Laravel (`Illuminate\*`, helpers, facades), `App\Models`, Application, Infrastructure, delivery |
| `app/Application/<Context>` | Domain (any context) | `Illuminate\Http`, `Request`, facades and helpers (`now()`, `auth()`, `config()`, `session()`), `App\Models`, Infrastructure, delivery |
| `app/Infrastructure` | Domain, Application, Laravel, vendors, `App\Models` | delivery |
| Delivery: `app/Http`, `app/Livewire`, `app/Console`, `app/View` | Application, Domain DTOs / value objects / enums / exceptions, Laravel | `App\Models`, Eloquent queries, repositories, Infrastructure classes — the one exception is Backpack CRUD controllers, which work on models by design (`admin.md`) |

- Time, logging, cache, translation and other framework services reach Domain and Application only through the ports in `app/Domain/Shared/*/Contracts` (`ClockInterface`, `LogInterface`, …), implemented in `app/Infrastructure/Shared`.
- The current user reaches Application as an argument (`UserId`), passed in by delivery — Application never asks `auth()`.
- Livewire components are delivery exactly like controllers: they call Application services and render DTOs; a component never builds a query.

## Ports and interfaces

An interface exists only for a **port**:

1. a contract that Domain or Application defines and Infrastructure implements (repositories, gateways, PDF/QR renderers, clock, external APIs), or
2. a contract with two or more implementations (QR payment providers per country).

Everything else — application services, pure domain services, presenters, assemblers — is a concrete `final` class without an interface, resolved by autowiring with no container binding. Tests replace ports with fakes and run the real services (`testing.md`).

## Data classes

| Role | Name | Example |
|---|---|---|
| Read model of an entity, returned by a repository | `<Entity>DTO` | `InvoiceDTO`, `ContactDTO` |
| Input for creating or updating an entity | `<Entity>WriteData` | `InvoiceWriteData` |
| Read-only projection for one purpose (print, statistics, form, external format) | `<Purpose><Entity>DTO` | `PrintableInvoiceDTO`, `MonthlyStatDTO`, `QrPaymentDTO` |
| Outcome of an application operation (status, messages, created id) | `<Operation>Result` | `InvoiceActionResult`, `RefundResult` |
| Generic building block shared across contexts | concept name, no suffix | `PageRequest`, `PaginatedResult`, `Recipient` |

- `Data` is reserved for write input: anything only read is a `DTO`. One suffix per class (`RefundResult`, not `RefundResultDTO`).
- A data class never wraps an untyped request array (`array $validated`); the form request's validated input is turned into a `WriteData` by the delivery layer or an assembler.
- DTOs carry typed values: `Money` for amounts, `DateTimeImmutable` for dates, enums for statuses, `<Entity>Id` value objects for identifiers.

## Persistence path

Repositories are Domain ports: `app/Domain/<Context>/Contracts/<Entity>{Read,Write}RepositoryInterface`, returning `<Entity>DTO` and accepting `<Entity>WriteData`, implemented in `app/Infrastructure/Persistence/Eloquent/<Context>/Repositories` with a mapper (`persistence.md`). Application, delivery and Domain never receive an Eloquent model, query builder or `Collection` of models.

## Bounded contexts

- A context uses another context only through that context's Domain contracts, DTOs, value objects and events — never through its Infrastructure or models.
- Shared kernel (`Domain/Shared`) holds only what several contexts need: money, tax, status, time, events, pagination, files. A concept used by one context lives in that context.
- Business (the user's own identity) and contact (anyone the user does business with) are the two entities of the Party context (`docs/adr/D-045-businesses-and-contacts.md`). Supplier and customer are roles on a document, never entity names. Behaviour both entities share (party profile, address, bank accounts, duplicates, archiving, capacity) is written once and used by both; a near-identical business/contact pair of classes is a defect to merge.

## Events

- A state change other contexts react to is a domain event (`app/Domain/<Context>/Events/<Entity><PastTense>`), published through `EventPublisherInterface` by the service that made the change. Handlers live in `Events/Handlers/` of the reacting context.
- Where a reaction runs follows from its effect (`LaravelEventPublisher`), so nothing is lost or left behind between the commit and the reaction:
  - a write to the Billex database (usage counters, audit) is a domain handler (`DomainEventHandler`); it runs inside the use case's transaction and commits or rolls back with it;
  - an effect outside the database that can be rebuilt (cache invalidation) is a Laravel listener; it runs after the commit and not at all on a rollback, and its failure is logged without failing the committed use case. If the process dies between the commit and the listener, the cache stays stale until its TTL expires, so every cache entry has one;
  - work that must not be lost (mail, external calls) is a queued job on the `database` connection, dispatched by the use case or a domain handler: the job row is stored in the same transaction and the worker runs it after the commit. A listener of a domain event is never queued (`ShouldQueue`) and dispatches no jobs: it runs after the commit, so its job would be lost if the process died in between.
- Eloquent observers carry no business logic or cache invalidation; that belongs in event handlers (conversion tracked in `docs/TECH_DEBT.md`).
- Only events that are actually published exist; an event without a publisher is deleted.

## Composition roots

Each context has one provider, `App\Providers\<Context>ServiceProvider` (ADR `docs/adr/D-042-context-service-providers.md`): ports bound in `registerDomainServices()` / `registerInfrastructure()` / `registerApplicationServices()`, observers and policies registered in `boot()`. A contract from `App\Domain\<Context>` or `App\Application\<Context>` is bound exactly once, in its own context's provider (`tests/Feature/Providers/ContextProviderBindingOriginTest.php`). After adding or removing a binding, refresh the snapshot: `docker exec BILLEX-app php artisan ddd:providers:snapshot --write`.

## Directory layout

- Domain: `Contracts/`, `DTO/`, `Enums/`, `Events/` (+ `Handlers/`), `Exceptions/`, `Services/`, `ValueObjects/`.
- Application: `Services/`, `DTO/` (Results and projections owned by the use case), `Presenters/`, `Contracts/` only for ports Application defines.
- Infrastructure: by technical concern, then context — `Persistence/Eloquent/<Context>/{Repositories,Mappers}`, `Gateways/<Context>/`, `Authorization/`, `Forms/<Context>/`, `Notifications/`, `Renderers/`, `Shared/`.
- Directory names are plural where they hold a kind of class (`Enums`, `Exceptions`), context names are singular and match the bounded context (`Forms/Product`, not `Forms/Products`). No grab-bag subdirectories such as `Specialized/`.

## Architecture guards

CI runs `ddd:guard`, the provider snapshot diff and the controller-thinness / Eloquent-usage baselines (`.github/workflows/static-analysis.yml`). Baselines are regenerated by their artisan commands only for an intentional change (`architecture/baselines/README.md`), never edited by hand. These guards are scaffolding from the DDD migration and will be replaced by Larastan plus architecture tests of the dependency table above (`docs/TECH_DEBT.md`); until then, keep them green.
