---
mode: 'agent'
description: 'Testing standards and best practices for Laravel application'
---

# Testing Instructions and Standards

## Test Organization
- **Unit Tests**: `tests/Unit/` - Test individual classes without dependencies
- **Feature Tests**: `tests/Feature/` - Test application features with full context
- **Integration Tests**: Test interactions between components

## Unit vs Feature Test Guidelines

### Unit Tests Should:
- Test model structure, accessors, mutators, traits (no database)
- Test request validation rules, authorization, messages (no HTTP context)
- Test service methods and business logic (mocked dependencies)
- Test helper functions and utilities
- Run fast without external dependencies

### Feature Tests Should:
- Test relationships and database interactions
- Test HTTP requests and responses
- Test full workflow scenarios
- Test with real database using RefreshDatabase
- Test authentication and authorization flows

## Test Standards
- Use `WithFaker` trait for generating test data
- Use explicit setup methods instead of global seeders
- Create helper methods for permissions, roles, and test data
- Test isolation - each test should be independent
- Use descriptive test method names
- Group related tests in logical test classes
- **NEVER use deprecated methods or features in tests** - always use current best practices
- If a test shows deprecated warnings, it must be fixed immediately

## 🚨 IMPORTANT: Code Quality First
**When tests fail due to missing return types or method signatures:**
1. **PREFER fixing the code** over changing tests
2. **Add explicit return types** to improve code quality and IDE support
3. **Implement missing methods** that should exist according to business logic
4. **Only modify tests** if the expectation is genuinely wrong
5. **Always test functionality** after any code changes
6. **Report any problematic code patterns** and suggest better solutions

## Request Class Testing Pattern
```php
// Unit Test: Test rules, authorize, messages methods
// Feature Test: Test actual validation scenarios with HTTP context
```

## Database Testing
- Use `RefreshDatabase` trait for database tests
- Create explicit test data with factories
- Test database constraints and relationships
- Clean up after tests automatically

## Permissions Testing
- Create permissions and roles explicitly in tests
- Use helper methods for user creation with roles
- Test authorization scenarios thoroughly
- Don't rely on existing seeded data

## Backpack Admin Testing Requirements

### Authentication Setup for Admin Tests
```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            ->where('guard_name', 'backpack')
            ->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
    }
}
```

### Admin Test Method Template
```php
public function test_admin_functionality(): void
{
    $this->withoutMiddleware(); // REQUIRED: Bypass Backpack middleware
    $this->actingAs($this->user, 'backpack'); // REQUIRED: Use backpack guard

    $response = $this->postJson('/admin/resource', $data);
    
    $response->assertStatus(200); // 403 for unauthenticated
    $response->assertJson(['success' => true]);
}
```

### Critical Points:
- **Always use 'backpack' guard**: `$this->actingAs($user, 'backpack')`
- **Always use withoutMiddleware()** for admin HTTP tests
- **Use postJson/putJson** instead of post/put for proper status codes
- **Expect 403** for unauthenticated admin requests (not 401)

## Deprecated Code Policy
- No deprecated methods should be used in any tests
- All deprecated warnings must be resolved before merging
- Use reflection or direct method calls instead of deprecated `createMock()` patterns
- Follow current Laravel testing best practices
