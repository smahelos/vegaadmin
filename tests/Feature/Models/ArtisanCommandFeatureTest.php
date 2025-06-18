<?php

namespace Tests\Feature\Models;

use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function can_create_artisan_command_with_factory(): void
    {
        $command = ArtisanCommand::factory()->create();

        $this->assertDatabaseHas('artisan_commands', [
            'id' => $command->id,
            'name' => $command->name,
            'command' => $command->command,
        ]);
    }

    #[Test]
    public function fillable_attributes_can_be_mass_assigned(): void
    {
        $category = ArtisanCommandCategory::factory()->create();
        
        $data = [
            'name' => 'Test Command',
            'description' => 'Test command description',
            'category_id' => $category->id,
            'is_active' => true,
            'command' => 'php artisan test:command',
            'parameters_description' => 'Parameters description',
            'sort_order' => 10,
        ];

        $command = ArtisanCommand::create($data);

        $this->assertDatabaseHas('artisan_commands', $data);
        $this->assertEquals($data['name'], $command->name);
        $this->assertEquals($data['command'], $command->command);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $command = ArtisanCommand::factory()->create([
            'is_active' => 1,
            'sort_order' => '20',
        ]);

        $this->assertIsBool($command->is_active);
        $this->assertIsInt($command->sort_order);
        $this->assertInstanceOf(\Carbon\Carbon::class, $command->created_at);
    }

    #[Test]
    public function belongs_to_category_relationship(): void
    {
        $category = ArtisanCommandCategory::factory()->create();
        $command = ArtisanCommand::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(ArtisanCommandCategory::class, $command->category);
        $this->assertEquals($category->id, $command->category->id);
        $this->assertEquals($category->name, $command->category->name);
    }

    #[Test]
    public function can_create_command_without_optional_fields(): void
    {
        $category = ArtisanCommandCategory::factory()->create();
        
        $data = [
            'name' => 'Minimal Command',
            'category_id' => $category->id,
            'command' => 'php artisan minimal:command',
        ];

        $command = ArtisanCommand::create($data);

        $this->assertDatabaseHas('artisan_commands', $data);
        $this->assertNull($command->description);
        $this->assertNull($command->parameters_description);
    }

    #[Test]
    public function factory_creates_valid_command_format(): void
    {
        $command = ArtisanCommand::factory()->create();

        $this->assertStringStartsWith('php artisan ', $command->command);
        $this->assertNotEmpty($command->name);
        $this->assertInstanceOf(ArtisanCommandCategory::class, $command->category);
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $activeCommand = ArtisanCommand::factory()->active()->create();
        $inactiveCommand = ArtisanCommand::factory()->inactive()->create();

        $this->assertTrue($activeCommand->is_active);
        $this->assertFalse($inactiveCommand->is_active);
    }

    #[Test]
    public function can_update_command(): void
    {
        $command = ArtisanCommand::factory()->create();
        
        $newData = [
            'name' => 'Updated Command',
            'is_active' => false,
            'sort_order' => 100,
        ];
        
        $command->update($newData);
        
        $this->assertDatabaseHas('artisan_commands', array_merge(
            ['id' => $command->id],
            $newData
        ));
    }

    #[Test]
    public function can_delete_command(): void
    {
        $command = ArtisanCommand::factory()->create();
        $commandId = $command->id;
        
        $command->delete();
        
        $this->assertDatabaseMissing('artisan_commands', ['id' => $commandId]);
    }

    #[Test]
    public function boolean_cast_works_with_is_active(): void
    {
        $activeCommand = ArtisanCommand::factory()->create(['is_active' => 1]);
        $inactiveCommand = ArtisanCommand::factory()->create(['is_active' => 0]);
        
        $this->assertTrue($activeCommand->is_active);
        $this->assertFalse($inactiveCommand->is_active);
        $this->assertIsBool($activeCommand->is_active);
        $this->assertIsBool($inactiveCommand->is_active);
    }

    #[Test]
    public function can_create_multiple_commands_with_different_categories(): void
    {
        $category1 = ArtisanCommandCategory::factory()->create(['name' => 'Cache']);
        $category2 = ArtisanCommandCategory::factory()->create(['name' => 'Database']);
        
        $command1 = ArtisanCommand::factory()->create(['category_id' => $category1->id]);
        $command2 = ArtisanCommand::factory()->create(['category_id' => $category2->id]);
        
        $this->assertEquals('Cache', $command1->category->name);
        $this->assertEquals('Database', $command2->category->name);
    }

    #[Test]
    public function sort_order_has_default_value_when_not_specified(): void
    {
        $command = ArtisanCommand::factory()->create(['sort_order' => 0]);
        
        $this->assertEquals(0, $command->sort_order);
        $this->assertIsInt($command->sort_order);
    }

    #[Test]
    public function can_have_commands_with_parameters_description(): void
    {
        $parametersDesc = 'Use --force to override existing files';
        $command = ArtisanCommand::factory()->create([
            'parameters_description' => $parametersDesc
        ]);
        
        $this->assertEquals($parametersDesc, $command->parameters_description);
    }

    #[Test]
    public function relationship_eager_loading_works(): void
    {
        $commands = ArtisanCommand::factory()->count(3)->create();
        
        $commandsWithCategory = ArtisanCommand::with('category')->get();
        
        $this->assertCount(3, $commandsWithCategory);
        foreach ($commandsWithCategory as $command) {
            $this->assertNotNull($command->category);
            $this->assertInstanceOf(ArtisanCommandCategory::class, $command->category);
        }
    }
}
