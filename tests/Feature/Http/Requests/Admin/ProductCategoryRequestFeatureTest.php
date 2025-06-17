<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ProductCategoryRequest;
use App\Models\User;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test for ProductCategoryRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class ProductCategoryRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProductCategory $productCategory;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->productCategory = ProductCategory::factory()->create();
        
        // Create necessary permissions for testing
        $this->createRequiredPermissions();
        
        // Define test routes
        Route::post('/admin/product-category', function (ProductCategoryRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
        
        Route::put('/admin/product-category/{id}', function (ProductCategoryRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    /**
     * Create required permissions for testing.
     */
    private function createRequiredPermissions(): void
    {
        // Define all permissions required for admin operations and navigation
        $permissions = [
            // User management permissions
            'can_create_edit_user',
            
            // Business operations permissions
            'can_create_edit_invoice',
            'can_create_edit_client',
            'can_create_edit_supplier',
            
            // Financial management permissions
            'can_create_edit_expense',
            'can_create_edit_tax',
            'can_create_edit_bank',
            'can_create_edit_payment_method',
            
            // Inventory management permissions
            'can_create_edit_product',
            
            // System administration permissions
            'can_create_edit_command',
            'can_create_edit_cron_task',
            'can_create_edit_status',
            'can_configure_system',
            
            // Basic backpack access
            'backpack.access',
        ];

        // Create all permissions for backpack guard
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission, 
                'guard_name' => 'backpack'
            ]);
        }

        // Give the user all necessary permissions for the backpack guard
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'backpack')
                ->first();
            if ($permission) {
                $this->user->givePermissionTo($permission);
            }
        }
    }

    #[Test]
    public function validation_passes_with_complete_valid_data(): void
    {
        $validData = [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and accessories',
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $minimalData = [
            'name' => 'Minimal Category',
            // slug and description are nullable
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new ProductCategoryRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_short(): void
    {
        $invalidData = [
            'name' => 'A', // Too short (min 2 characters)
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_slug_too_long(): void
    {
        $invalidData = [
            'name' => 'Valid Name',
            'slug' => str_repeat('a', 256), // Exceeds max length of 255
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug(): void
    {
        $existingCategory = ProductCategory::factory()->create(['slug' => 'existing-slug']);
        
        $invalidData = [
            'name' => 'Valid Name',
            'slug' => 'existing-slug', // Already exists
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update(): void
    {
        $existingCategory = ProductCategory::factory()->create(['slug' => 'existing-slug']);
        
        $validData = [
            'name' => 'Updated Name',
            'slug' => 'existing-slug', // Same slug for update should be valid
        ];

        $request = new ProductCategoryRequest();
        $request->merge(['id' => $existingCategory->id]);
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_nullable_slug(): void
    {
        $validData = [
            'name' => 'Valid Name',
            'slug' => null,
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_nullable_description(): void
    {
        $validData = [
            'name' => 'Valid Name',
            'description' => null,
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_edge_case_lengths(): void
    {
        // Test minimum valid name length
        $validData = [
            'name' => 'AB', // Exactly 2 characters (minimum)
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());

        // Test maximum valid name length
        $validData = [
            'name' => str_repeat('a', 255), // Exactly 255 characters (maximum)
        ];

        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        // Test maximum valid slug length
        $validData = [
            'name' => 'Valid Name',
            'slug' => str_repeat('b', 255), // Exactly 255 characters (maximum)
        ];

        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_allows_string_description(): void
    {
        $validData = [
            'name' => 'Valid Name',
            'description' => 'This is a valid string description for the product category',
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'backpack')
             ->withoutMiddleware()
             ->postJson('/admin/product-category', [
                 'name' => 'Test Category',
             ])
             ->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->withoutMiddleware()
             ->postJson('/admin/product-category', [
                 'name' => 'Test Category',
             ])
             ->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_correct_translations(): void
    {
        $request = new ProductCategoryRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        
        // Check that translations are being called
        $this->assertEquals(trans('admin.name'), $attributes['name']);
        $this->assertEquals(trans('admin.slug'), $attributes['slug']);
        $this->assertEquals(trans('admin.description'), $attributes['description']);
    }

    #[Test]
    public function validation_handles_empty_slug(): void
    {
        $validData = [
            'name' => 'Valid Name',
            'slug' => '', // Empty string should be treated as null
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_handles_whitespace_in_name(): void
    {
        $validData = [
            'name' => '  Valid Name  ', // Name with whitespace
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_handles_special_characters_in_slug(): void
    {
        $validData = [
            'name' => 'Valid Name',
            'slug' => 'valid-slug_123', // Slug with special characters
        ];

        $request = new ProductCategoryRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }
}
