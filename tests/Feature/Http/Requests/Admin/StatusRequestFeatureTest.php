<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\StatusRequest;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test for StatusRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class StatusRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private StatusCategory $statusCategory;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Create necessary permissions for testing
        $this->createRequiredPermissions();
        
        $this->statusCategory = StatusCategory::factory()->create();
        
        // Define test routes
        Route::post('/admin/status', function (StatusRequest $request) {
            return response()->json(['success' => true]);
        })->middleware(['web', 'admin']);
        
        Route::put('/admin/status/{id}', function (StatusRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware(['web', 'admin']);
    }

    /**
     * Create required permissions for testing.
     * Based on the permissions checked in the Backpack menu.
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

    /**
     * Test successful validation with valid data.
     */
    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $this->actingAs($this->user, 'backpack');

        $validData = [
            'name' => 'Test Status',
            'slug' => 'test-status',
            'category_id' => $this->statusCategory->id,
            'color' => '#FF0000',
            'description' => 'Test description',
            'is_active' => true,
        ];

        $response = $this->post('/admin/status', $validData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test validation fails when required fields are missing.
     */
    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $this->actingAs($this->user, 'backpack');

        $response = $this->postJson('/admin/status', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug', 'category_id']);
    }

    /**
     * Test validation fails when name exceeds maximum length.
     */
    #[Test]
    public function validation_fails_when_name_too_long(): void
    {
        $this->actingAs($this->user, 'backpack');

        $invalidData = [
            'name' => str_repeat('a', 256), // Exceeds max length of 255
            'slug' => 'test-slug',
            'category_id' => $this->statusCategory->id,
        ];

        $response = $this->postJson('/admin/status', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test validation fails when slug exceeds maximum length.
    /**
     * Test validation fails when slug exceeds maximum length.
     */
    #[Test]
    public function validation_fails_when_slug_too_long(): void
    {
        $this->actingAs($this->user, 'backpack');

        $invalidData = [
            'name' => 'Test Status',
            'slug' => str_repeat('a', 256), // Exceeds max length of 255
            'category_id' => $this->statusCategory->id,
        ];

        $response = $this->postJson('/admin/status', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    /**
     * Test validation fails when category_id does not exist.
     */
    #[Test]
    public function validation_fails_with_invalid_category_id(): void
    {
        $this->actingAs($this->user, 'backpack');

        $invalidData = [
            'name' => 'Test Status',
            'slug' => 'test-slug',
            'category_id' => 99999, // Non-existent category
        ];

        $response = $this->postJson('/admin/status', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    /**
     * Test unique slug validation on creation.
     */
    #[Test]
    public function validation_fails_with_duplicate_slug(): void
    {
        $this->actingAs($this->user, 'backpack');

        // Create existing status
        $existingStatus = Status::factory()->create([
            'slug' => 'existing-slug',
            'category_id' => $this->statusCategory->id,
        ]);

        $invalidData = [
            'name' => 'Test Status',
            'slug' => 'existing-slug', // Duplicate slug
            'category_id' => $this->statusCategory->id,
        ];

        $response = $this->postJson('/admin/status', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    /**
     * Test unique slug validation allows same slug on update.
     */
    #[Test]
    public function validation_allows_same_slug_on_update(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user, 'backpack');

        $status = Status::factory()->create([
            'slug' => 'test-slug',
            'category_id' => $this->statusCategory->id,
        ]);

        $updateData = [
            'name' => 'Updated Status Name',
            'slug' => 'test-slug', // Same slug should be allowed
            'category_id' => $this->statusCategory->id,
        ];

        $response = $this->put("/admin/status/{$status->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test authorization passes when user has required permission.
     */
    #[Test]
    public function authorization_passes_with_permission(): void
    {
        $this->actingAs($this->user, 'backpack');

        $validData = [
            'name' => 'Test Status',
            'slug' => 'test-status',
            'category_id' => $this->statusCategory->id,
        ];

        $response = $this->post('/admin/status', $validData);

        $response->assertStatus(200);
    }

    /**
     * Test authorization fails when user lacks required permission.
     */
    #[Test]
    public function authorization_fails_without_permission(): void
    {
        // Create user without admin permissions
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser, 'backpack');

        $validData = [
            'name' => 'Test Status',
            'slug' => 'test-status',
            'category_id' => $this->statusCategory->id,
        ];

        $response = $this->post('/admin/status', $validData);

        $response->assertStatus(403);
    }

    /**
     * Test custom attributes are correctly defined.
     */
    #[Test]
    public function custom_attributes_are_defined(): void
    {
        $request = new StatusRequest();
        $attributes = $request->attributes();

        $expectedAttributes = [
            'name' => __('statuses.fields.name'),
            'slug' => __('statuses.fields.slug'),
            'category_id' => __('statuses.fields.category'),
            'color' => __('statuses.fields.color'),
            'description' => __('statuses.fields.description'),
            'is_active' => __('statuses.fields.is_active'),
        ];

        $this->assertEquals($expectedAttributes, $attributes);
    }

    /**
     * Test custom messages are correctly defined.
     */
    #[Test]
    public function custom_messages_are_defined(): void
    {
        $request = new StatusRequest();
        $messages = $request->messages();

        $expectedMessages = [
            'name.required' => __('statuses.validation.name_required'),
            'slug.required' => __('statuses.validation.slug_required'),
            'slug.unique' => __('statuses.validation.slug_unique'),
        ];

        $this->assertEquals($expectedMessages, $messages);
    }

    /**
     * Test boolean validation for is_active field.
     */
    #[Test]
    public function validation_accepts_boolean_values_for_is_active(): void
    {
        $this->actingAs($this->user, 'backpack');

        // Test with boolean true
        $validData = [
            'name' => 'Test Status',
            'slug' => 'test-status-true',
            'category_id' => $this->statusCategory->id,
            'is_active' => true,
        ];

        $response = $this->post('/admin/status', $validData);
        $response->assertStatus(200);

        // Test with boolean false
        $validData['slug'] = 'test-status-false';
        $validData['is_active'] = false;

        $response = $this->post('/admin/status', $validData);
        $response->assertStatus(200);

        // Test with string '1'
        $validData['slug'] = 'test-status-string-one';
        $validData['is_active'] = '1';

        $response = $this->post('/admin/status', $validData);
        $response->assertStatus(200);

        // Test with string '0'
        $validData['slug'] = 'test-status-string-zero';
        $validData['is_active'] = '0';

        $response = $this->post('/admin/status', $validData);
        $response->assertStatus(200);
    }

    /**
     * Test validation handles nullable fields correctly.
     */
    #[Test]
    public function validation_handles_nullable_fields(): void
    {
        $this->actingAs($this->user, 'backpack');

        $minimalData = [
            'name' => 'Minimal Status',
            'slug' => 'minimal-status',
            'category_id' => $this->statusCategory->id,
            // color and description are nullable
            // is_active defaults to false if not provided
        ];

        $response = $this->post('/admin/status', $minimalData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
