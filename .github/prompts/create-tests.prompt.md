---
mode: 'agent'
description: 'Prompt for creating comprehensive tests for any component'
---

# Create Tests for Component

## 🚨 VERY IMPORTANT: Test-Driven Development (TDD) Principles

### Core Testing Philosophy

#### ❌ NEVER: Write Tests That Accommodate Bad Code
- **NEVER** write tests that work around or accommodate incorrect behavior in application code
- **NEVER** adjust test expectations to match buggy or non-standard code behavior
- **NEVER** accept incorrect exit codes, missing error handling, or improper return values
- **NEVER** write tests that pass for the wrong reasons

#### ✅ ALWAYS: Fix Application Code to Meet Test Expectations
- **ALWAYS** fix the application code when tests reveal problems
- **ALWAYS** ensure proper exit codes (0 for success, non-zero for errors)
- **ALWAYS** implement proper error handling and return values
- **ALWAYS** follow standard conventions and best practices
- **ALWAYS** make the code better through testing

### TDD Process (CRITICAL)
1. **Write tests that define correct behavior** (proper exit codes, error handling, etc.)
2. **Run tests and let them fail** if code doesn't meet expectations
3. **Analyze failures** - do they indicate real problems in the code?
4. **Fix the application code** to make tests pass (not the tests!)
5. **Refactor and improve** while maintaining test coverage

### Console Command Testing Standards
```php
// ✅ CORRECT: Expect proper behavior and fix code if needed
public function test_command_handles_invalid_user(): void
{
    $exitCode = Artisan::call('command', ['--user' => 'invalid']);
    $this->assertEquals(1, $exitCode); // Expecting proper error exit code
}

// ❌ WRONG: Adjusting test to accommodate bad code
public function test_command_handles_invalid_user(): void
{
    $exitCode = Artisan::call('command', ['--user' => 'invalid']);
    $this->assertEquals(0, $exitCode); // Accepting wrong behavior
}
```

### When Tests Fail - Decision Tree
1. **First**: Does the failure indicate incorrect application behavior?
2. **If YES**: Fix the application code, don't change the test
3. **If NO**: Check if test expectations are wrong, then fix test
4. **Remember**: Tests should drive code quality improvement

## 🚨 CRITICAL: File Safety and Content Verification

### Mandatory File Handling Rules
- **ALWAYS read files completely** before making any modifications
- **NEVER delete and recreate files** - always edit existing files in place 
- **VERIFY file contents** after every creation/modification operation
- **When creating new files, ALWAYS include complete content** - never create empty files
- **Use replace_string_in_file and insert_edit_into_file** for existing file modifications
- **Double-check that business logic is properly transferred** when moving tests between files

### File Operations Safety Checklist
1. **Before any edit**: Use read_file to understand current file structure
2. **After file creation**: Verify the file contains expected content, not just exists
3. **After moving logic**: Ensure source logic is removed and target logic is complete
4. **After refactoring**: Run tests to verify functionality is preserved

## 🚨 CRITICAL: Docker Container & Testing Rules

### Docker Container Execution
- **ALL artisan commands MUST be run in the `vegaadmin-app` docker container**
- Use: `docker exec vegaadmin-app php artisan test ...`
- Never run artisan commands directly on host system

### Testing Commands - NEVER Use Verbose Options
- **NEVER use `-v` or `--verbose` options** when running unit tests
- These options cause "Unknown option" error in our testing environment
- Use standard `php artisan test` without verbose flags

### Correct Test Execution Examples
```bash
# ✅ Correct
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/
docker exec vegaadmin-app php artisan test --filter=RequestTest

# ❌ Wrong - will cause "Unknown option" error
docker exec vegaadmin-app php artisan test file.php -v
docker exec vegaadmin-app php artisan test file.php --verbose

# ❌ Wrong - missing docker container
php artisan test file.php
```

## 🚨 IMPORTANT: Code Quality First
**When tests fail due to missing return types or method signatures:**
1. **PREFER fixing the code** over changing tests
2. **Add explicit return types** to improve code quality and IDE support
3. **Implement missing methods** that should exist according to business logic
4. **Only modify tests** if the expectation is genuinely wrong
5. **Always test functionality** after any code changes
6. **Report any problematic code patterns** and suggest better solutions

## Modern PHPUnit Testing Standards (REQUIRED)

### Test File Naming Conventions (CRITICAL)
- **Feature Tests**: MUST include "Feature" in filename (e.g., `ClientControllerFeatureTest.php`)
- **Unit Tests**: MUST NOT include "Unit" in filename (e.g., `ClientListTest.php`, not `ClientListUnitTest.php`)
- **Avoid Duplicates**: Never create both `ClassName.php` and `ClassNameUnitTest.php` - use only `ClassName.php` for Unit tests
- **Be Descriptive**: Use meaningful names that clearly indicate what is being tested

### Test Organization Rules
- **Unit Tests**: `tests/Unit/` - Test individual classes without dependencies
- **Feature Tests**: `tests/Feature/` - Test application features with full context
- **No Duplicate Coverage**: Each component should have either Unit OR Feature tests, not both testing same functionality
- **Integration Tests**: Use Feature tests for interactions between components

### Use Modern Syntax
- Use `#[Test]` attribute instead of `test` prefix
- Use descriptive method names: `validation_fails_when_name_is_missing()`
- Use `ReflectionClass` instead of `ReflectionMethod`
- Create unique test data with `uniqid()` to avoid conflicts

## Test Development Principles
- Tests should document expected behavior
- Code should meet test expectations, not the other way around
- Explicit return types improve code quality and catch errors early
- Missing methods often indicate incomplete implementation

## Determine Test Type
1. **Models**: Split into Unit (structure, traits) and Feature (relationships, DB)
2. **Request Classes**: Split into Unit (rules, messages) and Feature (HTTP validation)
3. **Controllers**: Feature tests only (test HTTP workflows)
4. **Services**: Unit tests with mocked dependencies
5. **Repositories**: Feature tests with database interactions
6. **Middleware**: Feature tests with HTTP context

## Request Class Unit Test Template (REQUIRED)
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

## Unit Test Template
```php
<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleModelTest extends TestCase
{
    #[Test]
    public function model_has_correct_fillable_attributes(): void
    {
        // Test class structure and methods without external dependencies
        // Mock all external dependencies
        // Test return values and method behaviors
        // Fast execution, no database/HTTP
    }
}
```

## Feature Test Template
```php
<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleModelTest extends TestCase
{
    use RefreshDatabase, WithFaker; // REQUIRED: RefreshDatabase for all Feature tests

    #[Test]
    public function model_can_be_created_with_factory()
    {
        // Test real-world scenarios with full context
        // RefreshDatabase ensures clean database state for each test
        // Create explicit test data with factories
        // Test complete workflows and integrations
    }
}
```

## Required Test Patterns

### For Request Classes:
- **Unit**: Test rules(), authorize(), messages(), attributes()
- **Feature**: Test actual validation with HTTP context

**✅ PROJECT STATUS - Request Classes Testing:**
- **36 Request classes** total (17 frontend + 19 admin)
- **36 unit tests** created and passing
- **319 test assertions** covering explicit return types
- **All tests use PHP reflection** to verify method signatures
- **100% explicit return type coverage** for authorize(), rules(), attributes(), messages()
- **All missing methods added** where required
- **Consistent code quality** across all Request classes

### For Models:
- **Unit**: Test fillable, casts, accessors, mutators, traits
- **Feature**: Test relationships, scopes, database interactions

### For Controllers:
- **Feature**: Test all CRUD operations, permissions, responses

### For Services:
- **Unit**: Test business logic with mocked dependencies

## Test Data Setup & Factory Requirements

### Factory Creation (REQUIRED)
- **Every model MUST have a corresponding factory** in `database/factories/`
- **Use Faker for realistic, dynamic data** to avoid unique constraint conflicts
- **Handle unique fields properly** with sequences or unique() method
- **Create factories before writing tests**

### Test Data Best Practices
- Use faker for realistic test data
- Create helper methods for common setup
- Use explicit permissions and roles
- Avoid global seeders or hardcoded data
- **ALL Feature tests MUST use RefreshDatabase trait**

### Factory Example:
```php
<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExampleModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company,
            'email' => $this->faker->unique()->safeEmail,
            'slug' => $this->faker->unique()->slug,
            'is_active' => $this->faker->boolean(80),
            'user_id' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
```

## PHPUnit Modern Syntax Requirements
- **Use attributes instead of docblock annotations**: `#[Test]` not `/** @test */`
- **Import attributes**: `use PHPUnit\Framework\Attributes\Test;`
- **Other useful attributes**: `#[DataProvider]`, `#[Depends]`, `#[Group]`
- **Method naming**: Use descriptive method names like `test_model_has_correct_fillable_attributes()`
- **Use ReflectionClass instead of ReflectionMethod**: For method introspection, use `$reflection = new \ReflectionClass($object); $method = $reflection->getMethod('methodName');` instead of `new \ReflectionMethod($object, 'methodName')` to avoid deprecation warnings

## Test Class Structure Examples

### Unit Test Example:
```php
<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ExampleRequest;
use Illuminate\Foundation\Http\FormRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleRequestTest extends TestCase
{
    private ExampleRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ExampleRequest();
    }

    #[Test]
    public function validation_rules_are_correctly_defined()
    {
        $rules = $this->request->rules();
        
        $this->assertArrayHasKey('name', $rules);
        $this->assertStringContainsString('required', $rules['name']);
    }

    #[Test]
    public function request_extends_form_request()
    {
        $this->assertInstanceOf(FormRequest::class, $this->request);
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
}
```

### Feature Test Example:
```php
<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ExampleRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $validData = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ];

        $request = new ExampleRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $invalidData = [];

        $request = new ExampleRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
    }
}
```

## Authentication and Authorization in Tests

### Backpack Admin Tests Setup
For admin Feature tests that test HTTP requests, you need proper authentication setup:

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

protected function setUp(): void
{
    parent::setUp();
    
    $this->user = User::factory()->create();
    
    // Create necessary permissions for testing
    $this->createRequiredPermissions();
    
    // Define test routes if testing HTTP behavior
    Route::post('/admin/resource', function (ResourceRequest $request) {
        return response()->json(['success' => true]);
    })->middleware('web');
}

private function createRequiredPermissions(): void
{
    $permissions = [
        'can_create_edit_user',
        'can_create_edit_invoice', 
        'can_create_edit_client',
        'can_create_edit_supplier',
        'can_create_edit_expense',
        'can_create_edit_tax',
        'can_create_edit_bank',
        'can_create_edit_payment_method',
        'can_create_edit_product',
        'can_create_edit_command',
        'can_create_edit_cron_task',
        'can_create_edit_status',
        'can_configure_system',
        'backpack.access',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission, 
            'guard_name' => 'backpack'
        ]);
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

### Test HTTP Requests with Authentication
```php
public function test_authenticated_request(): void
{
    $this->withoutMiddleware(); // Bypass Backpack middleware in tests
    $this->actingAs($this->user, 'backpack'); // Use backpack guard

    $data = ['name' => 'Test Data'];
    
    $response = $this->postJson('/admin/resource', $data); // Use postJson for JSON responses
    
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
}

public function test_unauthenticated_request(): void
{
    $data = ['name' => 'Test Data'];
    
    $response = $this->postJson('/admin/resource', $data);
    
    $response->assertStatus(403); // Expect 403 for unauthenticated in admin
}
```

### Key Authentication Points:
1. **Always use 'backpack' guard** for admin tests: `$this->actingAs($user, 'backpack')`
2. **Create all required permissions** in setUp() method
3. **Use withoutMiddleware()** to bypass Backpack middleware complications
4. **Use postJson/putJson** instead of post/put for proper JSON validation responses
5. **Expect 403** for unauthenticated admin requests (not 401)
6. **Always assign permissions to test users** for the 'backpack' guard

## Assertions to Include
- Test success and failure scenarios
- Test edge cases and boundary values
- Test permissions and authorization
- Test validation messages and error handling
- Test response formats and status codes

## Factory Requirements
- Create factories for all models that need testing
- Match database schema exactly
- Use realistic test data with Faker
- Include factory states for different scenarios
- Example factory structure:

```php
<?php

namespace Database\Factories;

use App\Models\ExampleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExampleModelFactory extends Factory
{
    protected $model = ExampleModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'active' => $this->faker->boolean(85),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }
}
```

## Common Test Patterns to Follow
1. **Always use RefreshDatabase for Feature tests**
2. **Mock external dependencies in Unit tests**
3. **Test both success and failure paths**
4. **Use descriptive test method names**
5. **Group related assertions logically**
6. **Test edge cases and boundary conditions**
7. **Verify error messages and status codes**
8. **Test with different user permissions when applicable**

## Mockery Syntax and Best Practices

### Correct Mockery Syntax (Laravel 12 + Mockery 1.6+)
```php
// ✅ CORRECT - Use string with full namespace
$mockUser = Mockery::mock('App\Models\User');
$mockProduct = Mockery::mock('App\Models\Product');

// ❌ WRONG - These syntaxes cause errors
$mockUser = Mockery::mock([User::class]);        // Array syntax - causes constructor errors
$mockUser = Mockery::mock("User::class");        // Invalid class name characters
$mockUser = Mockery::mock(User::class);          // String constants may cause warnings
```

### Mocking Eloquent Model Properties
For Eloquent models that use `$model->property` syntax (like `$user->id`):
```php
// ✅ CORRECT - Mock getAttribute() method (Eloquent uses this internally)
$mockUser = Mockery::mock('App\Models\User');
$mockUser->shouldReceive('getAttribute')->with('id')->andReturn(123);

// ❌ WRONG - Direct property assignment fails in mocks
$mockUser->id = 123;  // Triggers setAttribute() and causes errors
```

### Mocking Eloquent Model Methods
```php
$mockUser = Mockery::mock('App\Models\User');
$mockUser->shouldReceive('hasPermissionTo')->with('permission_name')->andReturn(true);
$mockUser->shouldReceive('hasRole')->with('role_name')->andReturn(false);
```

## Testing Strategy: Unit vs Feature Tests

### For Policies (Business Logic)
**Use BOTH Unit AND Feature tests:**

**Unit Tests (tests/Unit/Policies/):**
- ✅ Test pure business logic without database
- ✅ Fast execution (mocked dependencies)
- ✅ Focus on logical branches and edge cases
- ✅ Example: Testing authorization rules, ownership checks

```php
#[Test]
public function view_returns_true_for_owner(): void
{
    $userId = 123;
    
    $mockUser = Mockery::mock('App\Models\User');
    $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
    
    $mockProduct = Mockery::mock('App\Models\Product');
    $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($userId);
    
    $result = $this->policy->view($mockUser, $mockProduct);
    
    $this->assertTrue($result);
}
```

**Feature Tests (tests/Feature/Policies/):**
- ✅ Test end-to-end authorization flows
- ✅ Real database and user interactions
- ✅ Integration with auth system

### For Repositories (Data Layer)
**Use ONLY Feature tests:**

**Why NOT Unit tests for Repositories:**
- ❌ Complex Eloquent static method mocking (`Client::where()`, `Client::create()`)
- ❌ `overload:` mockery is overly complex for simple repositories
- ❌ Repositories are mostly thin wrappers around Eloquent
- ❌ Integration testing is more valuable than isolated unit testing

**Feature Tests (tests/Feature/Repositories/):**
- ✅ Test real database interactions
- ✅ Verify actual SQL queries work
- ✅ Test data integrity and relationships
- ✅ More reliable than complex mocking

```php
#[Test]
public function find_by_id_returns_client_when_belongs_to_current_user(): void
{
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    
    $this->actingAs($user);
    
    $result = $this->repository->findById($client->id);
    
    $this->assertInstanceOf(Client::class, $result);
    $this->assertEquals($client->id, $result->id);
}
```

### CRITICAL: Unit Test Isolation Rule
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

### Decision Matrix

| Component Type | Unit Tests | Feature Tests | Reason |
|---------------|-----------|---------------|---------|
| **Policies** | ✅ Yes | ✅ Yes | Business logic + Integration |
| **Repositories** | ❌ No | ✅ Yes | Simple wrappers, integration more important |
| **Models** | ✅ Yes | ✅ Yes | Structure + Relationships |
| **Requests** | ✅ Yes | ✅ Yes | Validation rules + HTTP behavior |
| **Controllers** | ❌ No | ✅ Yes | HTTP integration testing only |
| **Services** | ✅ Yes | ❌ No | Pure business logic |
````
