<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Snapshot-like test for ddd:guard --di JSON output schema.
 * Ensures presence of required top-level keys and DI section keys.
 */
class DddGuardDiJsonSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ddd_guard_di_json_structure_is_valid(): void
    {
        // Run using Artisan facade to ensure full Laravel bootstrap
        // Capture output by buffering stdout via Symfony Stringable output capture
        $json = '';
        $exitCode = \Artisan::call('ddd:guard', ['--di' => true, '--format' => 'json']);
        $json = \Artisan::output();

        $data = json_decode($json, true);
        $this->assertIsArray($data, 'Output must decode to array');
        foreach (['status','violations','advisories','di'] as $key) {
            $this->assertArrayHasKey($key, $data, "Missing top-level key {$key}");
        }
        $this->assertIsArray($data['violations']);
        $this->assertIsArray($data['advisories']);

        if (!is_null($data['di'])) {
            $this->assertArrayHasKey('violations', $data['di']);
            $this->assertArrayHasKey('advisories', $data['di']);
            $this->assertIsArray($data['di']['violations']);
            $this->assertIsArray($data['di']['advisories']);
            foreach ($data['di']['violations'] as $v) {
                foreach (['type','path','line','message'] as $vk) {
                    $this->assertArrayHasKey($vk, $v, "DI violation missing {$vk}");
                }
            }
        }

        // Basic sanity: exit code should reflect violations presence
        if (!empty($data['di']['violations']) || !empty($data['violations'])) {
            $this->assertNotSame(0, $exitCode, 'Exit code should be non-zero when violations exist');
        }
    }
}
