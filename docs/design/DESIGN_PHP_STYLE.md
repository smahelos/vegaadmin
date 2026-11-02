# PHP syntax enforcement

Status: Implementation design, 2026-10-31; rollout in progress. Implements the agreed syntax and readability scope in [the PHP standard](../../.claude/rules/php.md). Documentation follows [the shared policy](../DOCUMENTATION.md).

## Purpose and scope

Make the project's PHP layout predictable through PHP_CodeSniffer and Pint, with the same standard in maintained documentation examples. Preserve application behaviour throughout the conversion.

The rollout covers standalone project PHP, maintained PHP examples and authored PHP expressions in Blade. PHP_CodeSniffer excludes raw Blade templates: their HTML, JavaScript and Blade syntax must be separated from authored PHP before checking. Compiler-generated PHP is not source to reformat. Coverage of Blade expressions must be established before declaring the rollout complete.

This work changes layout and expression readability. Architecture migrations, renames, stricter coercion, class inheritance changes, permissions, business rules and dependency upgrades keep their existing backlog scopes. Existing architectural violations remain tracked in [technical debt](../TECH_DEBT.md).

## Enforcement

PHP_CodeSniffer handles layouts that Pint does not express; Pint supplies indentation and the Laravel formatting preset. Run PHP_CodeSniffer's fixer before Pint. Both checks must accept the result, and another fixer pass must leave it unchanged.

| Construct | Rule | Automatic rewrite |
|---|---|---|
| Three or more call arguments | Billex `ComplexCallLayout` | Layout only |
| Three or more signature parameters or array items | Billex `MultiLineSignature`, `MultiLineArray` | Layout only |
| Complex or long argument, including one- and two-argument calls | Billex `ComplexCallLayout` | Layout only |
| Long compound or existing multiline condition | Billex `MultiLineCondition`, PSR12 spacing and operator placement | Layout only |
| Negation without successor whitespace | Generic `SpaceAfterNot`, Pint configuration | Layout only |
| Chained method calls | Billex `DisallowCallChaining` | None |
| Nested, multiline or long ternary | Billex `ShortTernary` | None |

Simple values and short parameterless accessors may remain inline. A nested invocation with arguments makes its containing call multiline, including dynamic functions, callable expressions and dynamic methods. Argument boundaries are determined by structural parentheses and brackets. An arrow function scope may end at the next argument separator, so its scope metadata must not consume that comma. This also applies to nested arrows, typed returns and named arguments. The same traversal enforces the three-argument threshold and complex-argument layout for named and dynamic calls, including constructors. Commas inside match arms, closure bodies and anonymous-class bodies do not count as call-argument separators. Pint preserves multiline anonymous-class constructor arguments through `class_definition.inline_constructor_arguments: false`, so its class-header formatting does not undo the argument layout. Ordinary whitespace after commas remains enabled.

The limits measure UTF-8 code points, not bytes or display-cell widths:

- A compound condition is split above 120 characters on its single source line, including indentation and surrounding source on that line. Billex measures that source line directly in UTF-8 characters. Existing multiline conditions put each boolean part on its own line regardless of length. Detection checks the actual positions of operators and grouping parentheses; the total number of lines cannot establish that parts are separated. The fixer changes layout around those boundaries while preserving grouping and short-circuit order. Boolean expressions inside call arguments, arrays, closures and other operand bodies belong to those operands and are excluded from this traversal.
- A ternary expression may contain at most 120 characters from the start of its condition to the end of its alternative, excluding a surrounding assignment or return. Nested and multiline ternaries require conversion regardless of length.
- An argument is long above 60 characters, including literal quotes and named-argument labels. Layout whitespace tokens count as one space; whitespace inside string literals keeps its actual length.

Conditions place `&&` and `||` at the start of continuation lines, with opening and closing parentheses on their own lines. Readability still calls for splitting complicated expressions below a mechanical threshold; length is an upper bound, not a definition of simplicity.

## Behaviour-preserving conversion

A method chain is a sequence of calls on the preceding call's result, including a path through a property or array offset. Plain property paths are allowed. Calling one method on a newly constructed object is allowed. Split chains into statements with meaningful local names.

Inspect the invoked API before reusing a receiver. A mutating builder can retain the same variable; an immutable operation or terminal call must retain its return value. For example:

```php
$query = Invoice::query();
$query->whereKey($invoiceId);
$invoice = $query->first();
```

```php
$due = $clock->now();
$due = $due->modify('+14 days');
$formatted = $due->format('Y-m-d');
```

For model factories, retain the factory returned by each state operation, including `state()`, `count()`, `for()` and the bank-account factory's `ownedBy()`. Preserve the model or collection returned by `create()` or `make()`. Every original `factory()` call still executes independently. A controlled transformation of direct method statements reserves names against existing local variables, verifies the expected statement-expansion AST and preserves comments and literals. Methods exposing their local symbol table and unsupported expression contexts require separate review.

For HTTP tests, distinguish the client returned by setup methods such as `actingAs()` or `withSession()` from the response returned by the request. Retain each setup and assertion return, and preserve terminal content/JSON reads and the final assignment or return. The supported direct `$this` chains and direct assertion statements on proven response variables follow this conversion. Establish an existing receiver's origin through straight-line request assignments and client/response aliases; invalidate it on reassignment, argument escapes and uncertain control flow. Reference aliases, bindings passed directly as arguments, observable local symbol tables and conditional or nested expressions require separate review. Use a fresh assertion-result variable so the original response binding and its aliases keep their values. Executable transformation checks cover distinct returned instances, alias reassignment, argument order, exceptions, reference side effects and name collisions before a representative pilot and a larger explicit file batch.

For `Storage::disk()`, model query starters and `DB::table()`, the receiver is a fresh return value, so no local origin needs proving. Keep each original lookup separate, because `Storage::fake()` can replace a disk between two lookups. A query setup call reuses the builder only when the installed framework returns that same builder for it; a named scope or macro does not qualify. Every other call retains its return. A new statement goes immediately before the original one in the same statement list, which preserves branch, loop and closure execution. A chain may leave a larger expression only when it always runs there and everything evaluated before it in that statement has no side effect: literals, constants, plain variables and property or array reads of those. The last call stays in its original position. `app()` and `UploadedFile::fake()` follow the same rule; each `app()` call stays separate, because a binding may change or build a new instance between lookups. `$validator->errors()` keeps the variable as its receiver and assigns the returned bag.

Do not lift calls out of branches, short-circuit expressions, nullsafe paths, closures or loop iterations. Preserve argument evaluation order and reference semantics. Converting a ternary must evaluate only its selected branch and evaluate its condition once. The shorthand ternary must retain its evaluated truthy value.

Money, immutable dates, invoice numbering and locks, payment callbacks, permissions and entity limits, and rendered PDFs require explicit behaviour review. Existing PDF snapshots remain the expected output.

## Verification and delivery

1. Pin accepted and rejected syntax, UTF-8 boundaries, dynamic calls, nested ternaries and the absence of automatic chain/ternary rewrites with behaviour tests of the linters.
2. Check fenced PHP examples in `docs/`, `.claude/rules/` and `.claude/references/` with the same PHP_CodeSniffer configuration. Deliberately invalid examples belong in test inputs or clearly identified inline descriptions.
3. Keep tooling, mechanical formatting and expression conversion in separate reviewable changes. Compare significant PHP tokens around formatting; investigate every difference beyond layout and permitted trailing commas.
   Mechanical formatting may use large, explicit file lists grouped into application, test and remaining PHP scopes. Preserve the starting sources, automate token comparisons for every selected file and verify that another fixer pass leaves the result unchanged. Run affected tests at those larger boundaries rather than repeating the same checks for small file-count batches. Keep controlled expression conversion in smaller groups where its evaluation order and API return values can be reviewed.
4. After each conversion batch, run the affected behaviour tests and check remaining diagnostics. Verify authored Blade expressions separately using a Blade-aware approach.
5. Before completing the rollout, pass both linters, Larastan, the complete application suite, architecture guards, documentation checks and dependency audits through [pre-pr-check](../../.claude/skills/pre-pr-check/SKILL.md).

Do not add a suppression baseline or raise limits to hide remaining violations. Existing architectural reports can drift because source line counts change: explain those metric deltas and verify unchanged semantic metrics before regenerating existing metadata through its documented command.
