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

For model factories, retain the factory returned by each state operation, including `state()`, `count()`, `for()` and the bank-account factory's `ownedBy()`. Preserve the model or collection returned by `create()` or `make()`. A repeated `factory()` root in one function scope merges under the one-receiver rule below. A controlled transformation of direct method statements reserves names against existing local variables, verifies the expected statement-expansion AST and preserves comments and literals. Methods exposing their local symbol table and unsupported expression contexts require separate review.

For HTTP tests, distinguish the client returned by setup methods such as `actingAs()` or `withSession()` from the response returned by the request. Retain each setup and assertion return, and preserve terminal content/JSON reads and the final assignment or return. The supported direct `$this` chains and direct assertion statements on proven response variables follow this conversion. Establish an existing receiver's origin through straight-line request assignments and client/response aliases; invalidate it on reassignment, argument escapes and uncertain control flow. Reference aliases, bindings passed directly as arguments, observable local symbol tables and conditional or nested expressions require separate review. Use a fresh assertion-result variable so the original response binding and its aliases keep their values. Executable transformation checks cover distinct returned instances, alias reassignment, argument order, exceptions, reference side effects and name collisions before a representative pilot and a larger explicit file batch.

For `Storage::disk()`, model query starters and `DB::table()`, the receiver is a fresh return value, so no local origin needs proving. Repeated lookups merge under the one-receiver rule below, never across `Storage::fake()`, which replaces the disk. A query setup call reuses the builder only when the installed framework returns that same builder for it; a named scope or macro does not qualify. Every other call retains its return. A new statement goes immediately before the original one in the same statement list, which preserves branch, loop and closure execution. A chain may leave a larger expression only when it always runs there and everything evaluated before it in that statement has no side effect: literals, constants, plain variables and property or array reads of those. The last call stays in its original position. `app()` and `UploadedFile::fake()` follow the same rule. `$validator->errors()` keeps the variable as its receiver and assigns the returned bag.

Test helpers with a fresh return, such as `now()`, `collect()` or `Route::post()`, follow the same rule with three additions. A local variable keeps a temporary alive until the method ends, so a value with a destructor never goes into a variable: `PendingDispatch` dispatches on destruct, and assigning it would move the dispatch. The job is built first and handed over at the end (`$job = new QueuedCommand($data); $job->onConnection('database'); dispatch($job);`), where the helper's `PendingDispatch` still ends with its statement. A call moved past a property or offset read may have only side-effect-free arguments, because a closure or nested call could change the value that read still has to see. A `now()` chain made only of Carbon calls that touch its own new instance may also move past value object constructors, dated `DateTimeImmutable` objects, other such chains, `uniqid()` and a read-only query on a model without a `retrieved` listener, since none of them can change the clock. Clock reads keep their order, so a bare `now()` or an undated `DateTimeImmutable` before the chain keeps it in place, unless a bare `now()` merges with the chain into one `$now` under the table below.

For chains rooted at the `Livewire` facade in tests, manager calls such as `actingAs()` and `withQueryParams()` and the `Testable` calls return their receiver, verified against the installed Livewire, so no return value is retained. Each manager call becomes its own facade statement, `test()` assigns the `Testable`, and every later call uses that variable. An assignment keeps its target name, and a second `Livewire::test()` is a new component that follows the second-initialisation rule below. A call whose return is not verified, a return value or argument, a link argument reading the target and a target captured by reference stay as they are.

A chain on a variable holding a `Testable` splits the same way for calls that return it. `instance()` and `viewData()` return something else, so that value, the rendered view and a mapped or plucked result are retained under names from context. Such a read may leave an assertion's argument only when the earlier arguments have no side effect.

A console test holds its command through `$this->pendingCommand()`, sets its expectations and ends with `$command->run()`. `PendingCommand` runs in `__destruct` when `run()` was not called, so a variable without `run()` would move the command to the end of the method, after the assertions that follow it. `$this->artisan()` stays for a call with no expectations; its `PendingCommand|int` return cannot be used as a typed receiver.

Mockery and PHPUnit expectation setters return the same expectation, so `$expectation = $mock->shouldReceive('x'); $expectation->once(); $expectation->andReturn(1);` is the whole form, and the next expectation goes into the same variable because the mock keeps the previous one. Verification is different: `shouldHaveReceived()` returns a `VerificationDirector`, and its `with()` and `once()` verify at once and return a clone. Its result is reassigned (`$verification = $verification->with('a'); $verification->once();`), because dropping it also drops the argument filter. Setup on the test itself (`actingAs()`, `withHeader()`) returns `$this`, so it stands as a statement and only the request's response is retained.

A receiver with the same origin is initialised once per function scope and reused; a closure is its own scope, an arrow function shares its parent's. A merge happens only under the condition verified for that receiver against the installed framework:

| Receiver | Merged when | Reason |
|---|---|---|
| `Model::factory()` | same root, no `sequence()` state | `create($attributes)` and state methods return a new factory and leave the receiver unchanged; a retained `sequence()` advances |
| Factory with the same state | the state's arguments cannot have changed, the state draws nothing when requested, and `createMany()` does not share a counted factory | `$publishedFactory = $pageFactory->published();` serves several `create()` calls; `createMany()` clears the count of its receiver |
| `Storage::disk('x')` | no `Storage::fake()` in between, also through a helper of the class | the fake replaces the disk |
| `collect($x)` | `$x` unchanged, only reading calls | a mutator would reach the later reads |
| `session()`, `config()`, `cache()`, `auth()`, `auth('web')` | no container change or `Auth::forgetGuards()` in between | the same manager, repository or named guard is returned |
| `app()` without arguments | no new application in between | it is the container itself; a binding changes what it resolves, not which container it is |
| `app(Foo::class)` | a verified singleton, no binding change in between | another abstract may build a new instance |
| `UploadedFile::fake()` | always | `FileFactory` has no state; the files stay separate, and a fake file keeps its own variable because its temporary file is released with its last reference |
| `$validator->errors()` | `$validator` not reassigned or rerun | `passes()` replaces the bag |
| `$component->viewData('x')`, `$component->instance()` | no `set()` or `call()` in between | each of them renders again and replaces the view data and the component; an assertion does not |
| `now()` | no `travel`, `setTestNow` or `freezeTime` in between | see below |
| Relation (`$plan->features()`) | same parent, only `attach`, `detach`, `count` and `pluck` | the where clause is fixed when the relation is made and these calls leave its builder unchanged; `first()` adds a limit, so it is never shared |
| `fake()` | always | the container's singleton; `Generator` reseeds in its destructor, which a local variable cannot trigger while the container holds it |
| `$faker->unique()` | no `unique(true)` in between | the provider keeps one `UniqueGenerator` and creating it draws nothing |
| `$faker->optional($weight)` | same weight and default | `ChanceGenerator` draws in every call, so fields stay independent and no draw moves |
| `$model->fresh()` | judged per method: only assertions on the read between the uses | each call reads the row again; after a request or a write the new read goes into the same variable |
| Value of a `final readonly` object (`$line->total()`, `$dueDate->date()`) | the object not reassigned | the method computes the same value from immutable state |

A factory and its state calls only build a value: they run no query and dispatch nothing. So `$userFactory = User::factory();` moves out of a branch, match arm, `??`, arrow function or loop to the statement of its first use, and the terminal call stays where it runs: `fn () => $userFactory->create()` still creates the row only when called. A further state goes into the same variable when the previous one is no longer read (`$draftFactory = $draftFactory->root();`), and the same state on two models adds the model (`$archivedBusinessFactory`). Four limits apply. A read of the terminal's result (`->id`) moves the terminal, so it follows the argument rule above. A class held in a variable moves only while it has one value, and not out of a branch where another class may have no factory. A data provider runs before the application boots and the factory's constructor needs the container, so a factory there stays inside the closure the provider returns. `DatabaseMaintenanceLogFactory::completed()` and `failed()` draw a date when the state is requested, so these states are never shared.

`$now…` variables hold `CarbonImmutable::now()`, which reads the same clock and timezone as `now()` and follows `setTestNow`. Sharing a mutable `now()` would be a bug: a mutable Carbon call returns its receiver, so in `$a = $now->startOfDay(); $b = $a->subDays(10);` both variables are one object and a later read of `$a` sees `$b`'s value. `now()` stays in place where a variable is read after a later step changed that object. This is the one conversion with a small behaviour change: one clock read and a different type. A held value does not follow the clock, which is why travel is a barrier.

Different queries never share a builder, since setup calls mutate it. Calls that act or read current state stay separate: `Livewire::test()`, `Validator::make()`, `Artisan::call()`, `DB::getQueryLog()`, `Request::create()`, `Role::findByName()` and each HTTP request. `fresh()` is the one reread that merges, under the row above. A root with one use stays on two lines (`$userFactory = User::factory(); $user = $userFactory->create();`). A chain step keeps its own name or reuses the receiver's (`$money = $money->multiplyBy('2');`), and calls verified to return `$this`, such as those of `ResponseSequence`, use the same variable.

A needed second initialisation goes into the same variable when the previous value is no longer read. If both values stay live, the second name comes from context (`$productQuery`), never from a number. Numbered names written by hand before the rollout are outside this conversion. `DisallowCallChainingSniff` keeps reporting chains; repeated roots have no sniff, so the rule is applied by the conversion and by review.

An arrow function with several calls becomes a closure with statements (`function () use ($user): void { $user->forceFill($attributes); $user->save(); }`), so the calls stay inside what runs later. A `match` arm that needs statements becomes an `if` block in front of the `match`. A `PDOStatement` may stay in a variable: after `fetchAll()` it holds no result, and a `FOR UPDATE` lock belongs to the transaction, not to the statement.

Apart from the factory values above, do not lift calls out of branches, short-circuit expressions, nullsafe paths, closures or loop iterations. Preserve argument evaluation order and reference semantics. Converting a ternary must evaluate only its selected branch and evaluate its condition once. The shorthand ternary must retain its evaluated truthy value.

Money, immutable dates, invoice numbering and locks, payment callbacks, permissions and entity limits, and rendered PDFs require explicit behaviour review. Existing PDF snapshots remain the expected output.

## Verification and delivery

1. Pin accepted and rejected syntax, UTF-8 boundaries, dynamic calls, nested ternaries and the absence of automatic chain/ternary rewrites with behaviour tests of the linters.
2. Check fenced PHP examples in `docs/`, `.claude/rules/` and `.claude/references/` with the same PHP_CodeSniffer configuration. Deliberately invalid examples belong in test inputs or clearly identified inline descriptions.
3. Keep tooling, mechanical formatting and expression conversion in separate reviewable changes. Compare significant PHP tokens around formatting; investigate every difference beyond layout and permitted trailing commas.
   Mechanical formatting may use large, explicit file lists grouped into application, test and remaining PHP scopes. Preserve the starting sources, automate token comparisons for every selected file and verify that another fixer pass leaves the result unchanged. Run affected tests at those larger boundaries rather than repeating the same checks for small file-count batches. Keep controlled expression conversion in smaller groups where its evaluation order and API return values can be reviewed.
4. After each conversion batch, run the affected behaviour tests and check remaining diagnostics. Verify authored Blade expressions separately using a Blade-aware approach.
5. Before completing the rollout, pass both linters, Larastan, the complete application suite, architecture guards, documentation checks and dependency audits through [pre-pr-check](../../.claude/skills/pre-pr-check/SKILL.md).

Do not add a suppression baseline or raise limits to hide remaining violations. Existing architectural reports can drift because source line counts change: explain those metric deltas and verify unchanged semantic metrics before regenerating existing metadata through its documented command.
