---
mode: 'agent'
description: 'Testing standards and best practices for Laravel application'
---

# Testing Instructions and Standards

## 🚨 CRITICAL: File Handling and Safety Rules

### File Creation and Editing Safety
- **ALWAYS read existing files completely** before making any changes
- **NEVER delete and recreate files** - always edit existing files in place
- **When creating new files, ALWAYS populate them with complete content** - never create empty files
- **VERIFY file contents** after creation/editing to ensure no data loss
- **Use read_file tool extensively** to understand current file structure before modifications

### File Verification Checklist
1. **Before editing**: Read the entire file to understand current structure
2. **After editing**: Verify that all expected content is present
3. **For new files**: Ensure complete content is written, not just file creation
4. **For moved logic**: Verify that business logic is properly transferred between files

## 🚨 CRITICAL: Docker Container Execution Rules

### NEVER Use Verbose Options
- **NEVER use `-v` or `--verbose` options** when running unit tests - these options cause "Unknown option" error
- Use standard `php artisan test` without verbose flags

### Docker Container Commands
- **ALL artisan commands MUST be run in the `vegaadmin-app` docker container**
- Use: `docker exec vegaadmin-app php artisan test ...`
- Never run artisan commands directly on host system

### Correct Test Command Examples
```bash
# ✅ Correct
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/
docker exec vegaadmin-app php artisan test --filter=RequestTest

# ❌ Wrong - will cause "Unknown option" error
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php -v
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php --verbose

# ❌ Wrong - missing docker container
php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php
```

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

### Unit Test Isolation - CRITICAL RULE
- **Unit tests MUST NOT depend on Laravel framework features** (database, container, boot methods)
- **Move Laravel-dependent tests to Feature tests**: Eloquent relationships, database operations, model events (boot methods)
- **Unit tests should focus on pure business logic**: static methods, calculations, data transformations
- **Avoid `new Model()` in Unit tests**: Model instantiation triggers boot methods that require database
- **Use Feature tests for**: Model relationships, database operations, authentication, validation with database

```php
// ❌ WRONG - Unit test that requires Laravel framework
public function test_model_relationship()
{
    $model = new Model(); // This triggers boot() method requiring database
    $relation = $model->relationship();
    $this->assertInstanceOf(HasMany::class, $relation);
}

// ✅ CORRECT - Move to Feature test
class ModelFeatureTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_model_relationship()
    {
        $model = Model::factory()->create();
        $this->assertInstanceOf(HasMany::class, $model->relationship());
    }
}

// ✅ CORRECT - Unit test for pure business logic
public function test_calculation_method()
{
    $result = MyClass::calculateTax(100, 0.21);
    $this->assertEquals(21, $result);
}
```

#### Avoid Unit Tests When
- Class primarily uses Eloquent static methods (`Model::where()`, `Model::create()`)
- Model has boot methods that require database connection
- Testing requires Laravel service container (config, auth, database)
- Minimal business logic (simple CRUD operations)
- Mocking complexity exceeds testing value
- Feature tests provide adequate coverage

## Modern PHPUnit Testing Standards (REQUIRED)

### Use Modern Syntax
- Use `#[Test]` attribute instead of `test` prefix
- Use descriptive method names: `validation_fails_when_name_is_missing()`
- Use `ReflectionClass` instead of `ReflectionMethod`
- Create unique test data with `uniqid()` to avoid conflicts

### Test Standards
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

## Request Class Testing Pattern (REQUIRED TEMPLATE)
```php
<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\SomeRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SomeRequestTest extends TestCase
{
    private SomeRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required permissions for 'backpack' guard
        Permission::firstOrCreate(['name' => 'required_permission', 'guard_name' => 'backpack']);
        
        $this->request = new SomeRequest();
    }

    #[Test]
    public function request_extends_form_request(): void
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
    }

    #[Test]
    public function authorize_uses_backpack_auth(): void
    {
        // Test without authenticated user - should return false
        $this->assertFalse($this->request->authorize());
        
        // Test with authenticated user - should return true
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function authorize_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('authorize');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function rules_returns_correct_validation_rules(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        // Test specific validation rules based on business requirements
    }

    #[Test]
    public function rules_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('rules');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function attributes_returns_correct_custom_attributes(): void
    {
        $attributes = $this->request->attributes();
        
        $this->assertIsArray($attributes);
        // Test specific attributes and translation keys
    }

    #[Test]
    public function attributes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('attributes');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function messages_returns_correct_custom_messages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        // Test specific error messages and translation keys
    }

    #[Test]
    public function messages_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('messages');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        // Create test data with factories
        $data = [
            // Valid test data based on business requirements
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing(): void
    {
        $data = [];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertFalse($validator->passes());
        
        $errors = $validator->errors();
        // Test specific required field errors
    }

    // Additional business logic tests, edge cases, error scenarios...
}
```

## Required Test Coverage for Request Classes
1. **Structure Tests**: `request_extends_form_request()`
2. **Authorization Tests**: `authorize_uses_backpack_auth()` with permission testing
3. **Return Type Tests**: For `authorize()`, `rules()`, `attributes()`, `messages()`
4. **Validation Rules Tests**: Complete rule validation
5. **Attributes Tests**: Custom field names
6. **Messages Tests**: Custom error messages
7. **Business Logic Tests**: Valid/invalid data scenarios
8. **Edge Cases**: Boundary conditions, error scenarios

## Permission Setup in Tests
```php
// Always use firstOrCreate to avoid "already exists" errors
Permission::firstOrCreate(['name' => 'can_create_edit_expense', 'guard_name' => 'backpack']);

// Test permission-based authorization
$userWithPermission = User::factory()->create();
$userWithPermission->givePermissionTo('can_create_edit_expense');
$this->actingAs($userWithPermission, 'backpack');
```

## Request Class Return Types (REQUIRED)
All Request classes must have proper return type annotations:

```php
public function authorize(): bool
public function rules(): array
public function attributes(): array  // or array<string, string>
public function messages(): array    // or array<string, string>
```
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
