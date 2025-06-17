<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ArtisanCommandRequest;
use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $validData = [
            'name' => 'Test Command',
            'command' => 'test:command',
            'description' => 'Test command description',
            'parameters_description' => 'Test parameters description',
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 1,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $invalidData = [];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('command', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_name()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $invalidData = [
            'name' => str_repeat('a', 256),
            'command' => 'test:command',
            'category_id' => $category->id,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_command()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $invalidData = [
            'name' => 'Test Command',
            'command' => str_repeat('a', 256),
            'category_id' => $category->id,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('command', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_command()
    {
        $category = ArtisanCommandCategory::factory()->create();
        ArtisanCommand::factory()->create(['command' => 'existing:command']);

        $invalidData = [
            'name' => 'Test Command',
            'command' => 'existing:command',
            'category_id' => $category->id,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('command', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_command_for_update()
    {
        $category = ArtisanCommandCategory::factory()->create();
        $command = ArtisanCommand::factory()->create(['command' => 'existing:command']);

        $validData = [
            'name' => 'Updated Command',
            'command' => 'existing:command',
            'category_id' => $category->id,
            'id' => $command->id,
        ];

        $request = new ArtisanCommandRequest();
        $request->merge(['id' => $command->id]);
        
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_nonexistent_category_id()
    {
        $invalidData = [
            'name' => 'Test Command',
            'command' => 'test:command',
            'category_id' => 99999, // Non-existent
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $validData = [
            'name' => 'Minimal Command',
            'command' => 'minimal:command',
            'category_id' => $category->id,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $validData = [
            'name' => 'Test Command',
            'command' => 'test:command',
            'category_id' => $category->id,
            'description' => null,
            'parameters_description' => null,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_invalid_boolean_is_active()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $invalidData = [
            'name' => 'Test Command',
            'command' => 'test:command',
            'category_id' => $category->id,
            'is_active' => 'invalid',
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_values_for_is_active()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $validData1 = [
            'name' => 'Test Command 1',
            'command' => 'test:command1',
            'category_id' => $category->id,
            'is_active' => true,
        ];

        $validData2 = [
            'name' => 'Test Command 2',
            'command' => 'test:command2',
            'category_id' => $category->id,
            'is_active' => false,
        ];

        $request = new ArtisanCommandRequest();
        
        $validator1 = Validator::make($validData1, $request->rules());
        $validator2 = Validator::make($validData2, $request->rules());

        $this->assertTrue($validator1->passes());
        $this->assertTrue($validator2->passes());
    }

    #[Test]
    public function validation_fails_with_negative_sort_order()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $invalidData = [
            'name' => 'Test Command',
            'command' => 'test:command',
            'category_id' => $category->id,
            'sort_order' => -1,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('sort_order', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_valid_sort_order()
    {
        $category = ArtisanCommandCategory::factory()->create();

        $validData = [
            'name' => 'Test Command',
            'command' => 'test:command',
            'category_id' => $category->id,
            'sort_order' => 100,
        ];

        $request = new ArtisanCommandRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_when_authenticated()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        $request = new ArtisanCommandRequest();
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new ArtisanCommandRequest();
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new ArtisanCommandRequest();
        $attributes = $request->attributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('command', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('category_id', $attributes);
        $this->assertArrayHasKey('is_active', $attributes);
        $this->assertArrayHasKey('sort_order', $attributes);
    }
}
