<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\DatabaseMaintenanceLogRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test for DatabaseMaintenanceLogRequest class.
 * Tests authorization logic for read-only model.
 */
class DatabaseMaintenanceLogRequestFeatureTest extends TestCase
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
        Route::get('/admin/database-maintenance-log', function (DatabaseMaintenanceLogRequest $request) {
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
    public function validation_passes_with_empty_data(): void
    {
        // Since this is a read-only model, validation should always pass
        $request = new DatabaseMaintenanceLogRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_any_data(): void
    {
        // Since this is a read-only model, validation should always pass
        $anyData = [
            'random_field' => 'random_value',
            'another_field' => 123,
        ];

        $request = new DatabaseMaintenanceLogRequest();
        $validator = Validator::make($anyData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'backpack')
             ->withoutMiddleware()
             ->getJson('/admin/database-maintenance-log')
             ->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->withoutMiddleware()
             ->getJson('/admin/database-maintenance-log')
             ->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_empty_array(): void
    {
        $request = new DatabaseMaintenanceLogRequest();
        $attributes = $request->attributes();

        $this->assertIsArray($attributes);
        $this->assertEmpty($attributes);
    }

    #[Test]
    public function messages_method_returns_empty_array(): void
    {
        $request = new DatabaseMaintenanceLogRequest();
        $messages = $request->messages();

        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    #[Test]
    public function rules_method_returns_empty_array(): void
    {
        $request = new DatabaseMaintenanceLogRequest();
        $rules = $request->rules();

        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }
}
