---
paths:
  - "tests/**"
  - "phpunit.xml"
  - "database/factories/**"
  - "database/seeders/**"
---

# Testing

## What a test is for

- A test pins observable behaviour: a returned value, a stored row, a response, a published event, a rendered PDF. A change that adds or alters behaviour comes with a test of that behaviour; a bug fix adds the regression case that failed before the fix.
- Structural tests are not written and existing ones are deleted when touched: `class_exists`, `method_exists`, reflection over traits, parents or constructor parameters, assertions on `$fillable` / `$casts` / table names. The type system, Larastan and the architecture tests cover structure.
- Every route gets an HTTP test of its authorization: guest, user without permission, and another user's record (expects 404 — `application.md`, ownership).
- No `markTestSkipped()` / `markTestIncomplete()` in committed tests; a test that cannot run yet is not committed.

## Test types

- `tests/Unit/` — no framework: extends `PHPUnit\Framework\TestCase`, no container, DB, facades or `new Model()`. Domain value objects and services, mappers' pure conversions, presenters.
- `tests/Feature/` — boots Laravel (`Tests\TestCase`): application use cases, repositories, HTTP, Livewire, commands, providers.
- The directory below `Unit/` or `Feature/` mirrors the path of the class under test (`tests/Unit/Domain/Invoice/ValueObjects/InvoiceNumberTest.php`).
- Ports are replaced by fakes from `tests/Support/Fakes/` (gateways, clock, exchange rates); application and domain services always run for real — they are `final` and not mocked (`architecture.md`, Ports and interfaces).

## Test database

- Tests run on MySQL in the `invoice_testing` database, separate from the local development database (CI: a MySQL service). Code has no branches for other database drivers.
- Schema comes from the migrations and reference data from `DatabaseSeeder`, migrated and seeded once; each test runs in a transaction (`RefreshDatabase`). Tests never create tables, columns or permission schema themselves.
- Permissions in tests come from `PermissionCatalog` (`permissions.md`), not from hand-written lists.
- Seeded data is present: unique fields get unique values (`uniqid()`) and no test depends on fixed ids or on row order it did not set.

## Conventions

- `#[Test]` attribute, method names in snake_case describing the behaviour (`marks_invoice_as_paid_when_payment_confirmed`), `: void` return type.
- Admin HTTP tests: `actingAs($user, 'backpack')`; an unauthenticated admin request returns 403.
- `tests/TestCase.php` disables the `SetLocale` middleware. Tests call the application's real routes; a test never registers a route that shadows one.
- PDF output is pinned by `InvoicePdfSnapshotTest`; regenerate snapshots only for an intended change with `UPDATE_PDF_SNAPSHOTS=1` and review the diff.
- Run targeted tests while working. Before a commit the `pre-pr-check` skill decides between targeted tests and the full suite (only for changes that can reach anything) and runs the architecture guards; CI runs the full suite on every push to a PR.
