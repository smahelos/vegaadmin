---
mode: 'agent'
description: 'Complete guide for handling authentication and authorization in tests'
---

# Test Authentication & Authorization Guide

## 🚨 Critical Authentication Rules for Admin Tests

### Required Setup for Admin Feature Tests
```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route;

protected function setUp(): void
{
    parent::setUp();
    
    $this->user = User::factory()->create();
    
    // MANDATORY: Create permissions before testing
    $this->createRequiredPermissions();
    
    // Define test routes for HTTP testing
    Route::post('/admin/resource', function (ResourceRequest $request) {
        return response()->json(['success' => true]);
    })->middleware('web');
    
    Route::put('/admin/resource/{id}', function (ResourceRequest $request, $id) {
        return response()->json(['success' => true]);
    })->middleware('web');
}
```

### Complete Permission Setup Method
```php
private function createRequiredPermissions(): void
{
    // ALL required permissions for Backpack admin operations
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

    // Create permissions for backpack guard
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission, 
            'guard_name' => 'backpack'
        ]);
    }

    // Assign ALL permissions to test user
    foreach ($permissions as $permissionName) {
        $permission = Permission::where('name', $permissionName)
            ->where('guard_name', 'backpack')
            ->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
    }
}
```

## Test Method Templates

### 1. Successful Authentication Test
```php
public function test_authenticated_user_can_access_resource(): void
{
    $this->withoutMiddleware(); // REQUIRED: Bypass Backpack middleware
    $this->actingAs($this->user, 'backpack'); // REQUIRED: Use backpack guard

    $validData = [
        'name' => 'Test Resource',
        'required_field' => 'value'
    ];

    $response = $this->postJson('/admin/resource', $validData);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
}
```

### 2. Failed Authentication Test
```php
public function test_unauthenticated_user_cannot_access_resource(): void
{
    // DON'T call actingAs() - test unauthenticated access
    
    $validData = [
        'name' => 'Test Resource',
        'required_field' => 'value'
    ];

    $response = $this->postJson('/admin/resource', $validData);

    $response->assertStatus(403); // IMPORTANT: Expect 403, not 401 for admin
}
```

### 3. Validation Test with Authentication
```php
public function test_validation_fails_with_invalid_data(): void
{
    $this->withoutMiddleware();
    $this->actingAs($this->user, 'backpack');

    $invalidData = [
        'name' => '', // Invalid empty name
        'required_field' => 'value'
    ];

    $response = $this->postJson('/admin/resource', $invalidData);

    $response->assertStatus(422); // Validation error
    $response->assertJsonValidationErrors(['name']);
}
```

### 4. Update Operation Test
```php
public function test_user_can_update_resource(): void
{
    $this->withoutMiddleware();
    $this->actingAs($this->user, 'backpack');

    $resource = Resource::factory()->create();
    
    $updateData = [
        'name' => 'Updated Name',
        'required_field' => 'updated_value'
    ];

    $response = $this->putJson("/admin/resource/{$resource->id}", $updateData);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
}
```

## 🔑 Key Requirements Checklist

### ✅ Always Required for Admin Tests:
- [ ] Import `Spatie\Permission\Models\Permission`
- [ ] Import `Spatie\Permission\Models\Role` 
- [ ] Import `Illuminate\Support\Facades\Route`
- [ ] Create `createRequiredPermissions()` method
- [ ] Call `createRequiredPermissions()` in setUp()
- [ ] Use `$this->actingAs($user, 'backpack')` - NEVER forget the guard!
- [ ] Use `$this->withoutMiddleware()` before HTTP tests
- [ ] Use `postJson/putJson` instead of `post/put`
- [ ] Define test routes in setUp() if testing HTTP behavior
- [ ] Expect 403 for unauthenticated admin requests

### ❌ Common Mistakes to Avoid:
- ❌ Using `$this->actingAs($user)` without 'backpack' guard
- ❌ Forgetting `withoutMiddleware()` - causes 404/302 errors
- ❌ Using `post()` instead of `postJson()` - wrong status codes
- ❌ Expecting 401 instead of 403 for unauthenticated admin
- ❌ Missing permission creation - causes authorization failures
- ❌ Not assigning permissions to test user
- ❌ Using wrong guard names ('web' instead of 'backpack')

## Status Code Reference

| Scenario | Expected Status | Method |
|----------|----------------|--------|
| Successful request | 200 | `postJson/putJson` with auth |
| Validation error | 422 | `postJson/putJson` with invalid data |
| Unauthenticated admin | 403 | `postJson/putJson` without auth |
| Missing permissions | 403 | `postJson/putJson` with insufficient perms |
| Not found | 404 | Wrong route or missing resource |

## Request Methods Guide

| Use Case | Method | Why |
|----------|--------|-----|
| Create resource | `postJson()` | Returns JSON validation errors (422) |
| Update resource | `putJson()` | Returns JSON validation errors (422) |
| Test HTML forms | `post()/put()` | Returns redirects (302) for errors |
| Admin API tests | `postJson()/putJson()` | Proper JSON error handling |

## Permission Dependencies

Different admin sections require specific permissions:
- **Users**: `can_create_edit_user`
- **Invoices**: `can_create_edit_invoice` 
- **Clients**: `can_create_edit_client`
- **Products**: `can_create_edit_product`
- **System**: `can_configure_system`
- **Base access**: `backpack.access`

Always include ALL permissions in tests to avoid unexpected failures.
