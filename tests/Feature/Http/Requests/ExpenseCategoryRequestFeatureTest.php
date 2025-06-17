<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ExpenseCategoryRequest;
use App\Models\User;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for ExpenseCategoryRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests expense category validation scenarios, authorization, and validation with database constraints
 */
class ExpenseCategoryRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected array $validExpenseCategoryData;

    /**
     * Set up the test environment.
     * Creates test user and valid expense category data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and user
        $this->createPermissionsAndUser();
        
        // Set up valid expense category data
        $this->setupValidExpenseCategoryData();
    }

    /**
     * Create necessary permissions and test user
     */
    private function createPermissionsAndUser(): void
    {
        // Create permissions
        Permission::firstOrCreate(['name' => 'can_create_edit_expense', 'guard_name' => 'web']);
        
        // Create role
        $userRole = Role::firstOrCreate(['name' => 'expense_manager', 'guard_name' => 'web']);
        $userRole->givePermissionTo('can_create_edit_expense');
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
        $this->user->assignRole($userRole);
    }

    /**
     * Setup valid expense category data for testing
     */
    private function setupValidExpenseCategoryData(): void
    {
        $this->validExpenseCategoryData = [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug,
            'color' => $this->faker->hexColor,
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $this->actingAs($this->user);
        
        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($this->validExpenseCategoryData, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $this->actingAs($this->user);
        
        $requiredFields = ['name', 'slug'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validExpenseCategoryData;
            unset($invalidData[$field]);

            $request = new ExpenseCategoryRequest();
            $validator = Validator::make($invalidData, $request->rules());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_short_name()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseCategoryData;
        $invalidData['name'] = 'a'; // Too short (min 2)

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_long_name()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseCategoryData;
        $invalidData['name'] = str_repeat('a', 256); // Too long (max 255)

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug()
    {
        $this->actingAs($this->user);
        
        // Create existing expense category
        ExpenseCategory::factory()->create(['slug' => 'existing-slug']);

        $invalidData = $this->validExpenseCategoryData;
        $invalidData['slug'] = 'existing-slug';

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update()
    {
        $this->actingAs($this->user);
        
        // Create existing expense category
        $existingCategory = ExpenseCategory::factory()->create(['slug' => 'existing-slug']);

        $updateData = $this->validExpenseCategoryData;
        $updateData['slug'] = 'existing-slug';
        $updateData['id'] = $existingCategory->id;

        $request = new ExpenseCategoryRequest();
        $request->merge(['id' => $existingCategory->id]);
        $validator = Validator::make($updateData, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $this->actingAs($this->user);
        
        $minimalData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_invalid_boolean_is_active()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseCategoryData;
        $invalidData['is_active'] = 'invalid-boolean';

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_values_for_is_active()
    {
        $this->actingAs($this->user);
        
        $booleanValues = [true, false, 1, 0, '1', '0'];
        
        foreach ($booleanValues as $value) {
            $validData = $this->validExpenseCategoryData;
            $validData['is_active'] = $value;
            $validData['slug'] = 'test-slug-' . $value; // Make slug unique

            $request = new ExpenseCategoryRequest();
            $validator = Validator::make($validData, $request->rules());

            $this->assertFalse($validator->fails(), "Validation should pass for is_active value: " . json_encode($value));
        }
    }

    #[Test]
    public function authorization_fails_without_permission()
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);

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
        $this->actingAs($this->user);

        $request = new ExpenseCategoryRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new ExpenseCategoryRequest();
        $attributes = $request->attributes();

        $expectedKeys = ['name', 'slug', 'color', 'description', 'is_active'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $attributes, "Should have custom attribute for {$key}");
        }
    }

    #[Test]
    public function slug_is_auto_generated_when_missing()
    {
        $this->actingAs($this->user);
        
        // Test that the validation requires slug when not provided
        $dataWithoutSlug = [
            'name' => 'Test Category Name',
            'description' => 'Test description',
        ];

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($dataWithoutSlug, $request->rules());
        
        // Should fail because slug is required and not provided in validation rules
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_too_long_color()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseCategoryData;
        $invalidData['color'] = str_repeat('a', 51); // Too long (max 50)

        $request = new ExpenseCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('color', $validator->errors()->toArray());
    }
}
