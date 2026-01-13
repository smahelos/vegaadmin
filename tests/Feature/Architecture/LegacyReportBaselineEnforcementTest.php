<?php

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures the committed legacy report baseline JSON exists and is well-formed.
 * This guards accidental deletion and provides quick visibility if regeneration is needed.
 */
class LegacyReportBaselineEnforcementTest extends TestCase
{
    #[Test]
    public function legacy_report_baseline_exists_and_has_expected_structure(): void
    {
        $path = base_path('architecture/baselines/legacy_report_latest.json');
        $this->assertFileExists($path, 'Missing legacy_report_latest.json baseline. Generate via: docker exec INVOICE-php-fpm php artisan ddd:legacy-report --json --write-baseline and commit.');
        $json = json_decode(file_get_contents($path), true);
        $this->assertIsArray($json, 'Invalid JSON structure in legacy_report_latest.json');
        $this->assertArrayHasKey('counts', $json, 'Missing counts section');
        $this->assertArrayHasKey('residuals', $json, 'Missing residuals array');
        $this->assertArrayHasKey('neutralized_tests', $json, 'Missing neutralized_tests array');
        $this->assertIsArray($json['residuals']);
        $this->assertIsArray($json['neutralized_tests']);
        $this->assertArrayHasKey('residuals', $json['counts']);
        $this->assertArrayHasKey('neutralized_tests', $json['counts']);
    }
}
