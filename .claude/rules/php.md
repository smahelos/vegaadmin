---
paths:
  - "app/**/*.php"
  - "tests/**/*.php"
  - "database/**/*.php"
  - "routes/**/*.php"
  - "config/**/*.php"
---

# PHP

## File and class shape

- Every PHP file starts with `declare(strict_types=1);`. Without it PHP silently coerces `"12.50"` to `12` — the exact bug class an invoicing app cannot afford.
- Classes are `final`. Exceptions: abstract base classes, and Eloquent models (Backpack and PermissionManager build on them). A class that needs a test double is a port and gets an interface (`architecture.md`), never a non-final class for mocking.
- Value objects and data classes are `final readonly class`. What they hold is a public readonly property (`$invoiceId->value`, `$money->amount`, `$account->iban`); there are no getters. What they compute is a method named after the result, without `get` (`$account->formattedIban()`, `$number->year()`); predicates start with `is` (`isZero()`).
- Dependencies come in through constructor property promotion: `private readonly FooRepositoryInterface $foos`. No assignments in the constructor body, no untyped properties.
- One class per file; enums live in an `Enums/` directory of their context.

## Types

- Every parameter, property and return value is typed, `void` included. `mixed` only where the value is genuinely open (a cache value), never to avoid naming a type.
- Public methods of Domain and Application return objects (DTO, value object, `Result`, enum), not associative arrays. An array is fine as a list of typed items, documented as `list<FooDTO>`.
- Docblocks only add what the signature cannot say: array shapes (`list<...>`, `array<string, ...>`), `@throws`, a non-obvious invariant. No `@param`/`@return` that repeat the signature, no class docblocks that repeat the class name.
- Domain values that the code compares — statuses, types, units, templates — are backed enums, never string or int literals (`InvoiceStatus::Paid`, not `'paid'`).

## Naming

- English everywhere: identifiers, comments, log messages, exception messages. User-facing text is a translation key (`translations.md`).
- Properties and variables are camelCase, including DTO and value object properties (`$clientId`, not `$client_id`). snake_case belongs to DB columns, request keys and translation keys; converting between them is the mappers' and form requests' job.
- Czech business terms get English identifiers: IČO is `companyNumber` / `company_number`, DIČ is `vatNumber` / `vat_number`, in every layer (conversion in `docs/TECH_DEBT.md`). The `Id` / `_id` suffix is reserved for entity identifiers and foreign keys.
- Interfaces end with `Interface` and live in `Contracts/`. Data class suffixes are defined in `architecture.md` (Data classes).
- Import every class with `use`, sorted alphabetically; no inline fully qualified names (`\App\...`, `\Log::`).

## Errors

- Failures are exceptions, not `false`/`null`/`['success' => false]`. Each context defines its own exceptions in `Exceptions/` (`InvoiceNotFoundException`). `null` means "not found" only where absence is a normal outcome, and the method name says so (`findBy…(): ?FooDTO`).
- Catch only what you can handle, by its specific type. `\Throwable` / `\Exception` are caught only at a delivery boundary (controller, Livewire, command, queued job) to turn them into a response, and the original exception goes to the log with its context — never just `$e->getMessage()`.
- Log messages are fixed English strings with structured context: `$this->log->error('Invoice PDF rendering failed', ['invoiceId' => $id, 'exception' => $e])`. Never log a translated string.

## Comments

- Comments explain why, not what. No history notes ("removed X", "moved from Y", "Phase 2", "legacy compatibility") — git keeps the history.
- No commented-out code and no `TODO` without a matching item in `docs/TECH_DEBT.md`.

## Formatting

- Laravel Pint is the formatter: `docker exec BILLEX-app vendor/bin/pint --dirty` before every commit. Until `pint.json` exists and the codebase has been formatted once (`docs/TECH_DEBT.md`, Tooling), Pint runs only on explicit file paths (without them it aborts on `data/redis`), and you format only the lines you touch.
- Larastan is the static analyser, raised level by level with no baseline file. Code you write passes the configured level; until Larastan is installed (`docs/TECH_DEBT.md`), write code that would pass its maximum level.
