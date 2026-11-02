<?php

namespace Tests\Feature\Models;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandCategoryFeatureTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function can_create_artisan_command_category_with_factory(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create();

        $this->assertDatabaseHas(
            'artisan_command_categories',
            [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        );
    }

    #[Test]
    public function fillable_attributes_can_be_mass_assigned(): void
    {
        $data = [
            'name' => 'Cache Commands',
            'slug' => 'cache-commands',
            'description' => 'Commands related to cache management',
            'is_active' => true,
        ];

        $category = ArtisanCommandCategory::create($data);

        $this->assertDatabaseHas('artisan_command_categories', $data);
        $this->assertEquals($data['name'], $category->name);
        $this->assertEquals($data['slug'], $category->slug);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create([
            'is_active' => 1,
        ]);

        $this->assertIsBool($category->is_active);
        $this->assertInstanceOf(Carbon::class, $category->created_at);
    }

    #[Test]
    public function has_many_commands_relationship(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create();

        // Create some commands in this category
        $artisanCommandFactory = ArtisanCommand::factory();
        $artisanCommandFactory = $artisanCommandFactory->count(3);
        $commands = $artisanCommandFactory->create([
            'category_id' => $category->id,
        ]);

        $this->assertCount(3, $category->commands);
        foreach ($category->commands as $command) {
            $this->assertInstanceOf(ArtisanCommand::class, $command);
            $this->assertEquals($category->id, $command->category_id);
        }
    }

    #[Test]
    public function can_create_category_without_optional_fields(): void
    {
        $data = [
            'name' => 'Basic Category',
            'slug' => 'basic-category',
        ];

        $category = ArtisanCommandCategory::create($data);

        $this->assertDatabaseHas('artisan_command_categories', $data);
        $this->assertNull($category->description);
    }

    #[Test]
    public function factory_creates_unique_slugs(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $artisanCommandCategoryFactory = $artisanCommandCategoryFactory->count(5);
        $categories = $artisanCommandCategoryFactory->create();

        $slugs = $categories->pluck('slug')->toArray();
        $uniqueSlugs = array_unique($slugs);

        $this->assertEquals(
            count($slugs),
            count($uniqueSlugs),
        );
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $activeCategory = ArtisanCommandCategory::factory()->active()->create();
        $inactiveCategory = ArtisanCommandCategory::factory()->inactive()->create();

        $this->assertTrue($activeCategory->is_active);
        $this->assertFalse($inactiveCategory->is_active);
    }

    #[Test]
    public function can_update_category(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create();

        $newData = [
            'name' => 'Updated Category',
            'is_active' => false,
            'description' => 'Updated description',
        ];

        $category->update($newData);

        $this->assertDatabaseHas(
            'artisan_command_categories',
            array_merge(
                ['id' => $category->id],
                $newData,
            ),
        );
    }

    #[Test]
    public function can_delete_category(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create();
        $categoryId = $category->id;

        $category->delete();

        $this->assertDatabaseMissing('artisan_command_categories', ['id' => $categoryId]);
    }

    #[Test]
    public function boolean_cast_works_with_is_active(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $activeCategory = $artisanCommandCategoryFactory->create(['is_active' => 1]);
        $inactiveCategory = $artisanCommandCategoryFactory->create(['is_active' => 0]);

        $this->assertTrue($activeCategory->is_active);
        $this->assertFalse($inactiveCategory->is_active);
        $this->assertIsBool($activeCategory->is_active);
        $this->assertIsBool($inactiveCategory->is_active);
    }

    #[Test]
    public function can_have_multiple_commands_in_category(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create(['name' => 'Database']);

        $artisanCommandFactory = ArtisanCommand::factory();
        $command1 = $artisanCommandFactory->create([
            'category_id' => $category->id,
            'name' => 'Migrate',
        ]);

        $command2 = $artisanCommandFactory->create([
            'category_id' => $category->id,
            'name' => 'Seed',
        ]);

        $this->assertCount(2, $category->commands);
        $this->assertTrue(
            $category->commands->contains($command1),
        );
        $this->assertTrue(
            $category->commands->contains($command2),
        );
    }

    #[Test]
    public function factory_with_name_state_works(): void
    {
        $categoryName = 'Custom Category Name';
        $category = ArtisanCommandCategory::factory()
            ->withName($categoryName)
            ->create();

        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals('custom-category-name', $category->slug);
    }

    #[Test]
    public function relationship_eager_loading_works(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $artisanCommandCategoryFactory = $artisanCommandCategoryFactory->count(2);
        $categories = $artisanCommandCategoryFactory->create();

        // Create commands for each category
        foreach ($categories as $category) {
            ArtisanCommand::factory()->count(2)->create(['category_id' => $category->id]);
        }

        $artisanCommandCategoryQuery = ArtisanCommandCategory::with('commands');
        $artisanCommandCategoryQuery->whereIn(
            'id',
            $categories->pluck('id'),
        );
        $categoriesWithCommands = $artisanCommandCategoryQuery->get();

        $this->assertCount(2, $categoriesWithCommands);

        foreach ($categoriesWithCommands as $category) {
            $this->assertCount(2, $category->commands);
            foreach ($category->commands as $command) {
                $this->assertInstanceOf(ArtisanCommand::class, $command);
            }
        }
    }

    #[Test]
    public function can_create_category_without_description(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create(['description' => null]);

        $this->assertNull($category->description);
    }

    #[Test]
    public function slug_field_is_fillable_and_updatable(): void
    {
        $artisanCommandCategoryFactory = ArtisanCommandCategory::factory();
        $category = $artisanCommandCategoryFactory->create();
        $newSlug = 'new-custom-slug';

        $category->update(['slug' => $newSlug]);

        $this->assertEquals($newSlug, $category->slug);
        $this->assertDatabaseHas('artisan_command_categories', [
            'id' => $category->id,
            'slug' => $newSlug,
        ]);
    }
}
