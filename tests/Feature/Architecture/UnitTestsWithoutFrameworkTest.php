<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Tests in tests/Unit run without Laravel and without a database (`rules/testing.md`, Test types), so CI runs
 * them in a job without MySQL. Tests\TestCase, facades and global helpers fail there; `new Model()` would not
 * fail, it would only hide that the test belongs to tests/Feature, so this test catches both.
 */
final class UnitTestsWithoutFrameworkTest extends TestCase
{
    private const FORBIDDEN_REFERENCES = [
        'Tests\\TestCase' => 'boots Laravel',
        'App\\Models\\' => 'uses an Eloquent model',
        'Illuminate\\Support\\Facades\\' => 'uses a facade',
        'Illuminate\\Foundation\\' => 'uses the framework',
    ];

    private const HELPER_PATTERN = '/(?<![\w$>:\\\\])(?<!function )(app|auth|base_path|cache|config|event|logger|now|resolve|route|session|today|trans|url|view|__)\(/';

    #[Test]
    public function unit_tests_use_neither_laravel_nor_models(): void
    {
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('tests/Unit')));

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            $path = str_replace(
                base_path() . '/',
                '',
                $file->getPathname(),
            );

            foreach (self::FORBIDDEN_REFERENCES as $reference => $reason) {
                if (str_contains($source, $reference)) {
                    $violations[] = "{$path} {$reason} ({$reference})";
                }
            }

            if (preg_match(
                self::HELPER_PATTERN,
                $source,
                $match,
            ) === 1) {
                $violations[] = "{$path} calls the Laravel helper {$match[1]}()";
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Move these tests to tests/Feature or remove the framework from them:\n" . implode("\n", $violations),
        );
    }
}
