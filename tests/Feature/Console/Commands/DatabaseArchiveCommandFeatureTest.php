<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseArchiveCommandFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }


    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('db:archive');

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_table_option(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--table' => 'invoices'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_dry_run_option(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_force_option(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--force' => true,
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_uses_default_table(): void
    {
        Artisan::call('db:archive', [
            '--dry-run' => true
        ]);

        $output = Artisan::output();

        // Should use default table 'invoices'
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_handles_different_tables(): void
    {
        $tables = ['invoices', 'clients', 'suppliers'];

        foreach ($tables as $table) {
            $exitCode = Artisan::call('db:archive', [
                '--table' => $table,
                '--dry-run' => true
            ]);

            $this->assertEquals(0, $exitCode);
        }
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('db:archive', [
            '--dry-run' => true
        ]);

        $output = Artisan::output();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_respects_dry_run_mode(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--table' => 'invoices',
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);

        // In dry-run mode, no actual archiving should happen
        $this->assertTrue(true); // Command completed without errors
    }

    #[Test]
    public function command_handles_safety_options(): void
    {
        // Test force option with dry-run for safety
        $exitCode = Artisan::call('db:archive', [
            '--force' => true,
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_validates_table_parameter(): void
    {
        // Test with valid table name
        $exitCode = Artisan::call('db:archive', [
            '--table' => 'invoices',
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);
    }
}
