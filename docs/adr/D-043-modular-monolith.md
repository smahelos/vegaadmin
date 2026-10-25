# D-043: Modular Monolith, Satellite Services Only at a Narrow Seam

Status: Accepted 2026-10-24

## Context

Billex is one Laravel application organised by bounded context (Analytics, Content, Invoice, Party, Payment, Product, User, Shared) on one MySQL database. Planned extensions, such as bank integrations (Fio API, PSD2 / open banking, payment webhooks) and accounting exports, raised the question of splitting it into microservices with a message broker (RabbitMQ) between them.

The contexts are tightly coupled today:

- Domain and Application imports (2026-10-24): Invoice depends on User (22), Payment (7), Party (5) and Product (5); Product depends back on Invoice (`InvoiceProductDTO`, `InvoiceProductWriteData`, `InvoiceId`); User depends on Party and Analytics.
- One transaction covers issuing an invoice: entity limit check (UELS), invoice number and variable symbol, items with products, usage counters.
- Foreign keys cross contexts (`invoices.client_id`, `invoices.payment_method_id`, …), and Analytics joins invoices, clients, statuses, payment methods and expenses in one SQL query.
- The Backpack admin and spatie/laravel-permission work on the Eloquent models of the one database.

## Decision

Billex stays a **modular monolith**: one application, one database, bounded contexts separated by namespaces, ports and the architecture guards.

A part may leave the monolith as a **satellite service** only when all of these hold:

1. Its interface to the core is narrow and expressible as events or a small API (e.g. "payment received: variable symbol, amount, account").
2. It needs no transaction shared with the core.
3. It has its own reason to be separate: a different runtime or scaling profile, a separate security perimeter for secrets, or an external party that consumes it.

Candidates if the need arises: bank integration (polling, webhooks, consent renewal, bank tokens), accounting export, PDF rendering, notifications. The core (Invoice, Party, Product, User, subscriptions) is not split.

A message broker is introduced with the first satellite, not before. RabbitMQ is the expected choice then: the core publishes events from an outbox to an exchange, each satellite has its own queue (fanout), dead-letter queues and delivery confirmations guard against loss. Until then, queued work runs on Laravel Queue (TECH_DEBT "No queue worker").

## Rationale

- **Transactions guard money.** Splitting the core turns one transaction into a saga with compensations; entity limits become eventually consistent (two concurrent requests can exceed a plan limit) and numbering needs cross-service coordination, in the high-risk areas of `CLAUDE.md`.
- **Request fan-out.** The invoice form needs client, supplier, products, payment methods, permissions and limits; as services that is 4–5 synchronous calls per request, each with timeouts, retries and a circuit breaker, and an outage of one breaks the form.
- **The admin comes for free only on one database.** Backpack CRUD works on Eloquent models; per-service admins or an API-driven admin would replace it.
- **Operations scale with services.** Deployments, CI pipelines, versioned contracts, distributed logging and tracing, and the local Docker stack all multiply.
- **Microservices solve team scaling.** Billex has one developer; independent deployment by separate teams brings nothing.
- **The broker does not make bank integrations reliable.** Banks are reached over HTTP; reliability comes from idempotency (bank transaction id), an inbox table for webhooks, re-polling as recovery and retries with backoff, all of which work on any queue.

## Alternatives Considered

| Option | Reason rejected |
|---|---|
| Microservices per bounded context | Loses transactions across tightly coupled contexts, the admin and permissions; operational cost without a team that needs independent deployment |
| RabbitMQ now, inside the monolith | Routing and fanout have no consumer yet; an extra container and a third-party queue driver for what Laravel Queue already does |

## Consequences

- The work in `docs/TECH_DEBT.md` keeps the door open for satellites: events delivered after the commit (later an outbox), ports instead of framework and model dependencies outside Infrastructure, a party and item snapshot in issued invoices, no SQL joins across contexts in Analytics.
- Jobs are written against Laravel Queue and domain events go through the `EventPublisher` port, so changing the queue backend or publishing to a broker touches only Infrastructure.
- Revisit this decision when a satellite candidate meets the three conditions above, or when an outside party needs to subscribe to Billex events.
