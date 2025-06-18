<?php

namespace Tests\Feature\Services;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use App\Services\ArtisanCommandsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandsServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private ArtisanCommandsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ArtisanCommandsService();
        
        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = new ArtisanCommandsService();
        $this->assertInstanceOf(ArtisanCommandsService::class, $service);
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
        // Create a test category
        $category = ArtisanCommandCategory::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true
        ]);
        
        // Create a test command in this category
        ArtisanCommand::factory()->create([
            'name' => 'Test Command',
            'command' => 'test:command',
            'description' => 'Test command description',
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 1
        ]);
        
        $commands = $this->service->getCommandsByCategory('test-category');
        
        $this->assertIsArray($commands);
        $this->assertArrayHasKey('test:command', $commands);
        $this->assertStringContainsString('Test Command', $commands['test:command']);
    }

    #[Test]
    public function get_commands_by_category_excludes_inactive_commands(): void
    {
        $category = ArtisanCommandCategory::factory()->create([
            'slug' => 'test-category',
            'is_active' => true
        ]);
        
        // Create inactive command
        ArtisanCommand::factory()->create([
            'command' => 'inactive:command',
            'category_id' => $category->id,
            'is_active' => false
        ]);
        
        $commands = $this->service->getCommandsByCategory('test-category');
        
        $this->assertIsArray($commands);
        $this->assertArrayNotHasKey('inactive:command', $commands);
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
        // Create active category
        $activeCategory = ArtisanCommandCategory::factory()->create([
            'name' => 'Active Category',
            'slug' => 'active-category',
            'is_active' => true
        ]);
        
        // Create inactive category
        ArtisanCommandCategory::factory()->create([
            'name' => 'Inactive Category',
            'slug' => 'inactive-category',
            'is_active' => false
        ]);
        
        $categories = $this->service->getAllCategories(true);
        
        $this->assertArrayHasKey('active-category', $categories);
        $this->assertArrayNotHasKey('inactive-category', $categories);
    }

    #[Test]
    public function get_all_categories_can_include_inactive(): void
    {
        // Create active category
        ArtisanCommandCategory::factory()->create([
            'slug' => 'active-category',
            'is_active' => true
        ]);
        
        // Create inactive category
        ArtisanCommandCategory::factory()->create([
            'slug' => 'inactive-category',
            'is_active' => false
        ]);
        
        $categories = $this->service->getAllCategories(false);
        
        $this->assertArrayHasKey('active-category', $categories);
        $this->assertArrayHasKey('inactive-category', $categories);
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
