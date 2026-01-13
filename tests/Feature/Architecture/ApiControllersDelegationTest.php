<?php

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures selected API controllers are delegating (no direct query building / model static calls / DB facade usage).
 * Target controllers: Api/InvoiceController.php, Api/StatisticsController.php
 */
class ApiControllersDelegationTest extends TestCase
{
    private array $targets = [
        'Http/Controllers/Api/InvoiceController.php',
        'Http/Controllers/Api/StatisticsController.php',
    ];

    #[Test]
    public function api_controllers_do_not_contain_direct_query_logic(): void
    {
        $root = app_path();
        $violations = [];
        foreach ($this->targets as $rel) {
            $path = $root.'/'.$rel;
            $this->assertFileExists($path, "Controller missing: $rel");
            $code = file_get_contents($path);
            $issues = $this->analyze($code);
            if (!empty($issues)) {
                $violations[] = $rel." =>\n  - ".implode("\n  - ", $issues);
            }
        }
        if (!empty($violations)) {
            $this->fail("API controller delegation violations:\n".implode("\n", $violations));
        }
        $this->assertTrue(true);
    }

    private function analyze(string $code): array
    {
        $issues = [];
        // Disallow direct Eloquent static calls
        if (preg_match('/App\\\\Models\\\\[A-Za-z0-9_]+::/', $code)) {
            $issues[] = 'Direct Eloquent static call detected.';
        }
        // Disallow DB facade
        if (preg_match('/DB::[a-zA-Z_]+\s*\(/', $code)) {
            $issues[] = 'Direct DB facade usage detected.';
        }
        // Disallow query builder chain tokens typical in controllers (select, join, groupBy) if not part of service call
        if (preg_match('/->(select|join|leftJoin|groupBy|orderBy|where|whereIn)\s*\(/', $code)) {
            $issues[] = 'Query builder chain fragments detected.';
        }
        return $issues;
    }
}
