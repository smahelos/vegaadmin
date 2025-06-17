<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseCategoryRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permission for expense management
        Permission::firstOrCreate(['name' => 'can_create_edit_expense', 'guard_name' => 'backpack']);
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $validData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'color' => '#FF0000',
            'description' => 'Test description',
            'is_active' => true,
        ];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $invalidData = [];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_short_name()
    {
        $invalidData = [
            'name' => 'A',
            'slug' => 'test-category',
        ];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_name()
    {
        $invalidData = [
            'name' => str_repeat('a', 256),
            'slug' => 'test-category',
        ];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug()
    {
        ExpenseCategory::factory()->create(['slug' => 'existing-slug']);

        $invalidData = [
            'name' => 'Test Category',
            'slug' => 'existing-slug',
        ];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update()
    {
        $category = ExpenseCategory::factory()->create(['slug' => 'existing-slug']);

        $validData = [
            'name' => 'Updated Category',
            'slug' => 'existing-slug',
        ];

        $request = new ExpenseCategoryRequest();
        // Mock the ID retrieval
        $request->merge(['id' => $category->id]);
        $validator = Validator::make($validData + ['id' => $category->id], $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $validData = [
            'name' => 'Minimal Category',
            'slug' => 'minimal-category',
        ];

        $request = new ExpenseCategoryRequest();
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

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_values_for_is_active()
    {
        $validData1 = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ];

        $validData2 = [
            'name' => 'Test Category 2',
            'slug' => 'test-category-2',
            'is_active' => false,
        ];

        $request = new ExpenseCategoryRequest();
        
        $validator1 = Validator::make($validData1, $request->rules());
        $validator2 = Validator::make($validData2, $request->rules());

        $this->assertTrue($validator1->passes());
        $this->assertTrue($validator2->passes());
    }

    #[Test]
    public function authorization_fails_without_permission()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        $request = new ExpenseCategoryRequest();
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new ExpenseCategoryRequest();
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function authorization_passes_with_correct_permission()
    {
        $user = User::factory()->create();
        $permission = Permission::where('name', 'can_create_edit_expense')->first();
        $user->givePermissionTo($permission);
        
        $this->actingAs($user, 'backpack');

        $request = new ExpenseCategoryRequest();
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new ExpenseCategoryRequest();
        $attributes = $request->attributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('color', $attributes);
        $this->assertArrayHasKey('description', $attributes);
    }

    #[Test]
    public function validation_fails_with_too_long_color()
    {
        $invalidData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'color' => str_repeat('a', 51),
        ];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
    }
}
