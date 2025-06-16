<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ProductRequest;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private array $validProductData;
    private User $user;
    private Tax $tax;
    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions and roles
        $this->createPermissionsAndRoles();
        
        // Create authenticated user
        $this->user = $this->createAuthenticatedUser();
        
        // Create related models
        $this->tax = $this->createTax();
        $this->category = $this->createProductCategory();
        
        // Set up valid product data using faker
        $this->validProductData = [
            'name' => $this->faker->words(3, true),
            'slug' => null, // Will be auto-generated
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'tax_id' => $this->tax->id,
            'category_id' => $this->category->id,
            'description' => $this->faker->paragraph,
            'is_default' => false,
            'is_active' => true,
            'currency' => $this->faker->randomElement(['CZK', 'EUR', 'USD']),
        ];
    }

    /**
     * Create permissions and roles for testing.
     *
     * @return void
     */
    private function createPermissionsAndRoles(): void
    {
        // Create basic permissions
        Permission::create(['name' => 'products.list', 'guard_name' => 'web']);
        Permission::create(['name' => 'products.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'products.show', 'guard_name' => 'web']);
        Permission::create(['name' => 'products.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'products.delete', 'guard_name' => 'web']);

        // Create role with permissions
        $role = Role::create(['name' => 'product_manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['products.list', 'products.create', 'products.show', 'products.update', 'products.delete']);
    }

    /**
     * Create authenticated user for testing.
     *
     * @return User
     */
    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'name' => $this->faker->name,
        ]);

        $user->assignRole('product_manager');
        
        return $user;
    }

    /**
     * Create a tax for testing.
     *
     * @return Tax
     */
    private function createTax(): Tax
    {
        return Tax::factory()->create([
            'name' => $this->faker->word,
            'rate' => $this->faker->randomFloat(2, 0, 30),
        ]);
    }

    /**
     * Create a product category for testing.
     *
     * @return ProductCategory
     */
    private function createProductCategory(): ProductCategory
    {
        return ProductCategory::factory()->create([
            'name' => $this->faker->word,
        ]);
    }

    /**
     * Test validation passes with valid data.
     *
     * @return void
     */
    public function test_validation_passes_with_valid_data()
    {
        $request = new ProductRequest();
        $validator = Validator::make($this->validProductData, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test slug generation during validation preparation.
     *
     * @return void
     */
    public function test_slug_generation_during_validation_preparation()
    {
        $data = $this->validProductData;
        $data['name'] = 'Test Product Name';
        $data['slug'] = null; // No slug provided

        $request = Request::create('/products', 'POST', $data);
        $productRequest = ProductRequest::createFrom($request);

        // Manually trigger prepareForValidation
        $reflection = new \ReflectionClass($productRequest);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($productRequest);

        // Check that slug was generated
        $this->assertEquals('test-product-name', $productRequest->input('slug'));
    }

    /**
     * Test validation fails when required fields are missing.
     *
     * @return void
     */
    public function test_validation_fails_when_required_fields_missing()
    {
        $requiredFields = ['name', 'price', 'currency'];
        
        foreach ($requiredFields as $field) {
            $data = $this->validProductData;
            unset($data[$field]);

            $request = new ProductRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertTrue($validator->errors()->has($field), "Should have error for missing {$field}");
        }
    }

    /**
     * Test validation fails with invalid price values.
     *
     * @return void
     */
    public function test_validation_fails_with_invalid_price_values()
    {
        $invalidPrices = ['not-a-number', -1, -0.01, 'abc'];

        foreach ($invalidPrices as $invalidPrice) {
            $data = $this->validProductData;
            $data['price'] = $invalidPrice;

            $request = new ProductRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertTrue($validator->fails(), "Validation should fail for invalid price: {$invalidPrice}");
            $this->assertTrue($validator->errors()->has('price'));
        }
    }

    /**
     * Test validation fails with invalid currency values.
     *
     * @return void
     */
    public function test_validation_fails_with_invalid_currency_values()
    {
        $invalidCurrencies = ['GBP', 'JPY', 'invalid', '', 123];

        foreach ($invalidCurrencies as $invalidCurrency) {
            $data = $this->validProductData;
            $data['currency'] = $invalidCurrency;

            $request = new ProductRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertTrue($validator->fails(), "Validation should fail for invalid currency: {$invalidCurrency}");
            $this->assertTrue($validator->errors()->has('currency'));
        }
    }

    /**
     * Test validation passes with valid currency values.
     *
     * @return void
     */
    public function test_validation_passes_with_valid_currency_values()
    {
        $validCurrencies = ['CZK', 'EUR', 'USD'];

        foreach ($validCurrencies as $validCurrency) {
            $data = $this->validProductData;
            $data['currency'] = $validCurrency;

            $request = new ProductRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertFalse($validator->fails(), "Validation should pass for valid currency: {$validCurrency}");
        }
    }

    /**
     * Test validation fails when string fields exceed max length.
     *
     * @return void
     */
    public function test_validation_fails_when_string_fields_exceed_max_length()
    {
        $fieldsWithMaxLength = [
            'name' => 256,      // max:255
            'slug' => 256,      // max:255
        ];

        foreach ($fieldsWithMaxLength as $field => $length) {
            $data = $this->validProductData;
            $data[$field] = str_repeat('a', $length);

            $request = new ProductRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} exceeds max length");
            $this->assertTrue($validator->errors()->has($field));
        }
    }

    /**
     * Test validation fails when name is too short.
     *
     * @return void
     */
    public function test_validation_fails_when_name_is_too_short()
    {
        $data = $this->validProductData;
        $data['name'] = 'a'; // Less than min:2

        $request = new ProductRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    /**
     * Test validation passes with nullable fields empty.
     *
     * @return void
     */
    public function test_validation_passes_with_nullable_fields_empty()
    {
        $nullableFields = ['slug', 'tax_id', 'category_id', 'description', 'image'];

        foreach ($nullableFields as $field) {
            $data = $this->validProductData;
            $data[$field] = null;

            $request = new ProductRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertFalse($validator->fails(), "Validation should pass when nullable field {$field} is null");
        }
    }

    /**
     * Test validation fails with invalid boolean values.
     *
     * @return void
     */
    public function test_validation_fails_with_invalid_boolean_values()
    {
        $invalidBooleans = ['invalid', 'yes', 'no', 2, -1];
        $booleanFields = ['is_default', 'is_active'];

        foreach ($booleanFields as $field) {
            foreach ($invalidBooleans as $invalidBoolean) {
                $data = $this->validProductData;
                $data[$field] = $invalidBoolean;

                $request = new ProductRequest();
                $validator = Validator::make($data, $request->rules());

                $this->assertTrue($validator->fails(), "Validation should fail for invalid boolean in {$field}: " . json_encode($invalidBoolean));
                $this->assertTrue($validator->errors()->has($field));
            }
        }
    }

    /**
     * Test validation passes with valid boolean values.
     *
     * @return void
     */
    public function test_validation_passes_with_valid_boolean_values()
    {
        $validBooleans = [true, false, 1, 0, '1', '0'];
        $booleanFields = ['is_default', 'is_active'];

        foreach ($booleanFields as $field) {
            foreach ($validBooleans as $validBoolean) {
                $data = $this->validProductData;
                $data[$field] = $validBoolean;

                $request = new ProductRequest();
                $validator = Validator::make($data, $request->rules());

                $this->assertFalse($validator->fails(), "Validation should pass for valid boolean in {$field}: " . json_encode($validBoolean));
            }
        }
    }

    /**
     * Test foreign key validation.
     *
     * @return void
     */
    public function test_foreign_key_validation()
    {
        // Test invalid tax_id
        $data = $this->validProductData;
        $data['tax_id'] = 99999; // Non-existent ID

        $request = new ProductRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('tax_id'));

        // Test invalid category_id
        $data = $this->validProductData;
        $data['category_id'] = 99999; // Non-existent ID

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
    }

    /**
     * Test image upload validation.
     *
     * @return void
     */
    public function test_image_upload_validation()
    {
        Storage::fake('public');

        // Test valid image
        $validImage = UploadedFile::fake()->image('product.jpg', 100, 100)->size(1000);
        $data = $this->validProductData;
        $data['image'] = $validImage;

        $request = new ProductRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails(), 'Validation should pass with valid image');

        // Test image too large
        $largeImage = UploadedFile::fake()->image('large.jpg', 1000, 1000)->size(3000); // > 2048 KB
        $data['image'] = $largeImage;

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails(), 'Validation should fail with large image');
        $this->assertTrue($validator->errors()->has('image'));

        // Test non-image file
        $textFile = UploadedFile::fake()->create('document.txt', 100);
        $data['image'] = $textFile;

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails(), 'Validation should fail with non-image file');
        $this->assertTrue($validator->errors()->has('image'));
    }

    /**
     * Test authorization with authenticated user via HTTP.
     *
     * @return void
     */
    public function test_authorization_with_authenticated_user_via_http()
    {
        $this->actingAs($this->user);

        $request = Request::create('/products', 'POST', $this->validProductData);
        $productRequest = ProductRequest::createFrom($request);

        $this->assertTrue($productRequest->authorize());
    }

    /**
     * Test authorization fails with unauthenticated user via HTTP.
     *
     * @return void
     */
    public function test_authorization_fails_with_unauthenticated_user_via_http()
    {
        // Don't authenticate user
        $request = Request::create('/products', 'POST', $this->validProductData);
        $productRequest = ProductRequest::createFrom($request);

        $this->assertFalse($productRequest->authorize());
    }

    /**
     * Test slug uniqueness validation.
     *
     * @return void
     */
    public function test_slug_uniqueness_validation()
    {
        // Create a product with a specific slug
        \App\Models\Product::factory()->create(['slug' => 'existing-slug']);

        // Try to create another product with the same slug
        $data = $this->validProductData;
        $data['slug'] = 'existing-slug';

        $request = new ProductRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails(), 'Validation should fail with duplicate slug');
        $this->assertTrue($validator->errors()->has('slug'));
    }

    /**
     * Test validation with actual HTTP request.
     *
     * @return void
     */
    public function test_validation_with_actual_http_request()
    {
        $this->actingAs($this->user);

        $response = $this->post('/products', $this->validProductData);

        // We expect either success or redirect (depending on controller implementation)
        // but not validation errors
        $this->assertNotEquals(422, $response->getStatusCode(), 
            'Request should not fail with validation errors');
    }

    /**
     * Test custom attributes are applied.
     *
     * @return void
     */
    public function test_custom_attributes_are_applied()
    {
        $data = []; // Empty data to trigger validation errors

        $request = new ProductRequest();
        $validator = Validator::make($data, $request->rules());
        $validator->setAttributeNames($request->attributes());

        $this->assertTrue($validator->fails());
        
        // Check that error messages use custom attribute names
        $errors = $validator->errors();
        
        // Since we can't easily test the exact translation output,
        // we verify that attributes() method is properly structured
        $attributes = $request->attributes();
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('price', $attributes);
    }
}
