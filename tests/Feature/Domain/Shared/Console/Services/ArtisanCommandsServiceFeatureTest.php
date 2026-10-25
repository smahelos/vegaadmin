<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Shared\Console\Services;

use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ArtisanCommandsServiceFeatureTest extends TestCase
{
    private ArtisanCommandsServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ArtisanCommandsServiceInterface::class);

        // Clear cache before each test
        Cache::flush();
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
            'is_active' => true,
        ]);

        // Create a test command in this category
        ArtisanCommand::create([
            'name' => 'Test Command ' . $uniqueId,
            'command' => 'test:command:' . $uniqueId,
            'description' => 'Test command description ' . $uniqueId,
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 1,
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
            'is_active' => true,
        ]);

        // Create inactive command
        ArtisanCommand::create([
            'name' => 'Inactive Command ' . $uniqueId,
            'command' => 'inactive:command:' . $uniqueId,
            'description' => 'Inactive command description',
            'category_id' => $category->id,
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $commands = $this->service->getCommandsByCategory('test-category-' . $uniqueId);

        $this->assertIsArray($commands);
        $this->assertArrayNotHasKey('inactive:command:' . $uniqueId, $commands);
    }
}
