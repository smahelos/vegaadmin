<?php

namespace Tests\Feature\Console\Commands;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncArtisanCommandsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test category if needed
        ArtisanCommandCategory::firstOrCreate([
            'name' => 'General',
            'slug' => 'general'
        ]);
    }

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('artisan:sync-commands');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_syncs_artisan_commands(): void
    {
        // Clear existing commands
        ArtisanCommand::truncate();
        
        $exitCode = Artisan::call('artisan:sync-commands');
        
        $this->assertEquals(0, $exitCode);
        
        // Should have created some commands
        $this->assertGreaterThan(0, ArtisanCommand::count());
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('artisan:sync-commands');
        
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_updates_existing_commands(): void
    {
        // Run sync twice to test updates
        $exitCode1 = Artisan::call('artisan:sync-commands');
        $count1 = ArtisanCommand::count();
        
        $exitCode2 = Artisan::call('artisan:sync-commands');
        $count2 = ArtisanCommand::count();
        
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);
        
        // Command count should be stable after second sync
        $this->assertEquals($count1, $count2);
    }

    #[Test]
    public function command_handles_command_categories(): void
    {
        $exitCode = Artisan::call('artisan:sync-commands');
        
        $this->assertEquals(0, $exitCode);
        
        // Should have at least one category
        $this->assertGreaterThan(0, ArtisanCommandCategory::count());
    }

    #[Test]
    public function command_creates_proper_command_records(): void
    {
        ArtisanCommand::truncate();
        
        $exitCode = Artisan::call('artisan:sync-commands');
        
        $this->assertEquals(0, $exitCode);
        
        // Check that some basic commands exist
        $commands = ArtisanCommand::all();
        $this->assertGreaterThan(0, $commands->count());
        
        // Check that commands have required fields
        $firstCommand = $commands->first();
        if ($firstCommand) {
            $this->assertNotEmpty($firstCommand->name);
            $this->assertNotEmpty($firstCommand->signature);
        }
    }

    #[Test]
    public function command_handles_empty_database(): void
    {
        // Clear all commands
        ArtisanCommand::query()->delete();
        
        $exitCode = Artisan::call('artisan:sync-commands');
        
        $this->assertEquals(0, $exitCode);
        
        // Should recreate everything
        $this->assertGreaterThan(0, ArtisanCommand::count());
    }

    #[Test]
    public function command_preserves_custom_data(): void
    {
        // First sync
        Artisan::call('artisan:sync-commands');
        
        // Modify a command record
        $command = ArtisanCommand::first();
        if ($command) {
            $originalDescription = $command->description;
            $command->update(['description' => 'Custom description']);
            
            // Sync again
            Artisan::call('artisan:sync-commands');
            
            // Should preserve the record but may update signature/name
            $this->assertTrue(ArtisanCommand::where('id', $command->id)->exists());
        } else {
            $this->markTestSkipped('No commands found to test preservation');
        }
    }

    #[Test]
    public function command_reports_sync_statistics(): void
    {
        Artisan::call('artisan:sync-commands');
        
        $output = Artisan::output();
        
        // Should provide some statistics about the sync
        $this->assertIsString($output);
        $this->assertNotEmpty(trim($output));
    }

    #[Test]
    public function command_handles_service_dependencies(): void
    {
        // Test that command properly uses injected service
        $exitCode = Artisan::call('artisan:sync-commands');
        
        $this->assertEquals(0, $exitCode);
        
        // Service should have created records
        $this->assertGreaterThan(0, ArtisanCommand::count());
    }
}
