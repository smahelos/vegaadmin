<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ArchivePolicyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test for ArchivePolicyRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class ArchivePolicyRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Create necessary permissions for testing
        $this->createRequiredPermissions();
        
        // Define test routes
        Route::post('/admin/archive-policy', function (ArchivePolicyRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
        
        Route::put('/admin/archive-policy/{id}', function (ArchivePolicyRequest $request, $id) {
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
            'table_name' => 'old_invoices',
            'retention_months' => 24,
            'date_column' => 'created_at',
            'is_active' => true,
            'description' => 'Archive policy for old invoices after 2 years',
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $minimalData = [
            'table_name' => 'test_table',
            'retention_months' => 12,
            'date_column' => 'updated_at',
            // is_active and description are optional
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new ArchivePolicyRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('table_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('retention_months', $validator->errors()->toArray());
        $this->assertArrayHasKey('date_column', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_table_name_too_long(): void
    {
        $invalidData = [
            'table_name' => str_repeat('a', 256), // Exceeds max length of 255
            'retention_months' => 12,
            'date_column' => 'created_at',
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('table_name', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_date_column_too_long(): void
    {
        $invalidData = [
            'table_name' => 'valid_table',
            'retention_months' => 12,
            'date_column' => str_repeat('a', 256), // Exceeds max length of 255
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('date_column', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_retention_months_too_low(): void
    {
        $invalidData = [
            'table_name' => 'valid_table',
            'retention_months' => 0, // Below minimum of 1
            'date_column' => 'created_at',
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('retention_months', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_retention_months_too_high(): void
    {
        $invalidData = [
            'table_name' => 'valid_table',
            'retention_months' => 121, // Above maximum of 120
            'date_column' => 'created_at',
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('retention_months', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_retention_months_not_integer(): void
    {
        $invalidData = [
            'table_name' => 'valid_table',
            'retention_months' => 'not_an_integer',
            'date_column' => 'created_at',
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('retention_months', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_when_description_too_long(): void
    {
        $invalidData = [
            'table_name' => 'valid_table',
            'retention_months' => 12,
            'date_column' => 'created_at',
            'description' => str_repeat('a', 1001), // Exceeds max length of 1000
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_boolean_is_active(): void
    {
        $validData = [
            'table_name' => 'valid_table',
            'retention_months' => 12,
            'date_column' => 'created_at',
            'is_active' => false,
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_edge_case_values(): void
    {
        // Test minimum valid retention months
        $validData = [
            'table_name' => 'valid_table',
            'retention_months' => 1, // Minimum value
            'date_column' => 'created_at',
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());

        // Test maximum valid retention months
        $validData['retention_months'] = 120; // Maximum value
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());

        // Test maximum valid description length
        $validData['description'] = str_repeat('a', 1000); // Exactly 1000 characters
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_nullable_fields(): void
    {
        $validData = [
            'table_name' => 'valid_table',
            'retention_months' => 12,
            'date_column' => 'created_at',
            // is_active is boolean, so skip it when null
            'description' => null,
        ];

        $request = new ArchivePolicyRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'backpack')
             ->withoutMiddleware()
             ->postJson('/admin/archive-policy', [
                 'table_name' => 'test_table',
                 'retention_months' => 12,
                 'date_column' => 'created_at',
             ])
             ->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->withoutMiddleware()
             ->postJson('/admin/archive-policy', [
                 'table_name' => 'test_table',
                 'retention_months' => 12,
                 'date_column' => 'created_at',
             ])
             ->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_correct_translations(): void
    {
        $request = new ArchivePolicyRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('table_name', $attributes);
        $this->assertArrayHasKey('retention_months', $attributes);
        $this->assertArrayHasKey('date_column', $attributes);
        $this->assertArrayHasKey('is_active', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        
        // Check that translations are being called
        $this->assertEquals(__('admin.database.table_name'), $attributes['table_name']);
        $this->assertEquals(__('admin.database.retention_months'), $attributes['retention_months']);
        $this->assertEquals(__('admin.database.date_column'), $attributes['date_column']);
        $this->assertEquals(__('admin.database.is_active'), $attributes['is_active']);
        $this->assertEquals(__('admin.database.description'), $attributes['description']);
    }

    #[Test]
    public function messages_method_returns_correct_translations(): void
    {
        $request = new ArchivePolicyRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('table_name.required', $messages);
        $this->assertArrayHasKey('retention_months.required', $messages);
        $this->assertArrayHasKey('retention_months.min', $messages);
        $this->assertArrayHasKey('retention_months.max', $messages);
        $this->assertArrayHasKey('date_column.required', $messages);
        
        // Check that translations are being called
        $this->assertEquals(__('admin.database.table_name_required'), $messages['table_name.required']);
        $this->assertEquals(__('admin.database.retention_months_required'), $messages['retention_months.required']);
        $this->assertEquals(__('admin.database.retention_months_min'), $messages['retention_months.min']);
        $this->assertEquals(__('admin.database.retention_months_max'), $messages['retention_months.max']);
        $this->assertEquals(__('admin.database.date_column_required'), $messages['date_column.required']);
    }
}
