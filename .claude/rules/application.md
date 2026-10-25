---
paths:
  - "app/Application/**"
---

# Application

Use cases called by delivery (controllers, Livewire, commands). Allowed dependencies: `architecture.md`.

- An application service is a `final` class named `<Context>…ApplicationService`, one public method per use case (`createInvoice`, `markAsPaid`), taking typed input — ids as value objects, the current user as `UserId`, data as `<Entity>WriteData` — and returning a DTO, a `<Operation>Result` or nothing.
- **Ownership is checked here.** Every use case that reads or changes a user's record loads it scoped to that user (`findByIdForUser(InvoiceId, UserId)`) and throws the context's not-found exception when it is missing — the same answer for "does not exist" and "belongs to someone else". Permission and entity-limit checks also run here (`permissions.md`).
- A use case that writes more than one record runs in one transaction through `TransactionBoundaryInterface`. Domain events published inside it are delivered after the commit and not at all on a rollback (`EventPublisherInterface` defers them), so a handler never acts on data that was rolled back.
- Input validation of request data belongs to form requests (`http.md`); business validation belongs to Domain value objects and services. Application holds no third copy of either.
- Presentation formatting (money, dates, labels for one screen) lives in `Presenters/`, using `MoneyFormatterInterface` and the translation port.
