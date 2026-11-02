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
- CI enforces it: PHP_CodeSniffer (Slevomat `ReturnTypeHint`, `ParameterTypeHint` in `phpcs.xml`) for return and parameter types, `tomasvotruba/type-coverage` in Larastan (`phpstan.neon`) for properties. A property declared by a parent class (Eloquent `$fillable`, Command `$signature`) keeps the parent's declaration and is not counted. The only exception is a signature PHP refuses to narrow — a subclass of an untyped vendor method — excluded file by file in `phpcs.xml` with the reason.
- Public methods of Domain and Application return objects (DTO, value object, `Result`, enum), not associative arrays. An array is fine as a list of typed items, documented as `list<FooDTO>`.
- Docblocks only add what the signature cannot say: array shapes (`list<...>`, `array<string, ...>`), `@throws`, a non-obvious invariant. No `@param`/`@return` that repeat the signature, no class docblocks that repeat the class name.
- Domain values that the code compares — statuses, types, units, templates — are backed enums, never string or int literals (`InvoiceStatus::PAID`, not `'paid'`).

## Naming

- English everywhere: identifiers, comments, log messages, exception messages. User-facing text is a translation key (`translations.md`).
- Enum cases and class constants are UPPER_SNAKE_CASE (`Unit::CUBIC_METERS`, `CACHE_TTL`); enum values stay as stored (`'cubic_meters'`).
- Properties and variables are camelCase, including DTO and value object properties (`$contactId`, not `$contact_id`). snake_case belongs to DB columns, request keys and translation keys; converting between them is the mappers' and form requests' job.
- Czech business terms get English identifiers: IČO is `companyNumber` / `company_number`, DIČ is `vatNumber` / `vat_number`, in every layer (conversion in `docs/TECH_DEBT.md`). The `Id` / `_id` suffix is reserved for entity identifiers and foreign keys.
- Interfaces end with `Interface` and live in `Contracts/`. Data class suffixes are defined in `architecture.md` (Data classes).
- Import every class with `use`, sorted alphabetically; no inline fully qualified names (`\App\...`, `\Log::`).

## Errors

- Failures are exceptions, not `false`/`null`/`['success' => false]`. Each context defines its own exceptions in `Exceptions/` (`InvoiceNotFoundException`). `null` means "not found" only where absence is a normal outcome, and the method name says so (`findBy…(): ?FooDTO`).
- Catch only what you can handle, by its specific type. `\Throwable` / `\Exception` are caught only at a delivery boundary (controller, Livewire, command, queued job) to turn them into a response, and the original exception goes to the log with its context — never just `$e->getMessage()`.
- Log messages are fixed English strings with structured context. Never log a translated string:

```php
$this->log->error(
    'Invoice PDF rendering failed',
    ['invoiceId' => $id, 'exception' => $e],
);
```

## Readability

Code is read far more often than written, and in an invoicing app a misread line costs money. Readability wins over brevity.

- No chained method calls, including a returned-object path through a property or array offset: use separate statements and meaningful intermediate variables. Plain property paths and one method on a newly constructed object are allowed. Mutating query-builder calls may use the same builder; immutable operations and terminal calls must retain their returned value. `Billex.Functions.DisallowCallChaining` reports this without an unsafe automatic rewrite.
- A receiver with the same origin is initialised once per method and then reused: one `$invoiceFactory` for several `create()` calls, one `$disk`, one `$session`. Calls verified to return `$this` may use the same variable (`$sequence->pushStatus(500); $sequence->pushStatus(200);`). A current time kept in a variable is `CarbonImmutable::now()`, because sharing a mutable `now()` lets one step change the next one's value. A needed second initialisation (another query, a disk after `Storage::fake()`) goes into the same variable when the previous value is no longer read; otherwise its name comes from context (`$productQuery`), never from a number. Conditions per receiver: `docs/design/DESIGN_PHP_STYLE.md`.
- Ternaries are for short choices: one expression on one line, at most 120 characters, without a nested ternary. Use `if`/`else` otherwise (`Billex.ControlStructures.ShortTernary`).
- No code without a purpose: unused parameters, variables, methods, properties, constants and unreachable branches are deleted. Code that only tests call is unused too: delete it with its tests. `shipmonk/dead-code-detector` reports unused members in the Larastan run. Members that Backpack, Livewire, Blade components and templates or laravel/ui reach by name are marked as used by the providers in `phpstan/DeadCode/`. A new framework convention of that kind gets a provider there, never `@api` or an ignore.

## Comments

- Comments explain why, not what. No history notes ("removed X", "moved from Y", "Phase 2", "legacy compatibility") — git keeps the history.
- No commented-out code and no `TODO` without a matching item in `docs/TECH_DEBT.md`.

## Formatting

- Formatting is the linters' job, not a reviewer's: Laravel Pint (`pint.json`) and PHP_CodeSniffer (`phpcs.xml`). Before every commit run `docker exec BILLEX-app vendor/bin/phpcbf` and then `docker exec BILLEX-app vendor/bin/pint --dirty`; CI checks both (`.github/workflows/static-analysis.yml`).
- A method signature, a call or an array with three or more parameters or items puts each one on its own line, with a trailing comma; one or two simple arguments may share a line. A call is also split when an argument contains a nested call with arguments, or an argument exceeds 60 characters; short argumentless accessors may stay inline (`Billex.Functions.ComplexCallLayout`). A list is easier to scan, and adding an item changes one line of the diff. PHP_CodeSniffer enforces and fixes it: `Billex.Functions.ComplexCallLayout` for calls, `Billex.Functions.MultiLineSignature` for methods, functions and closures, `Billex.Arrays.MultiLineArray` for arrays (destructuring stays on one line).
- A compound condition exceeding 120 characters on its source line is split with `if (` / `elseif (` and the closing parenthesis on separate lines, one condition part per line, and `&&` / `||` at the start of continuation lines. Already multiline conditions follow the same layout (Billex `MultiLineCondition` checks boolean-part and grouping positions; PSR12 `BooleanOperatorPlacement`).
- Length limits count UTF-8 characters: the condition's source line includes indentation, a ternary counts its expression without an enclosing assignment or return, and an argument includes quotes and named-argument labels. Argument layout whitespace counts as one space; literal contents keep their actual length. Keep ordinary spaces after commas. Measurement details and conversion safeguards: `docs/design/DESIGN_PHP_STYLE.md`.
- Write logical negation without a space (`!$allowed`, `!$service->isReady()`). Pint's `not_operator_with_successor_space: false` and PHP_CodeSniffer's `Generic.Formatting.SpaceAfterNot` agree on this.
- Executable syntax checks own the mechanical rules above; extend their regression examples when changing a rule. Keep explanations here and enforcement in the existing Pint / PHP_CodeSniffer configuration rather than in agent-specific checklists.
- Larastan is the static analyser (`phpstan.neon`, `docker exec BILLEX-app vendor/bin/phpstan analyse --memory-limit=4G`), with no baseline file and no `@phpstan-ignore` comments. Its level is raised as contexts are converted, up to `max` (`docs/TECH_DEBT.md`); code you write passes the configured level and is written to pass `max`.
