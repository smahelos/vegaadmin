<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ProductCategoryRequest;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature test for ProductCategoryRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class ProductCategoryRequestFeatureTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();

        $productCategoryFactory = ProductCategory::factory();
        $productCategoryFactory->create();
        // Define test routes
        $route = Route::post(
            '/test-product-category',
            function (ProductCategoryRequest $request) {
                $responseFactory = response();

                return $responseFactory->json(['success' => true]);
            },
        );
        $route->middleware('web');

        $route = Route::put(
            '/test-product-category/{id}',
            function (ProductCategoryRequest $request, $id) {
                $responseFactory = response();

                return $responseFactory->json(['success' => true]);
            },
        );
        $route->middleware('web');
    }

    #[Test]
    public function validation_passes_with_complete_valid_data(): void
    {
        $validData = [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and accessories',
        ];

        $request = new ProductCategoryRequest;
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

        $request = new ProductCategoryRequest;
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new ProductCategoryRequest;
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_short(): void
    {
        $invalidData = [
            'name' => 'A', // Too short (min 2 characters)
        ];

        $request = new ProductCategoryRequest;
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
        ];

        $request = new ProductCategoryRequest;
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('name', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_slug_too_long(): void
    {
        $invalidData = [
            'name' => 'Valid Name',
            'slug' => str_repeat('a', 256), // Exceeds max length of 255
        ];

        $request = new ProductCategoryRequest;
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('slug', $errors->toArray());
    }

    #[Test]
    public function validation_fails_with_duplicate_slug(): void
    {
        $productCategoryFactory = ProductCategory::factory();
        $existingCategory = $productCategoryFactory->create(['slug' => 'existing-slug']);

        $invalidData = [
            'name' => 'Valid Name',
            'slug' => 'existing-slug', // Already exists
        ];

        $request = new ProductCategoryRequest;
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();
        $this->assertArrayHasKey('slug', $errors->toArray());
    }

    #[Test]
    public function validation_passes_with_same_slug_for_update(): void
    {
        $productCategoryFactory = ProductCategory::factory();
        $existingCategory = $productCategoryFactory->create(['slug' => 'existing-slug']);

        $validData = [
            'name' => 'Updated Name',
            'slug' => 'existing-slug', // Same slug for update should be valid
        ];

        $request = new ProductCategoryRequest;
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

        $request = new ProductCategoryRequest;
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

        $request = new ProductCategoryRequest;
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

        $request = new ProductCategoryRequest;
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

        $request = new ProductCategoryRequest;
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $httpClient->postJson('/test-product-category', [
            'name' => 'Test Category',
        ]);
        $httpResponse->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_authenticated_user_without_permission(): void
    {
        $userFactory = User::factory();
        $userNoPerm = $userFactory->create();
        $this->actingAs($userNoPerm, 'backpack');

        $httpResponse = $this->postJson('/test-product-category', [
            'name' => 'No Perm Category',
        ]);
        $httpResponse->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $httpResponse = $this->postJson('/test-product-category', [
            'name' => 'Test Category',
        ]);
        $httpResponse->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_correct_translations(): void
    {
        $request = new ProductCategoryRequest;
        $attributes = $request->attributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('description', $attributes);

        // Check that translations are being called
        $this->assertEquals(
            __('products.categories.fields.name'),
            $attributes['name'],
        );
        $this->assertEquals(
            __('products.categories.fields.slug'),
            $attributes['slug'],
        );
        $this->assertEquals(
            __('products.categories.fields.description'),
            $attributes['description'],
        );
    }

    #[Test]
    public function validation_handles_empty_slug(): void
    {
        $validData = [
            'name' => 'Valid Name',
            'slug' => '', // Empty string should be treated as null
        ];

        $request = new ProductCategoryRequest;
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_handles_whitespace_in_name(): void
    {
        $validData = [
            'name' => '  Valid Name  ', // Name with whitespace
        ];

        $request = new ProductCategoryRequest;
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

        $request = new ProductCategoryRequest;
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }
}
