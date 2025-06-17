<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ArtisanCommandCategoryRequest;
use App\Models\ArtisanCommandCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandCategoryRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $validData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test category description',
            'is_active' => true,
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $invalidData = [];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_name()
    {
        $invalidData = [
            'name' => str_repeat('a', 256),
            'slug' => 'test-category',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_slug()
    {
        $invalidData = [
            'name' => 'Test Category',
            'slug' => str_repeat('a', 256),
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug()
    {
        ArtisanCommandCategory::factory()->create(['slug' => 'existing-slug']);

        $invalidData = [
            'name' => 'Test Category',
            'slug' => 'existing-slug',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update()
    {
        $category = ArtisanCommandCategory::factory()->create(['slug' => 'existing-slug']);

        $validData = [
            'name' => 'Updated Category',
            'slug' => 'existing-slug',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $request->merge(['id' => $category->id]);
        
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $validData = [
            'name' => 'Minimal Category',
            'slug' => 'minimal-category',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $validData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => null,
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_invalid_boolean_is_active()
    {
        $invalidData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => 'invalid',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_values_for_is_active()
    {
        $validData1 = [
            'name' => 'Test Category 1',
            'slug' => 'test-category-1',
            'is_active' => true,
        ];

        $validData2 = [
            'name' => 'Test Category 2',
            'slug' => 'test-category-2',
            'is_active' => false,
        ];

        $request = new ArtisanCommandCategoryRequest();
        
        $validator1 = Validator::make($validData1, $request->rules());
        $validator2 = Validator::make($validData2, $request->rules());

        $this->assertTrue($validator1->passes());
        $this->assertTrue($validator2->passes());
    }

    #[Test]
    public function authorization_passes_when_authenticated()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        $request = new ArtisanCommandCategoryRequest();
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new ArtisanCommandCategoryRequest();
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new ArtisanCommandCategoryRequest();
        $attributes = $request->attributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('is_active', $attributes);
    }

    #[Test]
    public function validation_passes_without_optional_fields()
    {
        $validData = [
            'name' => 'Simple Category',
            'slug' => 'simple-category',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_different_string_types()
    {
        $validData = [
            'name' => 'Category with Numbers 123',
            'slug' => 'category-with-numbers-123',
            'description' => 'Description with special chars !@#$%',
        ];

        $request = new ArtisanCommandCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }
}
