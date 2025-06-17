<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ProductRequest;
use App\Models\User;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Supplier;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test for Admin ProductRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class ProductRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $productUser;
    private Tax $tax;
    private Supplier $supplier;
    private ProductCategory $category;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->productUser = User::factory()->create();
        $this->tax = Tax::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->category = ProductCategory::factory()->create();
        
        // Create necessary permissions for testing
        $this->createRequiredPermissions();
        
        // Define test routes
        Route::post('/admin/product', function (ProductRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
        
        Route::put('/admin/product/{id}', function (ProductRequest $request, $id) {
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
        Storage::fake('local');
        $image = UploadedFile::fake()->image('product.jpg', 100, 100)->size(1024);

        $validData = [
            'name' => 'Wireless Headphones',
            'slug' => 'wireless-headphones',
            'user_id' => $this->productUser->id,
            'price' => 99.99,
            'tax_id' => $this->tax->id,
            'supplier_id' => $this->supplier->id,
            'category_id' => $this->category->id,
            'description' => 'High-quality wireless headphones with noise cancellation',
            'is_default' => true,
            'image' => $image,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $minimalData = [
            'name' => 'Minimal Product',
            'user_id' => $this->productUser->id,
            'price' => 10.50,
            // All other fields are nullable
        ];

        $request = new ProductRequest();
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new ProductRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_short(): void
    {
        $invalidData = [
            'name' => 'A', // Too short (min 2 characters)
            'user_id' => $this->productUser->id,
            'price' => 10.00,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
            'user_id' => $this->productUser->id,
            'price' => 10.00,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_slug_too_long(): void
    {
        $invalidData = [
            'name' => 'Valid Product',
            'slug' => str_repeat('a', 256), // Exceeds max length of 255
            'user_id' => $this->productUser->id,
            'price' => 10.00,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug(): void
    {
        $existingProduct = Product::factory()->create(['slug' => 'existing-slug']);
        
        $invalidData = [
            'name' => 'Valid Product',
            'slug' => 'existing-slug', // Already exists
            'user_id' => $this->productUser->id,
            'price' => 10.00,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update(): void
    {
        $existingProduct = Product::factory()->create(['slug' => 'existing-slug']);
        
        $validData = [
            'name' => 'Updated Product',
            'slug' => 'existing-slug', // Same slug for update should be valid
            'user_id' => $this->productUser->id,
            'price' => 15.00,
        ];

        $request = new ProductRequest();
        $request->merge(['id' => $existingProduct->id]);
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_invalid_user_id(): void
    {
        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => 99999, // Non-existent user
            'price' => 10.00,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_negative_price(): void
    {
        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => -10.00, // Negative price
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_non_numeric_price(): void
    {
        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 'not_a_number',
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_tax_id(): void
    {
        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'tax_id' => 99999, // Non-existent tax
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('tax_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_supplier_id(): void
    {
        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'supplier_id' => 99999, // Non-existent supplier
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('supplier_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_category_id(): void
    {
        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'category_id' => 99999, // Non-existent category
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_image_format(): void
    {
        Storage::fake('local');
        $invalidImage = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'image' => $invalidImage, // Not an image
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_oversized_image(): void
    {
        Storage::fake('local');
        $oversizedImage = UploadedFile::fake()->image('large.jpg', 1000, 1000)->size(3000); // 3MB

        $invalidData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'image' => $oversizedImage, // Too large (max 2048KB)
        ];

        $request = new ProductRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('image', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_valid_image(): void
    {
        Storage::fake('local');
        $validImage = UploadedFile::fake()->image('product.jpg', 200, 200)->size(1000); // 1MB

        $validData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'image' => $validImage,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_boolean_is_default(): void
    {
        $validData = [
            'name' => 'Valid Product',
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'is_default' => false,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_nullable_fields(): void
    {
        $validData = [
            'name' => 'Valid Product',
            'slug' => null,
            'user_id' => $this->productUser->id,
            'price' => 10.00,
            'tax_id' => null,
            'supplier_id' => null,
            'category_id' => null,
            'description' => null,
            'image' => null,
        ];

        $request = new ProductRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_zero_price(): void
    {
        $validData = [
            'name' => 'Free Product',
            'user_id' => $this->productUser->id,
            'price' => 0, // Zero price should be valid
        ];

        $request = new ProductRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'backpack')
             ->withoutMiddleware()
             ->postJson('/admin/product', [
                 'name' => 'Test Product',
                 'user_id' => $this->productUser->id,
                 'price' => 25.99,
             ])
             ->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->withoutMiddleware()
             ->postJson('/admin/product', [
                 'name' => 'Test Product',
                 'user_id' => $this->productUser->id,
                 'price' => 25.99,
             ])
             ->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_correct_translations(): void
    {
        $request = new ProductRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('price', $attributes);
        $this->assertArrayHasKey('tax_id', $attributes);
        $this->assertArrayHasKey('supplier_id', $attributes);
        $this->assertArrayHasKey('category_id', $attributes);
        $this->assertArrayHasKey('image', $attributes);
        $this->assertArrayHasKey('is_default', $attributes);
        $this->assertArrayHasKey('is_active', $attributes);
        
        // Check that translations are being called
        $this->assertEquals(trans('admin.products.name'), $attributes['name']);
        $this->assertEquals(trans('admin.products.slug'), $attributes['slug']);
        $this->assertEquals(trans('admin.products.description'), $attributes['description']);
        $this->assertEquals(trans('admin.products.price'), $attributes['price']);
        $this->assertEquals(trans('admin.products.tax'), $attributes['tax_id']);
        $this->assertEquals(trans('admin.products.supplier'), $attributes['supplier_id']);
        $this->assertEquals(trans('admin.products.category'), $attributes['category_id']);
        $this->assertEquals(trans('admin.products.image'), $attributes['image']);
        $this->assertEquals(trans('admin.products.is_default'), $attributes['is_default']);
        $this->assertEquals(trans('admin.products.is_active'), $attributes['is_active']);
    }

    #[Test]
    public function messages_method_returns_correct_translations(): void
    {
        $request = new ProductRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.min', $messages);
        $this->assertArrayHasKey('name.max', $messages);
        $this->assertArrayHasKey('price.required', $messages);
        $this->assertArrayHasKey('price.numeric', $messages);
        $this->assertArrayHasKey('price.min', $messages);
        $this->assertArrayHasKey('user_id.required', $messages);
        $this->assertArrayHasKey('user_id.exists', $messages);
        $this->assertArrayHasKey('tax_id.exists', $messages);
        $this->assertArrayHasKey('supplier_id.exists', $messages);
        $this->assertArrayHasKey('category_id.exists', $messages);
        $this->assertArrayHasKey('image.image', $messages);
        $this->assertArrayHasKey('image.max', $messages);
        
        // Check that translations are being called
        $this->assertEquals(trans('admin.products.validation.name_required'), $messages['name.required']);
        $this->assertEquals(trans('admin.products.validation.name_min'), $messages['name.min']);
        $this->assertEquals(trans('admin.products.validation.name_max'), $messages['name.max']);
        $this->assertEquals(trans('admin.products.validation.price_required'), $messages['price.required']);
        $this->assertEquals(trans('admin.products.validation.price_numeric'), $messages['price.numeric']);
        $this->assertEquals(trans('admin.products.validation.price_min'), $messages['price.min']);
        $this->assertEquals(trans('admin.products.validation.user_required'), $messages['user_id.required']);
        $this->assertEquals(trans('admin.products.validation.user_exists'), $messages['user_id.exists']);
        $this->assertEquals(trans('admin.products.validation.tax_exists'), $messages['tax_id.exists']);
        $this->assertEquals(trans('admin.products.validation.supplier_exists'), $messages['supplier_id.exists']);
        $this->assertEquals(trans('admin.products.validation.category_exists'), $messages['category_id.exists']);
        $this->assertEquals(trans('admin.products.validation.image_format'), $messages['image.image']);
        $this->assertEquals(trans('admin.products.validation.image_size'), $messages['image.max']);
    }
}
