---
mode: 'agent'
description: 'Testing standards and best practices for Laravel application'
---

# Testing Instructions and Standards

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

## Test Organization (CRITICAL NAMING CONVENTIONS)

### Test File Naming Standards
- **Feature Tests**: MUST include "Feature" in filename (e.g., `ClientControllerFeatureTest.php`)
- **Unit Tests**: MUST NOT include "Unit" in filename (e.g., `ClientListTest.php`, not `ClientListUnitTest.php`)
- **Avoid Duplicates**: Never create both `ClassName.php` and `ClassNameUnitTest.php` - use only `ClassName.php` for Unit tests
- **Be Descriptive**: Use meaningful names that clearly indicate what is being tested

### Test Types and Organization
- **Unit Tests**: `tests/Unit/` - Test individual classes without dependencies
- **Feature Tests**: `tests/Feature/` - Test application features with full context
- **Integration Tests**: Test interactions between components
- **No Duplicate Coverage**: Each component should have either Unit OR Feature tests, not both testing same functionality

### Directory Structure Guidelines
```
tests/
├── Unit/
│   ├── Http/Requests/MyRequestTest.php          ✅ Correct
│   ├── Http/Requests/MyRequestUnitTest.php      ❌ Wrong - never use Unit suffix
│   └── Livewire/ComponentTest.php               ✅ Correct
└── Feature/
    ├── Http/Controllers/MyControllerFeatureTest.php  ✅ Correct - has Feature suffix
    └── Livewire/ComponentFeatureTest.php             ✅ Correct - has Feature suffix
```

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

### RefreshDatabase Usage (REQUIRED)
- **ALL Feature tests MUST use `RefreshDatabase` trait**
- **NEVER use transactions or manual cleanup** - RefreshDatabase ensures clean state
- **RefreshDatabase migrates fresh database for each test**
- **Ensures test isolation and prevents data conflicts**
- **Required for all tests that interact with database (models, relationships, HTTP endpoints)**

```php
// ✅ CORRECT - Feature test with RefreshDatabase
class ModelFeatureTest extends TestCase
{
    use RefreshDatabase;  // REQUIRED for all Feature tests
    
    protected function setUp(): void
    {
        parent::setUp();
        // Setup test data using factories
    }
}
```

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

## Model Factory Guidelines (REQUIRED)

### Factory Creation Rules
- **Every model MUST have a corresponding factory** in `database/factories/`
- **Factories MUST generate realistic, valid test data**
- **Use Faker for dynamic content** to avoid unique constraint conflicts
- **Handle unique fields with sequences or Faker helpers**
- **Define all required fields** that have database constraints

### Factory Best Practices
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            // ✅ Use Faker for dynamic content
            'name' => $this->faker->unique()->company,
            'email' => $this->faker->unique()->safeEmail,
            
            // ✅ Handle unique constraints with sequences
            'slug' => $this->faker->unique()->slug,
            
            // ✅ Use realistic data types
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'is_active' => $this->faker->boolean(80), // 80% chance of true
            
            // ✅ Use foreign key references
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            
            // ✅ Handle nullable fields appropriately
            'description' => $this->faker->optional(0.7)->paragraph,
            
            // ✅ Use timestamps
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // ✅ Define useful states
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCustomName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
```

### Factory Usage in Tests
```php
// ✅ Basic factory usage
$model = Model::factory()->create();

// ✅ Create multiple models
$models = Model::factory()->count(5)->create();

// ✅ Override specific attributes
$model = Model::factory()->create(['name' => 'Custom Name']);

// ✅ Use factory states
$model = Model::factory()->inactive()->create();

// ✅ Create without persisting (for Unit tests)
$model = Model::factory()->make(); // Only in Unit tests when testing structure

// ✅ Create with relationships
$model = Model::factory()
    ->has(RelatedModel::factory()->count(3))
    ->create();
```

### Unique Constraint Handling
```php
// ✅ Use unique() for fields that must be unique
'email' => $this->faker->unique()->safeEmail,
'slug' => $this->faker->unique()->slug,

// ✅ Use sequence for predictable unique values
'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),

// ✅ Reset unique values in test setUp if needed
protected function setUp(): void
{
    parent::setUp();
    $this->faker->unique(true); // Reset unique values
}
```

## Livewire Component Testing Guidelines

### Authentication in Livewire Tests
When testing Livewire components that require user authentication:

1. **Use `$this->actingAs($user)` ONCE per test** - Sets global authentication for the entire test
2. **Use `Livewire::test()` DIRECTLY** - No need for `Livewire::actingAs($user)->test()` when global auth is set
3. **Global auth applies to ALL Livewire instances** in the same test

```php
#[Test]
public function livewire_component_requires_authentication(): void
{
    $this->actingAs($this->user); // ✅ Set global auth once
    
    // ✅ Correct - uses global auth
    $component = Livewire::test(MyComponent::class);
    
    // ❌ Wrong - redundant actingAs
    $component = Livewire::actingAs($this->user)->test(MyComponent::class);
}
```

### Accessing Livewire Component Data
- **Use `viewData('key')` for view variables** - Data passed to view from render() method
- **Use `get('property')` for component properties** - Public properties of the component class
- **NEVER use `assertSet()` for view data** - View data is not a component property

```php
#[Test]
public function component_passes_data_to_view(): void
{
    $component = Livewire::test(ProductListSelect::class);
    
    // ✅ Correct - access view data
    $products = $component->viewData('products');
    $hasData = $component->viewData('hasData');
    
    // ✅ Correct - access component properties
    $search = $component->get('search');
    $sortField = $component->get('sortField');
    
    // ❌ Wrong - view data is not a component property
    $component->assertSet('hasData', true);
}
```

### Testing Component Logic vs Database Queries
When Livewire tests fail:

1. **Test raw database queries first** - Verify data exists and queries work
2. **Test component behavior separately** - Isolate component logic issues
3. **Check authentication context** - Ensure user context is properly set
4. **Verify column existence** - Ensure searched columns exist in database

```php
#[Test]
public function search_functionality_works(): void
{
    $this->actingAs($this->user);
    
    $product = Product::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Product'
    ]);
    
    // ✅ Test raw query first
    $rawResults = Product::where('user_id', $this->user->id)
        ->where('name', 'like', '%Test%')
        ->get();
    $this->assertCount(1, $rawResults);
    
    // ✅ Test component behavior
    $component = Livewire::test(ProductListSelect::class)
        ->set('search', 'Test');
    
    $products = $component->viewData('products');
    $this->assertTrue($products->contains('id', $product->id));
}
```

### Common Livewire Testing Pitfalls

#### ❌ Wrong: Using non-existent database columns
```php
// This will fail if 'code' column doesn't exist
->orWhere('code', 'like', "%{$searchTerm}%")
```

#### ✅ Correct: Verify columns exist before searching
```php
// Only search existing columns
$query->where(function($q) use ($searchTerm) {
    $q->where('name', 'like', "%{$searchTerm}%")
      ->orWhere('description', 'like', "%{$searchTerm}%");
});
```

#### ❌ Wrong: Scope issues in closures
```php
->when($this->search, function ($query) {
    // $this->search may not be accessible in closure
    $query->where('name', 'like', "%{$this->search}%");
})
```

#### ✅ Correct: Pass variables to closure explicitly
```php
$searchTerm = $this->search;
->when($searchTerm, function ($query) use ($searchTerm) {
    $query->where('name', 'like', "%{$searchTerm}%");
})
```

## 🚨 CRITICAL: Preventing Duplicate Tests

### Anti-Duplication Rules
- **NEVER create multiple test files for the same class** unless they test fundamentally different aspects
- **CHECK existing tests** before creating new ones - use file search to find existing test coverage
- **PREFER comprehensive single test file** over multiple smaller files testing same functionality
- **REMOVE obsolete tests** when creating better versions

### Before Creating Any Test File
1. **Search for existing tests**: `find tests/ -name "*ComponentName*Test.php"`
2. **Check both Unit and Feature directories**
3. **Review existing test coverage** - don't duplicate functionality
4. **If updating existing tests, don't create new files** - enhance existing ones

### Duplicate Test Detection Commands
```bash
# Find potential duplicates by component name
find tests/ -name "*ClientList*Test.php"
find tests/ -name "*ProductSelect*Test.php" 
find tests/ -name "*Invoice*Test.php"

# Check for Unit suffix violations
find tests/Unit/ -name "*UnitTest.php"  # Should return empty

# Check for missing Feature suffix in Feature tests
find tests/Feature/ -name "*Test.php" | grep -v "FeatureTest.php"
```

### When You Find Duplicates
1. **Compare test coverage** - which file has better/more comprehensive tests?
2. **Keep the better version** - usually the one following current standards
3. **Merge useful tests** from the inferior version if any
4. **Delete the inferior version**
5. **Update documentation** if test organization changes
