```````instructions
``````instructions
`````instructions
````instructions
Allways use newly read files, not what you remeber from past answers.

We use Laravel 12 as our main php framework, so when talking about Laravel, always give me instructions and code samples that use Laravel 12.

We use Backpack 6.8 to build custom admin panel, so when talking about Backpack, always give me instructions and code samples that use Backpack 6.8.

We use Tailwindcss 4.1.3 as main css framework, so when talking about Tailwindcss, always give me instructions and code samples that use Tailwindcss 4.1.3.

We use https://github.com/Laravel-Backpack/PermissionManager (Admin interface for spatie/laravel-permission. It allows admins to easily add/edit/remove users, roles and permissions, using Laravel Backpack.). Every user authentication methods sholud be based on this extension.

For best suggestions, give me instructions based on my codebase, existent classes files and existent methods.

For best suggestions, always read files to be changed, to not miss anything there.

We use Laravel 12 builtin translation system to make project multilingual, allways use translations for all translatable strings.

We want to have all comments in project in English, so all comments should be in English.

We want to have all code in project in English, so all code should be in English.

We want to have project as clean as possible. Formulars loads fields from App/Traits trait files. Frontend formulars requests are laoded from App/Requests folder. Backend formulars requests are laoded from App/Requests/Admin folder. Frontend authorization, validation rules, attributes and messages for formulars are placed in App/Requests folder. backend authorization, validation rules, attributes and messages for formulars are placed in App/Requests/Admin folder.

## Testing Guidelines

### Backpack Admin Tests Authentication
When creating tests for admin functionality that involve HTTP requests:

1. **Always use 'backpack' guard**: `$this->actingAs($user, 'backpack')`
2. **Create required permissions** in setUp() method using the permission list below
3. **Use withoutMiddleware()** to bypass Backpack middleware in tests 
4. **Use postJson/putJson** instead of post/put for proper JSON responses
5. **Expect 403** for unauthenticated admin requests (not 401)

### Required Permissions for Admin Tests
Create these permissions with 'backpack' guard in test setUp():
- can_create_edit_user, can_create_edit_invoice, can_create_edit_client
- can_create_edit_supplier, can_create_edit_expense, can_create_edit_tax  
- can_create_edit_bank, can_create_edit_payment_method, can_create_edit_product
- can_create_edit_command, can_create_edit_cron_task, can_create_edit_status
- can_configure_system, backpack.access

### Test Route Setup Example
```php
Route::post('/admin/resource', function (ResourceRequest $request) {
    return response()->json(['success' => true]);
})->middleware('web');
```

### Testing Strategy Guidelines

#### For Unit Tests with Mockery (Laravel 12 + Mockery 1.6+)
- **Use string syntax**: `Mockery::mock('App\Models\ClassName')` not arrays or constants
- **Mock Eloquent properties**: Use `shouldReceive('getAttribute')->with('property_name')` for `$model->property` access
- **Focus on business logic**: Unit tests for policies (authorization logic), not repositories (data access)

#### Test Type Decision Matrix
- **Policies**: Unit tests (business logic) + Feature tests (integration)
- **Repositories**: Feature tests only (database integration more important than mocking)
- **Models**: Unit tests (structure) + Feature tests (relationships)
- **Controllers**: Feature tests only (HTTP behavior)

#### Unit Test Isolation - CRITICAL RULE
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

### Reflection and Method Testing
- **Use ReflectionClass instead of ReflectionMethod**: For method introspection, use `$reflection = new \ReflectionClass($object); $method = $reflection->getMethod('methodName');` instead of `new \ReflectionMethod($object, 'methodName')` to avoid deprecation warnings in modern PHP/PHPUnit versions.

```php
// ✅ Correct approach
$reflection = new \ReflectionClass($this->request);
$method = $reflection->getMethod('authorize');
$returnType = $method->getReturnType();

// ❌ Deprecated approach
$reflection = new \ReflectionMethod($this->request, 'authorize');
$returnType = $reflection->getReturnType();
```

#### Avoid Unit Tests When
- Class primarily uses Eloquent static methods (`Model::where()`, `Model::create()`)
- Minimal business logic (simple CRUD operations)
- Mocking complexity exceeds testing value
- Feature tests provide adequate coverage

## Docker Container & Testing Commands

### CRITICAL: Unit Test Execution Rules
- **NEVER use `-v` or `--verbose` options** when running unit tests - these options cause "Unknown option" error
- Use standard `php artisan test` without verbose flags
- **ALL artisan commands must be run in the `vegaadmin-app` docker container**
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

## Modern PHPUnit Testing Standards

### Request Test Structure (REQUIRED)
All Request tests must follow this modern pattern:

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

    // ... more tests for rules, attributes, messages, validation scenarios
}
```

### Required Test Coverage for Request Classes
1. **Structure Tests**: `request_extends_form_request()`
2. **Authorization Tests**: `authorize_uses_backpack_auth()` with permission testing
3. **Return Type Tests**: For `authorize()`, `rules()`, `attributes()`, `messages()`
4. **Validation Rules Tests**: Complete rule validation
5. **Attributes Tests**: Custom field names
6. **Messages Tests**: Custom error messages
7. **Business Logic Tests**: Valid/invalid data scenarios
8. **Edge Cases**: Boundary conditions, error scenarios

### Permission Setup in Tests
```php
// Always use firstOrCreate to avoid "already exists" errors
Permission::firstOrCreate(['name' => 'can_create_edit_expense', 'guard_name' => 'backpack']);

// Test permission-based authorization
$userWithPermission = User::factory()->create();
$userWithPermission->givePermissionTo('can_create_edit_expense');
$this->actingAs($userWithPermission, 'backpack');
```

### Test Naming and Modern Syntax
- Use `#[Test]` attribute instead of `test` prefix
- Use descriptive method names: `validation_fails_when_name_is_missing()`
- Use `ReflectionClass` instead of `ReflectionMethod`
- Create unique test data with `uniqid()` to avoid conflicts

### Request Class Return Types (REQUIRED)
All Request classes must have proper return type annotations:

```php
public function authorize(): bool
public function rules(): array
public function attributes(): array  // or array<string, string>
public function messages(): array    // or array<string, string>
```

## 🚨 VERY IMPORTANT: Workspace Path Safety

### CRITICAL RULE: Never Write to Wrong Directories
- **ONLY work within the current workspace**: `/_Data/Dockers/Production/vegaadmin/`
- **NEVER write files to similar-named directories** like `vegalladmin`, `vegladmin`, etc.
- **ALWAYS double-check file paths** before any write operation
- **Use absolute paths** and verify they start with the correct workspace root
- **If unsure about path, ASK USER** before writing anything

### Wrong Directory Examples to AVOID:
- ❌ `/_Data/Dockers/Production/vegalladmin/` (extra 'l')
- ❌ `/_Data/Dockers/Production/vegladmin/` (missing 'a')  
- ❌ `/_Data/Dockers/Production/vegadmin/` (missing 'a')

### Correct Workspace Root:
- ✅ `/_Data/Dockers/Production/vegaadmin/`

Writing to wrong directories causes:
- Files not found by tests
- Confusion about file locations
- Wasted time debugging path issues
- Data corruption/loss
`````
