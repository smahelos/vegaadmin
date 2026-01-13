<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ProductRequest;
use App\Models\User;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Supplier;
use App\Models\ProductCategory;
use App\Models\EntityLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;

/**
 * Feature test for Admin ProductRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class ProductRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    protected User $adminUser;
    protected User $regularUser;
    private User $productUser;
    private UniversalLimitService $limitService;
    private Tax $tax;
    private Supplier $supplier;
    private ProductCategory $category;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();
        $permission = Permission::where('name', 'can_create_edit_product')
            ->where('guard_name', 'backpack')
            ->first();
        $this->regularUser->givePermissionTo($permission);

        $this->productUser = User::factory()->create();
        $this->tax = Tax::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->category = ProductCategory::factory()->create();

    // Initialize limit service
    $this->limitService = app(UniversalLimitService::class);

    // Define test routes
        Route::post('/test-product', function (ProductRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');

        Route::put('/test-product/{id}', function (ProductRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
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
        $request->setMethod('PUT');
        $routeMock = new class($existingProduct) {
            public function __construct(private $product) {}
            public function parameter($name) { return $this->product->id; }
        };
        $request->setRouteResolver(fn() => $routeMock);
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
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_product',
            'entity_type' => 'product',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'backpack')
             ->postJson('/test-product', [
                 'name' => 'Test Product',
                 'user_id' => $this->productUser->id,
                 'price' => 25.99,
             ])
             ->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->postJson('/test-product', [
                 'name' => 'Test Product',
                 'user_id' => $this->productUser->id,
                 'price' => 25.99,
             ])
             ->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_for_authenticated_user_without_permission(): void
    {
        $userNoPerm = User::factory()->create();
        $this->actingAs($userNoPerm, 'backpack');

        $this->postJson('/test-product', [
            'name' => 'No Perm Product',
            'user_id' => $this->productUser->id,
            'price' => 9.99,
        ])->assertStatus(403);
    }

    #[Test]
    public function product_creation_respects_global_entity_limits(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_product',
            'entity_type' => 'product',
            'limit_value' => 1,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        // First creation passes
        $this->postJson('/test-product', [
            'name' => 'Product One',
            'user_id' => $this->productUser->id,
            'price' => 10,
        ])->assertStatus(200);

        // Record usage manually (authorize only checks)
        $this->limitService->recordUsage($this->adminUser->id, 'product', 'count', 'monthly', 'backpack');

        // Second creation exceeds limit via direct authorize()
        $this->expectException(\App\Domain\User\Exceptions\EntityLimitExceededException::class);
        $request = new class extends ProductRequest { public function rules(): array { return []; } };
        $request->replace([
            'name' => 'Product Two',
            'user_id' => $this->productUser->id,
            'price' => 20,
        ]);
        $request->setRouteResolver(fn()=> (object)['parameter'=>fn($n)=> null]);
        $request->setMethod('POST');
        $request->authorize();
    }

    #[Test]
    public function product_update_bypasses_limit_checks(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_product',
            'entity_type' => 'product',
            'limit_value' => 0,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $this->limitService->recordUsage($this->adminUser->id, 'product', 'count', 'monthly', 'backpack');

        $request = new class extends ProductRequest { public function rules(): array { return []; } };
        $request->replace([
            'name' => 'Updated Product',
            'user_id' => $this->productUser->id,
            'price' => 30,
        ]);
        $request->setRouteResolver(fn()=> (object)['parameter'=>fn($n)=> 'existing-product-id']);
        $request->setMethod('PUT');
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function auto_generates_slug_when_missing(): void
    {
        $request = new ProductRequest();
        $request->replace([
            'name' => 'My Cool Product',
            'user_id' => $this->productUser->id,
            'price' => 12.5,
        ]);
        $request->prepareForValidation();
        $this->assertEquals('my-cool-product', $request->get('slug'));
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

        // Basic sanity: values are non-empty strings
        foreach ($attributes as $val) {
            $this->assertIsString($val);
            $this->assertNotSame('', $val);
        }
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

        // Basic sanity: all message values are strings
        foreach ($messages as $val) {
            $this->assertIsString($val);
        }
    }
}
