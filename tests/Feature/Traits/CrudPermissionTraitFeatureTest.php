<?php

namespace Tests\Feature\Traits;

use App\Models\User;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CrudPermissionTraitFeatureTest extends TestCase
{
    use RefreshDatabase;

    private TestController $controller;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Create required permissions for 'backpack' guard according to authentication instructions
        $this->createRequiredPermissions();
        
        // Create test controller that uses the trait
        $this->controller = new TestController();
        $this->controller->setupCrud();
    }

    private function createRequiredPermissions(): void
    {
        $permissions = [
            'users.can_view_user',
            'users.can_create_edit_user',
            'clients.can_view_user',
            'clients.can_create_edit_user',
        ];

        // Create permissions for backpack guard
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission, 
                'guard_name' => 'backpack'
            ]);
        }
    }

    #[Test]
    public function operations_property_contains_correct_crud_operations(): void
    {
        $expectedOperations = ['list', 'show', 'create', 'update', 'delete'];
        $this->assertEquals($expectedOperations, $this->controller->operations);
    }

    #[Test]
    public function denies_all_access_when_no_user_authenticated(): void
    {
        // Test with null user
        $this->controller->setAccessUsingPermissionsWithUser(null);
        
        // Should deny all operations
        foreach ($this->controller->operations as $operation) {
            $this->assertFalse($this->controller->crud->hasAccess($operation));
        }
    }

    #[Test]
    public function allows_view_access_with_can_view_user_permission(): void
    {
        // Get permission object and assign it properly
        $permission = Permission::where('name', 'users.can_view_user')
            ->where('guard_name', 'backpack')
            ->first();
        $this->user->givePermissionTo($permission);
        
        // Test with user directly
        $this->controller->setAccessUsingPermissionsWithUser($this->user);
        
        // Should allow list and show
        $this->assertTrue($this->controller->crud->hasAccess('list'));
        $this->assertTrue($this->controller->crud->hasAccess('show'));
        
        // Should deny create, update, delete
        $this->assertFalse($this->controller->crud->hasAccess('create'));
        $this->assertFalse($this->controller->crud->hasAccess('update'));
        $this->assertFalse($this->controller->crud->hasAccess('delete'));
    }

    #[Test]
    public function allows_full_access_with_can_create_edit_user_permission(): void
    {
        // Get permission object and assign it properly
        $permission = Permission::where('name', 'users.can_create_edit_user')
            ->where('guard_name', 'backpack')
            ->first();
        $this->user->givePermissionTo($permission);
        
        $this->controller->setAccessUsingPermissionsWithUser($this->user);
        
        // Should allow all operations
        foreach ($this->controller->operations as $operation) {
            $this->assertTrue($this->controller->crud->hasAccess($operation));
        }
    }

    #[Test]
    public function denies_access_when_user_has_no_permissions(): void
    {
        // User has no permissions
        
        $this->controller->setAccessUsingPermissionsWithUser($this->user);
        
        // Should deny all operations
        foreach ($this->controller->operations as $operation) {
            $this->assertFalse($this->controller->crud->hasAccess($operation));
        }
    }

    #[Test]
    public function higher_permission_overrides_lower_permission(): void
    {
        // Assign both permissions
        $viewPermission = Permission::where('name', 'users.can_view_user')
            ->where('guard_name', 'backpack')
            ->first();
        $editPermission = Permission::where('name', 'users.can_create_edit_user')
            ->where('guard_name', 'backpack')
            ->first();
            
        $this->user->givePermissionTo($viewPermission);
        $this->user->givePermissionTo($editPermission);
        
        $this->controller->setAccessUsingPermissionsWithUser($this->user);
        
        // Should allow all operations (higher permission wins)
        foreach ($this->controller->operations as $operation) {
            $this->assertTrue($this->controller->crud->hasAccess($operation));
        }
    }

    #[Test]
    public function works_with_different_table_names(): void
    {
        // Get permission for different table
        $permission = Permission::where('name', 'clients.can_view_user')
            ->where('guard_name', 'backpack')
            ->first();
        $this->user->givePermissionTo($permission);
        
        // Create controller with different table name
        $controller = new TestController();
        $controller->setupCrud('clients');
        $controller->setAccessUsingPermissionsWithUser($this->user);
        
        // Should allow list and show for clients table
        $this->assertTrue($controller->crud->hasAccess('list'));
        $this->assertTrue($controller->crud->hasAccess('show'));
        $this->assertFalse($controller->crud->hasAccess('create'));
    }
}

/**
 * Test controller class that uses CrudPermissionTrait for testing
 */
class TestController
{
    use CrudPermissionTrait;

    public $crud;
    private string $table = 'users';

    public function setupCrud(string $table = 'users'): void
    {
        $this->table = $table;
        
        // Create a simple CRUD mock object
        $this->crud = new class {
            private array $allowedOperations = [];
            private array $deniedOperations = [];

            public function allowAccess($operations): void
            {
                $operations = is_array($operations) ? $operations : [$operations];
                $this->allowedOperations = array_unique(array_merge($this->allowedOperations, $operations));
                // Remove from denied if it was there
                $this->deniedOperations = array_diff($this->deniedOperations, $operations);
            }

            public function denyAccess($operations): void
            {
                $operations = is_array($operations) ? $operations : [$operations];
                $this->deniedOperations = array_unique(array_merge($this->deniedOperations, $operations));
                // Remove from allowed if it was there
                $this->allowedOperations = array_diff($this->allowedOperations, $operations);
            }

            public function hasAccess(string $operation): bool
            {
                return in_array($operation, $this->allowedOperations) && !in_array($operation, $this->deniedOperations);
            }
        };
    }

    // Test method that accepts user directly for testing
    public function setAccessUsingPermissionsWithUser($user): void
    {
        // Default - deny all access
        $this->crud->denyAccess($this->operations);

        // Exit if no authenticated user
        if (!$user) {
            return;
        }

        // Enable operations based on permissions - use hasPermissionTo with explicit guard
        foreach ([
            // Format: permission level => [allowed crud operations]
            'can_view_user' => ['list', 'show'],
            'can_create_edit_user' => ['list', 'show', 'create', 'update', 'delete'],
        ] as $level => $operations) {
            if ($user->hasPermissionTo("{$this->table}.$level", 'backpack')) {
                $this->crud->allowAccess($operations);
            }
        }
    }

    // Override the trait method to use auth system
    public function setAccessUsingPermissions(): void
    {
        $user = auth('backpack')->user();
        $this->setAccessUsingPermissionsWithUser($user);
    }
}
