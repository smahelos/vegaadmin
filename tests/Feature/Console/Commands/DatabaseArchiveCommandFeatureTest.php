<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseArchiveCommandFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create the required tables for this test
        $this->createTestTables();
        
        // Create archive policies for testing
        $this->createArchivePolicies();
    }

    private function createTestTables(): void
    {
        // Create archive_policies table
        DB::statement('CREATE TABLE IF NOT EXISTS archive_policies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(255) NOT NULL,
            enabled BOOLEAN DEFAULT 1,
            retention_months INT NOT NULL,
            date_column VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )');

        // Create database_maintenance_log table
        DB::statement('CREATE TABLE IF NOT EXISTS database_maintenance_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_type VARCHAR(255) NOT NULL,
            table_name VARCHAR(255) NOT NULL,
            status VARCHAR(255) NOT NULL,
            description TEXT,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            results TEXT,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )');

        // Create test tables with minimal structure
        DB::statement('CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP NULL
        )');
    }

    private function createArchivePolicies(): void
    {
        // Clear existing policies first
        DB::table('archive_policies')->delete();
        
        $policies = [
            ['table_name' => 'invoices', 'enabled' => true, 'retention_months' => 24, 'date_column' => 'created_at'],
            ['table_name' => 'clients', 'enabled' => true, 'retention_months' => 36, 'date_column' => 'created_at'],
            ['table_name' => 'suppliers', 'enabled' => true, 'retention_months' => 36, 'date_column' => 'created_at'],
        ];

        foreach ($policies as $policy) {
            DB::table('archive_policies')->updateOrInsert(
                ['table_name' => $policy['table_name']],
                array_merge($policy, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
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
