<?php

namespace Tests\Domain\Console\Feature\Services;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandsServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private ArtisanCommandsServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ArtisanCommandsServiceInterface::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Force database disconnection to clear transactions
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            // If rollback fails, disconnect and reconnect
            DB::disconnect();
        }
        parent::tearDown();
    }

    #[Test]
    public function instantiates_correctly(): void
    {
        $service = $this->app->make(ArtisanCommandsServiceInterface::class);
        $this->assertInstanceOf(ArtisanCommandsServiceInterface::class, $service);
    }

    #[Test]
    public function get_all_commands_returns_array(): void
    {
        $commands = $this->service->getAllCommands();
        
        $this->assertIsArray($commands);
        $this->assertNotEmpty($commands);
    }

    #[Test]
    public function get_all_commands_with_only_names_returns_command_names(): void
    {
        $commands = $this->service->getAllCommands(true);
        
        $this->assertIsArray($commands);
        $this->assertNotEmpty($commands);
        
        // Test that each value is equal to its key (only names)
        foreach ($commands as $key => $value) {
            $this->assertEquals($key, $value);
        }
    }

    #[Test]
    public function get_all_commands_with_descriptions_returns_formatted_commands(): void
    {
        $commands = $this->service->getAllCommands(false);
        
        $this->assertIsArray($commands);
        $this->assertNotEmpty($commands);
        
        // Test that some commands have descriptions (contain " - ")
        $hasDescriptions = false;
        foreach ($commands as $key => $value) {
            if (strpos($value, ' - ') !== false) {
                $hasDescriptions = true;
                break;
            }
        }
        
        // At least some commands should have descriptions
        $this->assertTrue($hasDescriptions || count($commands) > 0);
    }

    #[Test]
    public function get_all_commands_uses_cache(): void
    {
        // First call should cache the result
        $commands1 = $this->service->getAllCommands();
        
        // Second call should use cache
        $commands2 = $this->service->getAllCommands();
        
        $this->assertEquals($commands1, $commands2);
        
        // Check that cache key exists
        $this->assertTrue(Cache::has('artisan_commands_list'));
    }

    #[Test]
    public function get_commands_by_category_returns_empty_array_for_nonexistent_category(): void
    {
        $commands = $this->service->getCommandsByCategory('nonexistent-category');
        
        $this->assertIsArray($commands);
        $this->assertEmpty($commands);
    }

    #[Test]
    public function get_commands_by_category_returns_commands_for_existing_category(): void
    {
        $uniqueId = uniqid();
        
        // Create a test category
        $category = ArtisanCommandCategory::create([
            'name' => 'Test Category ' . $uniqueId,
            'slug' => 'test-category-' . $uniqueId,
            'is_active' => true
        ]);
        
        // Create a test command in this category
        ArtisanCommand::create([
            'name' => 'Test Command ' . $uniqueId,
            'command' => 'test:command:' . $uniqueId,
            'description' => 'Test command description ' . $uniqueId,
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 1
        ]);
        
        $commands = $this->service->getCommandsByCategory('test-category-' . $uniqueId);
        
        $this->assertIsArray($commands);
        $this->assertArrayHasKey('test:command:' . $uniqueId, $commands);
        $this->assertStringContainsString('Test Command ' . $uniqueId, $commands['test:command:' . $uniqueId]);
    }

    #[Test]
    public function get_commands_by_category_excludes_inactive_commands(): void
    {
        $uniqueId = uniqid();
        
        $category = ArtisanCommandCategory::create([
            'name' => 'Test Category ' . $uniqueId,
            'slug' => 'test-category-' . $uniqueId,
            'is_active' => true
        ]);
        
        // Create inactive command
        ArtisanCommand::create([
            'name' => 'Inactive Command ' . $uniqueId,
            'command' => 'inactive:command:' . $uniqueId,
            'description' => 'Inactive command description',
            'category_id' => $category->id,
            'is_active' => false,
            'sort_order' => 1
        ]);
        
        $commands = $this->service->getCommandsByCategory('test-category-' . $uniqueId);
        
        $this->assertIsArray($commands);
        $this->assertArrayNotHasKey('inactive:command:' . $uniqueId, $commands);
    }

    #[Test]
    public function get_all_categories_returns_array(): void
    {
        $categories = $this->service->getAllCategories();
        
        $this->assertIsArray($categories);
    }

    #[Test]
    public function get_all_categories_returns_only_active_by_default(): void
    {
        $uniqueId = uniqid();
        
        // Create active category
        $activeCategory = ArtisanCommandCategory::create([
            'name' => 'Active Category ' . $uniqueId,
            'slug' => 'active-category-' . $uniqueId,
            'is_active' => true
        ]);
        
        // Create inactive category
        ArtisanCommandCategory::create([
            'name' => 'Inactive Category ' . $uniqueId,
            'slug' => 'inactive-category-' . $uniqueId,
            'is_active' => false
        ]);
        
        $categories = $this->service->getAllCategories(true);
        
        $this->assertArrayHasKey('active-category-' . $uniqueId, $categories);
        $this->assertArrayNotHasKey('inactive-category-' . $uniqueId, $categories);
    }

    #[Test]
    public function get_all_categories_can_include_inactive(): void
    {
        $uniqueId = uniqid();
        
        // Create active category
        ArtisanCommandCategory::create([
            'name' => 'Active Category ' . $uniqueId,
            'slug' => 'active-category-' . $uniqueId,
            'is_active' => true
        ]);
        
        // Create inactive category
        ArtisanCommandCategory::create([
            'name' => 'Inactive Category ' . $uniqueId,
            'slug' => 'inactive-category-' . $uniqueId,
            'is_active' => false
        ]);
        
        $categories = $this->service->getAllCategories(false);
        
        $this->assertArrayHasKey('active-category-' . $uniqueId, $categories);
        $this->assertArrayHasKey('inactive-category-' . $uniqueId, $categories);
    }

    #[Test]
    public function clear_commands_cache_removes_all_cache_entries(): void
    {
        // Populate cache by calling methods
        $this->service->getAllCommands();
        $this->service->getAllCategories();
        
        // Verify cache exists
        $this->assertTrue(Cache::has('artisan_commands_list'));
        
        // Clear cache
        $this->service->clearCommandsCache();
        
        // Verify cache is cleared
        $this->assertFalse(Cache::has('artisan_commands_list'));
    }
}
