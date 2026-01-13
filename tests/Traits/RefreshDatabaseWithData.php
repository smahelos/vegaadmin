<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

trait RefreshDatabaseWithData
{
    use RefreshDatabase {
        refreshTestDatabase as parentRefreshTestDatabase;
    }

    /**
     * Override to disable transactions and use fresh database for each test
     */
    protected function refreshTestDatabase()
    {
        // Close all database connections first
        DB::disconnect();
        
        // Always run fresh migrations for complete isolation
        $this->artisan('migrate:fresh');
        
        // Allow test classes to disable seeding by setting $seedDatabase = false
        if (!property_exists($this, 'seedDatabase') || $this->seedDatabase !== false) {
            $this->artisan('db:seed');
        }
        
        // Do NOT call beginDatabaseTransaction() - this eliminates transaction conflicts
        // Each test gets completely fresh database state
    }

    /**
     * Ensure clean teardown.
     */
    protected function tearDown(): void
    {
        // Close database connections to prevent locking
        DB::disconnect();
        
        parent::tearDown();
    }
}
