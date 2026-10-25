# D-042: One Service Provider per Bounded Context

Status: Accepted 2026-10-16
Supersedes: the layer-provider consolidation from commit `7e999b2a` (2025-10-12, no ADR)
Reinstates: D-039 (AppServiceProvider Decomposition, 2025-08-26), with the amendments below

## Context

DI wiring has moved three times:

1. **Until August 2025**: everything in `AppServiceProvider`.
2. **D-039 (2025-08-26)**: one `<Domain>DomainServiceProvider` per domain (Analytics, Content, Invoice, Party, Payment, Product, Shared, User). D-039 explicitly rejected "merge thin domain providers back" because it would invite re-centralization. Structural tests asserted that each contract is bound in its own domain provider.
3. **Commit `7e999b2a` (2025-10-12, "Last part of DDD migrations")**: the Infrastructure layer was introduced (Eloquent repositories, observers and `GoPayGateway` moved out of `app/Domain`). In the same commit the domain providers were replaced by layer providers: `DomainServiceProvider`, `App\Infrastructure\InfrastructureServiceProvider`, `App\Application\ApplicationServiceProvider`. No ADR was written, and `docs/ddd` (including D-039) was deleted.

The likely motivation for step 3 was layer purity: once Eloquent left `app/Domain`, a "domain" provider binding Eloquent classes looked inconsistent. The change was left half-done:

- `PaymentDomainServiceProvider` survived and still bound 13 contracts that the layer providers bound as well (same implementations, so it was functionally harmless but ambiguous).
- The binding-origin test still encoded the D-039 convention, so the duplicates were kept to satisfy it.
- `ddd:providers:snapshot` only scanned `app/Providers/*DomainServiceProvider.php`, so the CI snapshot diff reported every repository and adapter contract as unbound.
- `DomainServiceProvider::registerPaymentDomainServices()` was an empty placeholder.

## Decision

Every bounded context has exactly one service provider: `App\Providers\<Context>ServiceProvider`. It is the context's **composition root** and wires all three layers of that context:

```
register()
  registerDomainServices()        // App\Domain\<Context> contracts -> domain services
  registerInfrastructure()        // repositories, framework adapters, gateways, notifiers
  registerApplicationServices()   // use cases, presenters, mappers
boot()                            // observers, policies, static VO configuration of the context
```

Contexts and providers:

| Context | Provider |
|---|---|
| Shared kernel (money, files, time, status, tax, framework adapters) | `SharedServiceProvider` |
| Analytics | `AnalyticsServiceProvider` |
| Content | `ContentServiceProvider` |
| Invoice | `InvoiceServiceProvider` |
| Party | `PartyServiceProvider` |
| Payment | `PaymentServiceProvider` |
| Product | `ProductServiceProvider` |
| User | `UserServiceProvider` |

The context of a contract is the second namespace segment: `App\Domain\<Context>\...` or `App\Application\<Context>\...` is bound in `App\Providers\<Context>ServiceProvider`. Infrastructure-namespaced abstracts (e.g. `App\Infrastructure\Interfaces\Product\...`) follow the context segment in their namespace.

Framework-level providers keep their current roles: `AppServiceProvider` (helpers, Backpack overrides, Blade), `AuthServiceProvider` (policies), `EventServiceProvider`, `RouteServiceProvider`, `BladeServiceProvider`, `UIServiceProvider`, `DomainEventsServiceProvider`, `ThirdPartyServiceProvider`.

### Amendments to D-039

- **Name `<Context>ServiceProvider`, not `<Domain>DomainServiceProvider`.** The provider is not part of the domain layer; the old name suggested it was, which is what made binding Infrastructure classes from it look wrong.
- **A provider may reference every layer of its context.** Layer purity is enforced by namespaces, `ddd:guard` and the architecture tests, not by which file holds a binding.

## Rationale

- **The unit of change is the context, not the layer.** Adding a payment gateway touches Payment's domain, application and infrastructure code; it now touches one provider instead of three (the old `InfrastructureServiceProvider` alone mixed 8 contexts in over 400 lines).
- **Discoverability.** All wiring of a context is in one file named after it (the original D-039 argument).
- **Module extraction.** A context with its own provider can later move into a module or package without untangling shared layer providers.
- **Growth ahead.** Payments are unfinished (more gateways, gateway registration, webhooks); that context needs room to grow.
- **Performance is not a factor.** Registering a binding stores a closure; 3 vs. 9 providers is not measurable.

## Alternatives Considered

| Option | Reason rejected |
|---|---|
| Keep layer providers (state after `7e999b2a`) | Optimizes for swapping a whole layer at once (e.g. leaving Eloquent), which is not a goal; spreads every context change across three files |
| Per-layer provider per context (`PaymentInfrastructureServiceProvider`, …) | Up to 24 providers with no added safety; layer rules are already enforced elsewhere |
| Auto-discovery of providers in context folders | Implicit, harder to read boot order; D-039 already rejected it |

## Consequences

- `DomainServiceProvider`, `PaymentDomainServiceProvider`, `InfrastructureServiceProvider` and `ApplicationServiceProvider` are removed; `bootstrap/providers.php` lists the context providers.
- The 13 duplicated Payment bindings are gone; each contract is bound exactly once.
- Runtime container state is unchanged: all 134 `App\` bindings resolve to the same classes with the same sharing, and observers, policies and the `UserPassword` hasher are registered as before (verified by dumping the container before and after the change).

## Enforcement

- `tests/Feature/Providers/ContextProviderBindingOriginTest.php`: every Domain/Application contract is bound in its context provider and in no other provider. It reuses the binding discovery of `ddd:providers:snapshot`, so CI and tests share one parser.
- `ddd:providers:snapshot` scans all app providers listed in `bootstrap/providers.php` and contracts in `app/Domain` and `app/Application`; the CI diff fails on a new contract without a binding and on duplicate bindings. The baseline `architecture/baselines/provider_snapshot.json` was regenerated for this change.
- `tests/Feature/Providers/DomainServiceProviderBindingsTest.php`: every contract is bound and resolvable.

## Adding a binding

1. Identify the context from the contract namespace.
2. Add the binding to the matching `register*()` method of `App\Providers\<Context>ServiceProvider`.
3. Run `php artisan test tests/Feature/Providers` and `php artisan ddd:providers:snapshot --diff`; for a new contract, refresh the snapshot with `--write` and commit it.
