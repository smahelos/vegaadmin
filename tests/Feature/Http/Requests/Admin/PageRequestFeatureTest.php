<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\PageRequest;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature test for Admin PageRequest.
 * Validates authorization and validation logic using Laravel services.
 */
class PageRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    private PageRequest $request;
    private User $adminUser;
    private PageCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
        $this->category = PageCategory::factory()->create();
        $this->request = new PageRequest();

        Route::post('/admin/test-page-validation', function (PageRequest $request) {
            return response()->json(['success' => true]);
        })->middleware(['web', 'auth:backpack']);
    }

    #[Test]
    public function authorize_returns_false_without_authenticated_user(): void
    {
        $this->assertFalse($this->request->authorize());
    }

    #[Test]
    public function authorize_returns_true_with_permission(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function validation_passes_with_minimal_data(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Test Page',
            'slug_cs' => 'test-page',
        ]);
        $response->assertStatus(200);
    }

    #[Test]
    public function validation_fails_when_name_missing(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function validation_fails_when_slug_too_long(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Test Page',
            'slug_cs' => str_repeat('a', 256),
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['slug.cs']);
    }

    #[Test]
    public function validation_passes_with_dates_and_order(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Test Page',
            'slug_cs' => 'test-page',
            'publishing_start' => '2024-01-01 10:00:00',
            'publishing_end' => '2024-01-02 10:00:00',
            'sort_order' => 0,
        ]);
        $response->assertStatus(200);
    }

    #[Test]
    public function validation_fails_with_invalid_date_sequence(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Test Page',
            'slug_cs' => 'test-page',
            'publishing_start' => '2024-01-02 10:00:00',
            'publishing_end' => '2024-01-01 10:00:00',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['publishing_end']);
    }

    #[Test]
    public function validation_fails_when_name_exceeds_max_length(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => str_repeat('a', 256),
            'slug_cs' => 'valid-slug',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['name.cs']);
    }

    #[Test]
    public function validation_fails_when_sort_order_negative(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Name',
            'slug_cs' => 'name',
            'sort_order' => -1,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['sort_order']);
    }

    #[Test]
    public function validation_passes_with_optional_fields(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Name',
            'slug_cs' => 'name',
            'description_cs' => 'Desc',
            'content_cs' => '<p>Content</p>',
            'meta_title_cs' => 'Meta',
            'published' => true,
            'sort_order' => 5,
        ]);
        $response->assertStatus(200);
    }

    #[Test]
    public function validation_passes_with_valid_boolean_values_for_published(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        foreach (['1','0',1,0,true,false] as $value) {
            $response = $this->postJson('/admin/test-page-validation', [
                'name_cs' => 'Name '.uniqid(),
                'slug_cs' => 'slug-'.uniqid(),
                'published' => $value,
            ]);
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function validation_fails_when_category_id_invalid(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Name',
            'slug_cs' => 'slug',
            'category_id' => 999999,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    #[Test]
    public function validation_passes_with_valid_category(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $category = PageCategory::factory()->create();
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => 'Name',
            'slug_cs' => 'slug',
            'category_id' => $category->id,
        ]);
        $response->assertStatus(200);
    }

    #[Test]
    public function attributes_method_returns_translated_field_names(): void
    {
        $attributes = $this->request->attributes();
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('slug', $attributes);
        $this->assertArrayHasKey('published', $attributes);
        $this->assertArrayHasKey('name.cs', $attributes);
    }

    #[Test]
    public function messages_method_returns_translated_validation_messages(): void
    {
        $messages = $this->request->messages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('slug.required', $messages);
        $this->assertArrayHasKey('name.cs.string', $messages);
    }

    #[Test]
    public function prepare_for_validation_transforms_multilingual_data_correctly(): void
    {
        // Define route locally to capture transformed output
        \Illuminate\Support\Facades\Route::post('/admin/test-page-transform', function (PageRequest $request) {
            return response()->json($request->all());
        })->middleware(['web','auth:backpack']);
        $this->actingAs($this->adminUser, 'backpack');
        $payload = [
            'name_cs' => 'Nazev',
            'name_en' => 'Name',
            'slug_cs' => 'nazev',
            'slug_en' => 'name',
        ];
        $response = $this->postJson('/admin/test-page-transform', $payload);
        $response->assertStatus(200)
            ->assertJsonMissing(['name_cs','name_en'])
            ->assertJsonStructure(['name'=>['cs','en'],'slug'=>['cs','en']]);
    }

    #[Test]
    public function with_validator_enforces_at_least_one_locale_for_name_and_slug(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->postJson('/admin/test-page-validation', [
            'name_cs' => '',
            'slug_cs' => '',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['name','slug']);
    }
}
