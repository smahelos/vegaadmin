# Backpack Admin Test Authentication - Quick Reference

## 🔥 Essential Setup (Copy-Paste Ready)

### 1. Required Imports
```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route;
```

### 2. setUp() Method Template
```php
protected function setUp(): void
{
    parent::setUp();
    
    $this->user = User::factory()->create();
    $this->createRequiredPermissions();
    
    // Define test routes
    Route::post('/admin/resource', function (ResourceRequest $request) {
        return response()->json(['success' => true]);
    })->middleware('web');
}
```

### 3. Permission Method (Copy Exact)
```php
private function createRequiredPermissions(): void
{
    $permissions = [
        'can_create_edit_user', 'can_create_edit_invoice', 'can_create_edit_client',
        'can_create_edit_supplier', 'can_create_edit_expense', 'can_create_edit_tax',
        'can_create_edit_bank', 'can_create_edit_payment_method', 'can_create_edit_product',
        'can_create_edit_command', 'can_create_edit_cron_task', 'can_create_edit_status',
        'can_configure_system', 'backpack.access'
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'backpack']);
    }

    foreach ($permissions as $permissionName) {
        $permission = Permission::where('name', $permissionName)
            ->where('guard_name', 'backpack')->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
    }
}
```

## 🎯 Test Method Templates

### Authenticated Success
```php
public function test_success(): void
{
    $this->withoutMiddleware();
    $this->actingAs($this->user, 'backpack');
    
    $response = $this->postJson('/admin/resource', $data);
    $response->assertStatus(200);
}
```

### Unauthenticated Failure
```php
public function test_unauthenticated(): void
{
    $response = $this->postJson('/admin/resource', $data);
    $response->assertStatus(403);
}
```

### Validation Error
```php
public function test_validation(): void
{
    $this->withoutMiddleware();
    $this->actingAs($this->user, 'backpack');
    
    $response = $this->postJson('/admin/resource', $invalidData);
    $response->assertStatus(422);
}
```

## ❌ Common Mistakes
- ❌ `$this->actingAs($user)` → ✅ `$this->actingAs($user, 'backpack')`
- ❌ `$this->post()` → ✅ `$this->postJson()`
- ❌ Missing `withoutMiddleware()` → 404/302 errors
- ❌ Expecting 401 → ✅ Expect 403 for admin
- ❌ Missing permissions → Authorization failures
