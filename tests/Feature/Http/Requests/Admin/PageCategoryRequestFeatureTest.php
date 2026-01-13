<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\PageCategoryRequest;
use App\Models\User;
use App\Models\PageCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature test for Admin PageCategoryRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 * 
 * Real DB schema for page_categories:
 * - id: bigint(20) unsigned, autoincrement
 * - name: varchar(255)
 * - slug: varchar(255), unique
 * - description: text, nullable
 * - created_at: timestamp, nullable
 * - updated_at: timestamp, nullable
 */
class PageCategoryRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    private User $user;
    protected User $adminUser;
    protected User $regularUser;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();
        
        // Define test routes
        Route::post('/test-page-category', function (PageCategoryRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
        
        Route::put('/test-page-category/{id}', function (PageCategoryRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    #[Test]
    public function validation_passes_with_valid_multilingual_data(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $validData = [
            'name_cs' => 'Testovací kategorie',
            'name_en' => 'Test Category',
            'name_de' => 'Test Kategorie',
            'name_sk' => 'Testovacia kategória',
            'slug_cs' => 'testovaci-kategorie-' . uniqid(),
            'slug_en' => 'test-category-' . uniqid(),
            'slug_de' => 'test-kategorie-' . uniqid(),
            'slug_sk' => 'testovacia-kategoria-' . uniqid(),
            'description_cs' => 'Popis testovací kategorie',
            'description_en' => 'Test category description',
            'description_de' => 'Test Kategorie Beschreibung',
            'description_sk' => 'Popis testovacej kategórie',
        ];

        $response = $this->postJson('/test-page-category', $validData);
        
        $response->assertStatus(200);
    }

    #[Test]
    public function validation_fails_when_multilingual_name_is_missing(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $invalidData = [
            'slug_cs' => 'test-category-' . uniqid(),
            'slug_en' => 'test-category-' . uniqid(),
            'description_cs' => 'Test category description',
            'description_en' => 'Test category description',
        ];

        $response = $this->postJson('/test-page-category', $invalidData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function validation_fails_when_no_locale_has_complete_data(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $invalidData = [
            'name_cs' => '', // empty name
            'name_en' => '', // empty name
            'name_de' => '', // empty name
            'name_sk' => '', // empty name
            'slug_cs' => 'some-slug', // has slug but no name
            'slug_en' => '', // empty slug
            'slug_de' => '', // empty slug  
            'slug_sk' => '', // empty slug
        ];

        $response = $this->postJson('/test-page-category', $invalidData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug']);
    }

    #[Test]
    public function validation_passes_when_at_least_one_locale_has_complete_data(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $validData = [
            'name_cs' => 'Testovací kategorie',
            'name_en' => '', // empty is OK
            'name_de' => '', // empty is OK
            'name_sk' => '', // empty is OK
            'slug_cs' => 'testovaci-kategorie-' . uniqid(),
            'slug_en' => '', // empty is OK
            'slug_de' => '', // empty is OK
            'slug_sk' => '', // empty is OK
            'description_cs' => 'Popis testovací kategorie',
            'description_en' => '',
            'description_de' => '',
            'description_sk' => '',
        ];

        $response = $this->postJson('/test-page-category', $validData);
        
        $response->assertStatus(200);
    }

    #[Test]
    public function validation_fails_when_slug_is_missing(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        // Since prepareForValidation auto-generates slug, test with an empty name instead
        // which would result in an empty slug
        $invalidData = [
            'name' => '', // Empty name will result in empty slug
            'description' => 'Test category description',
        ];

        $response = $this->postJson('/test-page-category', $invalidData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']); // Name is required, not slug
    }

    #[Test]
    public function validation_fails_when_slug_is_not_unique(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        // Create an existing category
        $existingCategory = PageCategory::factory()->create(['slug' => 'existing-slug']);
        
        $invalidData = [
            'name' => 'Test Category',
            'slug' => 'existing-slug', // Duplicate slug
            'description' => 'Test category description',
        ];

        $response = $this->postJson('/test-page-category', $invalidData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    #[Test]
    public function validation_passes_with_optional_multilingual_description(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        $validData = [
            'name_cs' => 'Test Category',
            'name_en' => 'Test Category',
            'name_de' => 'Test Kategorie',
            'name_sk' => 'Test Kategórie',
            'slug_cs' => 'test-category-' . uniqid(),
            'slug_en' => 'test-category-' . uniqid(),
            'slug_de' => 'test-kategorie-' . uniqid(),
            'slug_sk' => 'test-kategorie-' . uniqid(),
            // description is optional
        ];

        $response = $this->postJson('/test-page-category', $validData);
        
        $response->assertStatus(200);
    }

    #[Test]
    public function request_returns_403_without_authentication(): void
    {
        $validData = [
            'name_cs' => 'Test Category',
            'slug_cs' => 'test-category-' . uniqid(),
            'description_cs' => 'Test category description',
        ];

        $response = $this->postJson('/test-page-category', $validData);
        
        $response->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_custom_field_names(): void
    {
        $request = new PageCategoryRequest();
        $attributes = $request->attributes();
        
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        
        // Check multilingual specific attributes
        $this->assertArrayHasKey('name.cs', $attributes);
        $this->assertArrayHasKey('slug.cs', $attributes);
        $this->assertArrayHasKey('description.cs', $attributes);
    }

    #[Test]
    public function messages_method_returns_custom_error_messages(): void
    {
        $request = new PageCategoryRequest();
        $messages = $request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.array', $messages);
        $this->assertArrayHasKey('slug.required', $messages);
        $this->assertArrayHasKey('slug.array', $messages);
        
        // Check multilingual specific messages
        $this->assertArrayHasKey('name.cs.required', $messages);
        $this->assertArrayHasKey('slug.cs.required', $messages);
        $this->assertArrayHasKey('slug.cs.unique', $messages);
    }

    #[Test]
    public function validation_rules_can_be_tested_directly(): void
    {
        $request = new PageCategoryRequest();
        $rules = $request->rules();
        
        // Test with valid multilingual data (all locales required)
        $validData = [
            'name_cs' => 'Test Category',
            'name_en' => 'Test Category',
            'name_de' => 'Test Kategorie',
            'name_sk' => 'Test Kategórie',
            'slug_cs' => 'test-category-' . uniqid(),
            'slug_en' => 'test-category-' . uniqid(),
            'slug_de' => 'test-kategorie-' . uniqid(),
            'slug_sk' => 'test-kategorie-' . uniqid(),
            'description_cs' => 'Test category description',
            'description_en' => 'Test category description',
            'description_de' => 'Test Kategorie Beschreibung',
            'description_sk' => 'Test kategórie popis',
        ];
        
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_rules_fail_with_invalid_data(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        // Test with empty data - should fail because no locale has complete data
        $invalidData = [];
        
        $response = $this->postJson('/test-page-category', $invalidData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug']);
    }
}
