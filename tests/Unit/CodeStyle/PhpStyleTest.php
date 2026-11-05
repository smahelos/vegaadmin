<?php

declare(strict_types=1);

namespace Tests\Unit\CodeStyle;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;

final class PhpStyleTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function rejectedCode(): iterable
    {
        yield 'query chain' => [
            '$invoice = Invoice::query()->where("id", $id)->first();',
            'Billex.Functions.DisallowCallChaining.ChainedCall',
        ];
        yield 'collection chain' => [
            '$values = $collection->values()->all();',
            'Billex.Functions.DisallowCallChaining.ChainedCall',
        ];
        yield 'nullsafe chain' => [
            '$value = $factory?->create()?->name();',
            'Billex.Functions.DisallowCallChaining.ChainedCall',
        ];
        yield 'parenthesized chain' => [
            '$value = ($factory->create())->name();',
            'Billex.Functions.DisallowCallChaining.ChainedCall',
        ];
        yield 'dynamic method chain' => [
            '$value = $factory->create()->{$method}();',
            'Billex.Functions.DisallowCallChaining.ChainedCall',
        ];
        yield 'chain through a property' => [
            '$value = $factory->create()->relation->name();',
            'Billex.Functions.DisallowCallChaining.ChainedCall',
        ];
        yield 'chain through an array offset' => [
            '$value = $factory->create()[0]->name();',
            'Billex.Functions.DisallowCallChaining.ChainedCall',
        ];
        yield 'space after negation' => [
            'if (! $allowed) {}',
            'Generic.Formatting.SpaceAfterNot.Incorrect',
        ];
        yield 'trailing boolean operator' => [
            "if (\n    \$country === null ||\n    \$format === null\n) {}",
            'PSR12.ControlStructures.BooleanOperatorPlacement.FoundMixed',
        ];
        yield 'condition shares the opening parenthesis line' => [
            "if (\$country === null\n    || \$format === null\n) {}",
            'PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine',
        ];
        yield 'condition shares the closing parenthesis line' => [
            "if (\n    \$country === null\n    || \$format === null) {}",
            'PSR12.ControlStructures.ControlStructureSpacing.CloseParenthesisLine',
        ];
        yield 'long compound condition' => [
            'if ($country === null || $format === null || $checkDigits < 2 || $checkDigits > 98 || preg_match($format, $iban) !== 1) {}',
            'Billex.ControlStructures.MultiLineCondition.RequiredMultiLineCondition',
        ];
        yield 'constructor argument' => [
            '$contact = $contacts->create($userId, new ContactWriteData($invoice->parties->customer->toProfile(), null));',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'function argument' => [
            '$value = save($id, normalize($input));',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'dynamic callable argument' => [
            '$value = save($callback($input));',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'parenthesized callable argument' => [
            '$value = save(($callback)($input));',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'dynamic method argument' => [
            '$value = save($service->{$method}($input));',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'dynamic outer call' => [
            '$value = $callback(normalize($input));',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'long argument' => [
            '$value = save("A deliberately long argument which exceeds sixty characters in a single literal");',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'whitespace inside a long string literal' => [
            '$value = save("' . str_repeat(' ', 59) . '");',
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
        ];
        yield 'multiline ternary' => [
            "\$value = \$allowed\n    ? 'yes'\n    : 'no';",
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
        ];
        yield 'nested ternary' => [
            '$value = $first ? ($second ? "a" : "b") : "c";',
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
        ];
        yield 'ternary in another ternary condition' => [
            '$value = ($fallback ?: "none") ? "yes" : "no";',
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
        ];
        yield 'long ternary' => [
            '$value = $allowed ? "A long positive value which makes this expression exceed the limit" : "An equally long negative value which is difficult to scan";',
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
        ];
        yield 'multiline ternary after a concat assignment' => [
            "\$code .= \$allowed\n    ? 'yes'\n    : 'no';",
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
        ];
        yield 'nested ternary in the first match arm' => [
            "\$value = match (\$action) {\n    'view' => \$first ? (\$second ? 'a' : 'b') : 'c',\n};",
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
        ];
        yield 'long ternary with an assignment inside its condition' => [
            '$value = check($name = "A long argument value which makes this condition reach very far beyond the limit of the ternary rule") ? "yes" : "no";',
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
        ];
    }

    #[Test]
    #[DataProvider('rejectedCode')]
    public function rejects_disallowed_layout(string $source, string $expectedSource): void
    {
        $this->assertContains(
            $expectedSource,
            $this->violations($source),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function acceptedCode(): iterable
    {
        yield 'separate query calls' => [
            '$query = Invoice::query(); $query->where("id", $id); $query->lockForUpdate(); $invoice = $query->first();',
        ];
        yield 'retained immutable result' => [
            '$due = $clock->now(); $due = $due->modify("+14 days"); $date = $due->format("Y-m-d");',
        ];
        yield 'property path is not call chaining' => ['$id = $invoice->parties->contactId->value;'];
        yield 'one method on a new object' => ['$value = (new Value)->name();'];
        yield 'one method after construction with arguments' => ['$value = (new Value($id))->name();'];
        yield 'method after a property path' => ['$value = $invoice->parties->name();'];
        yield 'method after an array offset' => ['$value = $items[0]->name();'];
        yield 'dynamic method after a property' => ['$value = $invoice->party->{$method}();'];
        yield 'negation without a space' => ['if (!$allowed) {}'];
        yield 'short condition' => ['if ($value === null || $value === "") {}'];
        yield 'multiline compound condition' => ["if (\n    \$country === null\n    || \$format === null\n    || \$checkDigits < 2\n) {}"];
        yield 'simple arguments' => ['$value = save($id, $name);'];
        yield 'short accessor argument' => ['$value = save($id, $user->name());'];
        yield 'nested call on its own line' => ["\$value = save(\n    \$id,\n    normalize(\$input),\n);"];
        yield 'compact constructor inside outer call' => ["\$contact = \$contacts->create(\n    \$userId,\n    new ContactWriteData(\$invoice->parties->customer->toProfile(), null),\n);"];
        yield 'short ternary' => ['$value = $allowed ? "yes" : "no";'];
        yield 'short fallback' => ['$value = $label ?: "default";'];
        yield 'call-like string' => ['$value = save("call(argument)");'];
        yield 'sibling ternaries' => ['$values = [$first ? "a" : "b", $second ? "c" : "d"];'];
        yield 'short ternary after a concat assignment' => ["\$code = '';\n\$code .= \$allowed ? 'a' : 'b';"];
        yield 'short ternary after an addition assignment' => ["\$total = 0;\n\$total += \$allowed ? 1 : 2;"];
        yield 'short ternary after a coalesce assignment' => ["\$value = null;\n\$value ??= \$allowed ? 1 : 2;"];
        yield 'short ternaries in match arms' => [
            "\$first = 1;\n\$value = match (\$action) {\n    'view' => \$allowed ? 'a' : 'b',\n    'update' => \$allowed ? 'c' : 'd',\n};",
        ];
        yield 'short ternary statement' => ["\$first = 1;\n\$allowed ? start() : stop();"];
        yield 'grouped arithmetic argument' => ['$value = save(($first + $second));'];
        yield 'typed callable declaration' => ['function normalize(string $value): string { return $value; }'];
        yield 'short arrow and simple sibling' => ['save(fn (int $x): int => $x, $items);'];
        yield 'short arrow and accessor measured separately' => [
            'array_map(static fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::cases());',
        ];
        yield 'short arrow and constant measured separately' => [
            'array_map(fn ($type) => "entity_limits.entity_types.$type.singular", EntityLimit::ENTITY_TYPES);',
        ];
        yield 'complex arrow and separated sibling' => [
            "save(\n    fn (int \$x): int => normalize(\$x),\n    \$items,\n);",
        ];
        yield 'closure body keeps its internal commas' => [
            "save(\n    function (int \$x): array { return [\$x, \$x]; },\n    \$items,\n);",
        ];
        yield 'arrow parameters keep their internal commas' => [
            "save(\n    fn (int \$x, int \$y): int => combine(\$x, \$y),\n    \$items,\n);",
        ];
    }

    #[Test]
    public function keeps_match_arm_commas_inside_the_arrow_argument(): void
    {
        $this->assertSame(
            [],
            $this->violations(
                "save(\n    fn (int \$x): int => match (\$x) {1 => normalize(\$x), default => 0},\n    \$items,\n);",
            ),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function braceArgumentCode(): iterable
    {
        yield 'match arm conditions and results' => [
            'match ($x) {1, 2 => 3, default => 4}',
        ];
        yield 'closure body' => [
            'function (): void { echo 1, 2; }',
        ];
        yield 'anonymous class body' => [
            'new class { public function v(): void { echo 1, 2; } }',
        ];
        yield 'arrow returning a match' => [
            'fn (int $x): int => match ($x) {1, 2 => 3, default => 4}',
        ];
    }

    #[Test]
    #[DataProvider('braceArgumentCode')]
    public function accepts_two_separated_arguments_containing_internal_commas(string $argument): void
    {
        $this->assertSame(
            [],
            $this->violations("save(\n    " . $argument . ",\n    \$items,\n);"),
        );
    }

    /** @return iterable<string, array{string, bool}> */
    public static function countedCallCode(): iterable
    {
        yield 'no arguments' => ['save();', false];
        yield 'two arguments with a trailing comma' => ['save($x, $y,);', false];
        yield 'two arguments with compact match arms' => ['save(match ($x) {1, 2 => 3, default => 4}, $items);', false];
        foreach (
            [
                'function' => 'save',
                'constructor' => 'new Value',
                'self constructor' => 'new self',
                'parent constructor' => 'new parent',
                'late static constructor' => 'new static',
                'static method' => 'Value::save',
                'method' => '$service->save',
                'nullsafe method' => '$service?->save',
                'callable variable' => '$callback',
                'parenthesized callable' => '($callback)',
                'callable array offset' => '$callbacks[0]',
                'dynamic method' => '$service->{$method}',
            ] as $name => $callee
        ) {
            yield $name . ' with three arguments' => [$callee . '($x, $y, $z);', true];
            yield $name . ' with separated arguments' => [$callee . "(\n    \$x,\n    \$y,\n    \$z,\n);", false];
        }
        yield 'anonymous constructor with three arguments' => [
            '$value = new class($x, $y, $z) {};',
            true,
        ];
        yield 'anonymous constructor with separated arguments' => [
            "\$value = new class(\n    \$x,\n    \$y,\n    \$z,\n) {};",
            false,
        ];
        foreach (
            [
                'self',
                'parent',
                'static',
            ] as $class
        ) {
            yield $class . ' constructor with a complex argument' => [
                'new ' . $class . '(normalize($x), $y);',
                true,
            ];
        }
        yield 'anonymous constructor with a complex argument' => [
            '$value = new class(normalize($x), $y) {};',
            true,
        ];
        foreach (self::braceArgumentCode() as $name => [$argument]) {
            yield $name . ' with three real arguments' => [
                "save(\n    " . $argument . ",\n    \$x, \$y,\n);",
                true,
            ];
        }
    }

    #[Test]
    #[DataProvider('countedCallCode')]
    public function enforces_layout_using_the_actual_argument_count(string $source, bool $shouldReject): void
    {
        $this->assertSame(
            $shouldReject,
            $this->violations($source) !== [],
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function arrowArgumentCode(): iterable
    {
        yield 'typed arrow with a ternary' => [
            'fn (mixed $value): mixed => $value instanceof Unit ? $value->value : $value',
            '$items',
        ];
        yield 'untyped arrow with a nested call' => ['fn ($value) => normalize($value)', '$items'];
        yield 'short arrow before a complex sibling' => ['fn (): int => 1', 'normalize($value)'];
        yield 'short arrow before a long sibling' => [
            'fn (): int => 1',
            "'A deliberately long sibling argument exceeding the sixty character limit'",
        ];
        yield 'nested arrows' => [
            'fn (int $x): Closure => fn (int $y): int => combine($x, $y)',
            '$items',
        ];
        yield 'arrow returning an array' => [
            'fn (int $x): array => [$x, normalize($x)]',
            '$items',
        ];
        yield 'arrow returning a match expression' => [
            'fn (int $x): int => match ($x) {1 => normalize($x), default => 0}',
            '$items',
        ];
        yield 'adjacent arrows' => [
            'fn (int $x): int => normalize($x)',
            'fn (int $y): int => normalize($y)',
        ];
        yield 'named static arrow' => [
            'transform: static fn (int $x): int => normalize($x)',
            'items: $items',
        ];
        yield 'arrow with a DNF return type' => [
            'fn (A&B $x): (A&B)|null => normalize($x)',
            '$items',
        ];
        yield 'complex arrow after a sibling' => ['$items', 'fn (int $x): int => normalize($x)'];
    }

    #[Test]
    #[DataProvider('arrowArgumentCode')]
    public function rejects_complex_arrow_arguments_sharing_a_line(string $first, string $second): void
    {
        $this->assertContains(
            'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
            $this->violations("save(\n    " . $first . ', ' . $second . ",\n);"),
        );
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function measuredCode(): iterable
    {
        foreach (['a', 'ž'] as $character) {
            foreach (
                [
                    59,
                    60,
                    61,
                ] as $length
            ) {
                $argument = '"' . str_repeat($character, $length - 2) . '"';
                yield 'argument ' . $character . ' ' . $length => [
                    '$value = save(' . $argument . ');',
                    'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
                    $length > 60,
                ];
                $prefix = 'fn (): string => "';
                $argument = $prefix . str_repeat(
                    $character,
                    $length - strlen($prefix) - 1,
                ) . '"';
                yield 'arrow argument ' . $character . ' ' . $length => [
                    '$value = save(' . $argument . ', $items);',
                    'Billex.Functions.ComplexCallLayout.ItemsOnSharedLine',
                    $length > 60,
                ];
            }
            foreach (
                [
                    119,
                    120,
                    121,
                ] as $length
            ) {
                $prefix = '$allowed ? "';
                $suffix = '" : "no"';
                $padding = $length - strlen($prefix . $suffix);
                yield 'ternary ' . $character . ' ' . $length => [
                    '$value = ' . $prefix . str_repeat($character, $padding) . $suffix . ';',
                    'Billex.ControlStructures.ShortTernary.ComplexTernary',
                    $length > 120,
                ];
                $prefix = 'if ($allowed && $label === "';
                $suffix = '") {}';
                $padding = $length - strlen($prefix . $suffix);
                yield 'condition ' . $character . ' ' . $length => [
                    $prefix . str_repeat($character, $padding) . $suffix,
                    'Billex.ControlStructures.MultiLineCondition.RequiredMultiLineCondition',
                    $length > 120,
                ];
            }
        }
    }

    #[Test]
    #[DataProvider('measuredCode')]
    public function measures_layout_limits_in_utf8_characters(
        string $source,
        string $expectedSource,
        bool $shouldReject,
    ): void {
        $this->assertSame(
            $shouldReject,
            in_array(
                $expectedSource,
                $this->violations($source),
                true,
            ),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function documentationCode(): iterable
    {
        $root = dirname(__DIR__, 3);
        foreach (
            [
                'docs',
                '.claude/rules',
                '.claude/references',
            ] as $directory
        ) {
            $files = new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS);
            foreach (new RecursiveIteratorIterator($files) as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'md') {
                    continue;
                }
                $contents = (string) file_get_contents($file->getPathname());
                preg_match_all(
                    '/^```php[^\n]*\n(.*?)^```/ms',
                    $contents,
                    $matches,
                    PREG_OFFSET_CAPTURE,
                );
                foreach ($matches[1] as [$source, $offset]) {
                    $line = substr_count(
                        substr(
                            $contents,
                            0,
                            $offset,
                        ),
                        "\n",
                    ) + 1;
                    $name = substr(
                        $file->getPathname(),
                        strlen($root) + 1,
                    ) . ':' . $line;
                    if (str_starts_with($source, '<?php')) {
                        $source = substr($source, 5);
                    }
                    yield $name => [$source];
                }
            }
        }
    }

    #[Test]
    #[DataProvider('documentationCode')]
    public function documentation_examples_follow_php_layout(string $source): void
    {
        $this->assertSame(
            [],
            $this->violations($source),
        );
    }

    #[Test]
    public function leaves_chain_and_ternary_rewrites_to_the_reviewer(): void
    {
        $source = <<<'PHP'
            <?php
            $invoice = Invoice::query()->where("id", $id)->first();
            $value = ($fallback ?: "none") ? "yes" : "no";
            PHP;
        $fixer = $this->command('phpcbf');
        $fixer->setInput($source);
        $fixer->run();
        $fixed = $fixer->getOutput();

        $this->assertSame(
            $this->significantTokens($source),
            $this->significantTokens($fixed),
        );
        $this->assertContains(
            'Billex.Functions.DisallowCallChaining.ChainedCall',
            $this->violations(
                substr($fixed, 6),
            ),
        );
        $this->assertContains(
            'Billex.ControlStructures.ShortTernary.ComplexTernary',
            $this->violations(
                substr($fixed, 6),
            ),
        );
    }

    #[Test]
    #[DataProvider('acceptedCode')]
    public function accepts_readable_layout(string $source): void
    {
        $this->assertSame(
            [],
            $this->violations($source),
        );
    }

    #[Test]
    public function fixes_nested_calls_and_conditions_without_changing_their_tokens(): void
    {
        $source = <<<'PHP'
            <?php
            $value = save($id, normalize($input));
            if ($country === null || $format === null || $checkDigits < 2 || $checkDigits > 98 || preg_match($format, $iban) !== 1) {}
            PHP;
        $process = $this->command('phpcbf');
        $process->setInput($source);
        $process->run();
        $fixed = $process->getOutput();

        $this->assertSame(
            [],
            $this->violations(
                substr($fixed, 6),
            ),
        );
        $this->assertSame(
            $this->significantTokens($source),
            $this->significantTokens($fixed),
        );
    }

    /** @return iterable<string, array{string, bool, string}> */
    public static function conditionLayoutCode(): iterable
    {
        yield 'call lines hide a shared boolean part' => [
            "if (\n    \$zero && !equals(\n        new Value('0', \$currency),\n    )\n) {}",
            true,
            "\n    && !equals(",
        ];
        yield 'several call lines hide a shared final part' => [
            "if (\n    check(\n        \$first,\n        \$second,\n    ) && \$ready\n) {}",
            true,
            "\n    && \$ready",
        ];
        yield 'nested grouping with call operand' => [
            "if (\n    \$ready\n    && (\$first || check(\n        \$second,\n        \$third,\n    ))\n) {}",
            true,
            "&& (\n        \$first\n        || check(",
        ];
        yield 'group lines hide an outer shared part' => [
            "if (\n    (\n        \$first\n        || \$second\n    ) && \$ready\n) {}",
            true,
            "\n    && \$ready",
        ];
        yield 'operator trails a call operand' => [
            "if (\n    check(\n        \$first,\n        \$second,\n    ) &&\n    \$ready\n) {}",
            true,
            "\n    && \$ready",
        ];
        yield 'split operator and right operand' => [
            "if (\n    \$first\n    &&\n    \$second\n) {}",
            true,
            "\n    && \$second",
        ];
        yield 'comment before a hidden operator' => [
            "if (\n    \$first /* keep this reason */ && check(\n        \$second,\n        \$third,\n    )\n) {}",
            true,
            "/* keep this reason */\n    && check(",
        ];
        yield 'while shares parts' => [
            "while (\n    \$ready && check(\n        \$first,\n        \$second,\n    )\n) {}",
            true,
            "\n    && check(",
        ];
        yield 'elseif shares parts' => [
            "if (\$done) {\n} elseif (\n    \$ready || check(\n        \$first,\n        \$second,\n    )\n) {}",
            true,
            "\n    || check(",
        ];
        yield 'do while shares parts' => [
            "do {\n} while (\n    \$ready && check(\n        \$first,\n        \$second,\n    )\n);",
            true,
            "\n    && check(",
        ];
        yield 'already separated call operand' => [
            "if (\n    \$first\n    && check(\n        \$second,\n        \$third,\n    )\n) {}",
            false,
            "\n    && check(",
        ];
        yield 'already separated nested group' => [
            "if (\n    \$first\n    && (\n        \$second\n        || \$third\n    )\n) {}",
            false,
            "\n        || \$third",
        ];
        yield 'call argument boolean is not a condition part' => [
            "if (\n    check(\n        \$first && \$second,\n        \$third,\n    )\n) {}",
            false,
            '$first && $second',
        ];
        yield 'closure boolean is not a condition part' => [
            "if (\n    check(\n        function (): bool { return \$first && \$second; },\n    )\n) {}",
            false,
            'return $first && $second;',
        ];
        yield 'arrow boolean is not a condition part' => [
            "if (\n    check(\n        fn (): bool => \$first && \$second,\n    )\n) {}",
            false,
            '$first && $second,',
        ];
        yield 'array boolean is not a condition part' => [
            "if (\n    [\n        \$first && \$second,\n    ] !== []\n) {}",
            false,
            '$first && $second,',
        ];
        yield 'match arm boolean is not a condition part' => [
            "if (\n    match (\$value) {\n        1 => \$first && \$second,\n        default => false,\n    }\n) {}",
            false,
            '$first && $second,',
        ];
        yield 'call boolean stays inline when outer parts split' => [
            "if (\n    check(\$first && \$second) && \$ready\n) {}",
            true,
            "check(\$first && \$second)\n    && \$ready",
        ];
        yield 'isset argument boolean stays inline' => [
            "if (\n    isset(\$items[\$first && \$second]) && \$ready\n) {}",
            true,
            "isset(\$items[\$first && \$second])\n    && \$ready",
        ];
        yield 'empty argument boolean stays inline' => [
            "if (\n    empty(\$first && \$second) && \$ready\n) {}",
            true,
            "empty(\$first && \$second)\n    && \$ready",
        ];
        yield 'anonymous class body keeps its boolean expression' => [
            "if (\n    new class {\n        public function ready(): bool { return \$first && \$second; }\n    } && \$ready\n) {}",
            true,
            "\n    && \$ready",
        ];
        yield 'direct arrow expression owns its boolean body' => [
            "if (\n    (fn (): bool => \$first && \$second)\n) {}",
            false,
            '$first && $second)',
        ];
        yield 'multiple grouping levels' => [
            "if (\n    ((\$first && \$second)) || \$third\n) {}",
            true,
            "\n            && \$second",
        ];
        yield 'line comment after an operator' => [
            "if (\n    \$first && // keep this reason\n    \$second\n) {}",
            true,
            "&& // keep this reason\n",
        ];
        yield 'line comment before an operator' => [
            "if (\n    \$first // keep this reason\n    && \$second\n) {}",
            false,
            "// keep this reason\n",
        ];
        yield 'multiline literal followed by an operator' => [
            "if (\n    \$value === 'first\nsecond' && \$ready\n) {}",
            true,
            "second'\n    && \$ready",
        ];
        yield 'dynamic call owns its argument boolean' => [
            "if (\n    \$object?->{\$method}(\$first && \$second) && \$ready\n) {}",
            true,
            "(\$first && \$second)\n    && \$ready",
        ];
        yield 'already separated compound call argument' => [
            "if (\n    \$ready\n    && check(\$first && \$second)\n) {}",
            false,
            'check($first && $second)',
        ];
        foreach (
            [
                'and',
                'or',
                'xor',
            ] as $operator
        ) {
            yield 'word operator ' . $operator => [
                "if (\n    \$first " . $operator . " check(\n        \$second,\n        \$third,\n    )\n) {}",
                true,
                "\n    " . $operator . ' check(',
            ];
        }

    }

    #[Test]
    #[DataProvider('conditionLayoutCode')]
    public function checks_actual_boolean_part_positions(
        string $source,
        bool $shouldReject,
        string $expectedLayout,
    ): void {
        $this->assertSame(
            $shouldReject,
            in_array(
                'Billex.ControlStructures.MultiLineCondition.RequiredMultiLineCondition',
                $this->violations($source),
                true,
            ),
        );
        $fixer = $this->command('phpcbf');
        $fixer->setInput("<?php\n" . $source . "\n");
        $fixer->run();
        $fixed = $fixer->getOutput();
        $this->assertStringContainsString($expectedLayout, $fixed);
        if (!$shouldReject) {
            $this->assertSame("<?php\n" . $source . "\n", $fixed);
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function formatterCode(): iterable
    {
        foreach (self::conditionLayoutCode() as $name => $case) {
            yield $name => [$case[0], $case[2]];
        }
        yield 'nested call and condition' => [
            'if (! $allowed || $country === null || $format === null || $checkDigits < 2 || $checkDigits > 98 || preg_match($format, $iban) !== 1) {}'
                . "\n" . '$value = save($id, normalize($input));',
            '!$allowed',
        ];
        foreach (self::arrowArgumentCode() as $name => [$first, $second]) {
            yield $name => [
                "save(\n    " . $first . ', ' . $second . ",\n);",
                ",\n    " . $second . ",\n",
            ];
        }
        foreach (self::braceArgumentCode() as $name => [$argument]) {
            yield $name . ' with three real arguments' => [
                "save(\n    " . $argument . ",\n    \$x, \$y,\n);",
                ",\n    \$x,\n    \$y,\n",
            ];
        }
        foreach (self::countedCallCode() as $name => [$source, $shouldReject]) {
            if ($shouldReject && !str_starts_with($source, "save(\n")) {
                yield $name => [$source, "\n    \$y,\n"];
            }
        }
    }

    #[Test]
    #[DataProvider('braceArgumentCode')]
    public function leaves_separated_call_arguments_and_their_brace_contents_unchanged(string $argument): void
    {
        $source = "<?php\nsave(\n    " . $argument . ",\n    \$items,\n);\n";
        $fixer = $this->command('phpcbf');
        $fixer->setInput($source);
        $fixer->run();
        $this->assertSame($source, $fixer->getOutput());
    }

    #[Test]
    #[DataProvider('formatterCode')]
    public function pint_preserves_the_layout_fixed_by_phpcbf(string $code, string $expectedLayout): void
    {
        $root = dirname(__DIR__, 3);
        $directory = $root . '/dev_docs/ai/tmp/code-style-tests';
        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0777,
                true,
            );
        }
        $path = tempnam($directory, 'layout-');
        $phpPath = $path . '.php';
        rename($path, $phpPath);
        $source = "<?php\n" . $code . "\n";
        try {
            $fixer = $this->command('phpcbf');
            $fixer->setInput($source);
            $fixer->run();
            file_put_contents($phpPath, $fixer->getOutput());
            $pint = new Process(
                [
                    PHP_BINARY,
                    $root . '/vendor/bin/pint',
                    '--config=' . $root . '/pint.json',
                    $phpPath,
                ],
                $root,
            );
            $this->assertSame(
                0,
                $pint->run(),
                $pint->getOutput() . $pint->getErrorOutput(),
            );
            $fixed = (string) file_get_contents($phpPath);
            $this->assertStringContainsString($expectedLayout, $fixed);
            $this->assertSame(
                [],
                $this->violations(
                    substr($fixed, 6),
                ),
            );
            $this->assertSame(
                $this->significantTokens($source),
                $this->significantTokens($fixed),
            );
            $fixer->setInput($fixed);
            $fixer->run();
            $this->assertSame($fixed, $fixer->getOutput());
        } finally {
            unlink($phpPath);
        }
    }

    /** @return list<string> */
    private function violations(string $source): array
    {
        $process = $this->command('phpcs');
        $process->setInput("<?php\n" . $source . "\n");
        $process->run();
        $report = json_decode(
            $process->getOutput(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $sources = [];
        foreach ($report['files'] as $file) {
            foreach ($file['messages'] as $message) {
                $sources[] = $message['source'];
            }
        }

        return $sources;
    }

    private function command(string $binary): Process
    {
        $root = dirname(__DIR__, 3);

        return new Process(
            [
                PHP_BINARY,
                $root . '/vendor/bin/' . $binary,
                '--standard=' . $root . '/phpcs.xml',
                '--report=json',
                '--stdin-path=app/StyleExample.php',
                '-',
            ],
            $root,
        );
    }

    /** @return list<array{int, string}|string> */
    private function significantTokens(string $source): array
    {
        $tokens = [];
        $parsed = token_get_all($source, TOKEN_PARSE);
        foreach ($parsed as $index => $token) {
            if (!is_array($token)) {
                // A trailing argument comma is the only non-whitespace token the layout fixer may add.
                if ($token === ',') {
                    $next = $index + 1;
                    while (isset($parsed[$next]) && is_array($parsed[$next]) && $parsed[$next][0] === T_WHITESPACE) {
                        $next++;
                    }
                    if (($parsed[$next] ?? null) === ')') {
                        continue;
                    }
                }
                $tokens[] = $token;

                continue;
            }
            if ($token[0] !== T_WHITESPACE) {
                $tokens[] = [$token[0], $token[1]];
            }
        }

        return $tokens;
    }
}
