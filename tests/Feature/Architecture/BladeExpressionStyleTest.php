<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use FilesystemIterator;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * phpcs.xml excludes raw Blade, whose HTML and directives are not PHP. The chain and ternary rules
 * of rules/php.md reach the PHP written in a template through its compiled form. The layout rules
 * do not apply, because compilation rewrites the layout (docs/design/DESIGN_PHP_STYLE.md).
 */
final class BladeExpressionStyleTest extends TestCase
{
    private const VIEWS = 'resources/views';

    /**
     * Chains the compiler writes, as the code before and after the `->` the sniff reports.
     * A directive compiling to another form fails the test and gets its pair here.
     */
    private const COMPILER_OUTPUT = [
        '@include' => ["['__data' => 1, '__path' => 1]))", '->render(); ?>'],
        '@auth' => ['<?php if(auth()', '->guard()->check()): ?>'],
        '@auth, second link' => ['<?php if(auth()->guard()', '->check()): ?>'],
        '@guest' => ['<?php if(auth()', '->guard()->guest()): ?>'],
        '@guest, second link' => ['<?php if(auth()->guard()', '->guest()): ?>'],
        '@can' => ['<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)', '->check('],
        '@livewire' => ["\$__html = app('livewire')", '->mount('],
        '@js' => ['', '->toHtml() ?>'],
    ];

    #[Test]
    public function templates_hold_no_chain_or_complex_ternary(): void
    {
        $root = base_path(self::VIEWS);
        $templates = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $path = $file->getPathname();

            if (str_ends_with($path, '.blade.php')) {
                $templates[substr(
                    $path,
                    strlen($root) + 1,
                )] = (string) file_get_contents($path);
            }
        }

        $this->assertNotSame([], $templates);

        $findings = $this->findings($templates);

        $this->assertSame(
            [],
            $findings,
            "Rewrite these expressions in the template (rules/php.md, Readability):\n" . implode("\n", $findings),
        );
    }

    #[Test]
    public function reports_chains_and_complex_ternaries_written_in_a_template(): void
    {
        $longBranch = str_repeat('a', 120);

        $findings = $this->findings(
            [
                'chain.blade.php' => '{{ $invoice->client()->name() }}',
                'php-block.blade.php' => "@php\n\$name = \$invoice->client()->name();\n@endphp",
                'component.blade.php' => '<x-detail.card :title="$invoice->client()->name()" />',
                'ternary.blade.php' => "{{ \$paid ? '{$longBranch}' : 'b' }}",
            ],
        );

        $this->assertCount(
            4,
            array_unique(
                array_map(
                    fn (string $finding): string => strstr(
                        $finding,
                        ':',
                        true,
                    ),
                    $findings,
                ),
            ),
            implode("\n", $findings),
        );
    }

    #[Test]
    public function ignores_what_the_directives_compile_to(): void
    {
        $findings = $this->findings(
            [
                'directives.blade.php' => implode(
                    "\n",
                    [
                        "@include('partial')",
                        '@auth a @endauth',
                        '@guest b @endguest',
                        "@can('permission') c @endcan",
                        "@livewire('counter')",
                        '@js($value)',
                    ],
                ),
            ],
        );

        $this->assertSame([], $findings);
    }

    /**
     * @param  array<string, string>  $templates  Blade source by path relative to the views
     * @return list<string>
     */
    private function findings(array $templates): array
    {
        $directory = sys_get_temp_dir() . '/billex-blade-style-' . uniqid('', true);
        $compiled = [];

        try {
            foreach ($templates as $name => $source) {
                $target = $directory . '/' . str_replace(
                    '.blade.php',
                    '.php',
                    $name,
                );

                if (
                    !is_dir(
                        dirname($target),
                    )
                ) {
                    mkdir(
                        dirname($target),
                        0777,
                        true,
                    );
                }

                Blade::setPath(
                    base_path(self::VIEWS . '/' . $name),
                );
                $compiled[$name] = Blade::compileString($source);
                file_put_contents($target, $compiled[$name]);
            }

            $process = new Process(
                [
                    PHP_BINARY,
                    base_path('vendor/bin/phpcs'),
                    '-q',
                    '--no-cache',
                    '--standard=' . base_path('phpcs.xml'),
                    '--sniffs=Billex.Functions.DisallowCallChaining,Billex.ControlStructures.ShortTernary',
                    '--report=json',
                    '--basepath=' . $directory,
                    $directory,
                ],
                base_path(),
            );
            $process->run();
            $report = json_decode(
                $process->getOutput(),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        } finally {
            $this->deleteDirectory($directory);
        }

        $findings = [];

        foreach ($report['files'] as $name => $file) {
            $template = substr(
                $name,
                0,
                -strlen('.php'),
            ) . '.blade.php';
            $lines = explode("\n", $compiled[$template]);

            foreach ($file['messages'] as $message) {
                if (!$this->isCompilerOutput($lines[$message['line'] - 1], $message['column'] - 1)) {
                    $findings[] = $template . ': ' . trim($lines[$message['line'] - 1]);
                }
            }
        }

        return $findings;
    }

    private function isCompilerOutput(string $line, int $column): bool
    {
        $before = substr(
            $line,
            0,
            $column,
        );
        $after = substr($line, $column);

        foreach (self::COMPILER_OUTPUT as [$expectedBefore, $expectedAfter]) {
            if (str_ends_with($before, $expectedBefore) && str_starts_with($after, $expectedAfter)) {
                return true;
            }
        }

        return false;
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $entry */
        foreach ($entries as $entry) {
            $path = $entry->getPathname();

            if ($entry->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
