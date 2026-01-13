# Quick Reference: Auth, Mockery, Test Patterns

## Autentizace a prostředí (Quickref)

### Service Test s DB (transaction-safe)
```php
use Tests\Traits\RefreshDatabaseWithData;
class ServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;
    private Service $service;
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(Service::class);
        $this->user = User::create([
            'name' => 'Test User ' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Permission::firstOrCreate([
            'name' => 'frontend.can_create_edit_invoice',
            'guard_name' => 'web'
        ]);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
    }
    #[Test]
    public function service_method_works_with_authenticated_user(): void
    {
        Auth::login($this->user);
        $result = $this->service->someMethod();
        $this->assertNotNull($result);
    }
}
```

### Admin Controller Test (Backpack)
```php
use Tests\Traits\CreatesAdminTestEnvironment;
class AdminControllerTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        // Provides $this->adminUser and $this->regularUser
    }
    #[Test]
    public function admin_can_access_resource(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->get('/admin/resource');
        $response->assertOk();
    }
}
```

### Frontend Controller Test
```php
use Tests\Traits\CreatesFrontendTestEnvironment;
class FrontendTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        $this->user = User::factory()->create();
    }
    #[Test]
    public function user_can_access_frontend_resource(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get('/frontend/resource');
        $response->assertOk();
    }
}
```

### Unauthenticated Test
```php
public function test_unauthenticated(): void
{
    $response = $this->get('/protected/resource');
    $response->assertRedirect(); // 302 redirect to login
}
```

## Mockery & Unit Test Quickref

### Správná syntaxe Mockery (Laravel 12 + Mockery 1.6+)
```php
$mock = Mockery::mock('App\\Models\\User');
$mock->shouldReceive('getAttribute')->with('id')->andReturn(123);
$mock->shouldReceive('hasPermissionTo')->with('permission')->andReturn(true);
$mock->shouldReceive('hasRole')->with('role')->andReturn(false);
```

### Policy Unit Test (doporučený vzor)
```php
class ProductPolicyTest extends TestCase
{
    private ProductPolicy $policy;
    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProductPolicy();
    }
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    #[Test]
    public function view_returns_true_for_owner(): void
    {
        $userId = 123;
        $mockUser = Mockery::mock('App\\Models\\User');
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockProduct = Mockery::mock('App\\Models\\Product');
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($userId);
        $result = $this->policy->view($mockUser, $mockProduct);
        $this->assertTrue($result);
    }
}
```

### Repository Feature Test (doporučený vzor)
```php
class ClientRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private ClientRepository $repository;
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ClientRepository();
    }
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
}
```

## Anti-patterns & Troubleshooting

- ❌ Nepoužívej složité unit testy pro Eloquent repozitáře (raději feature testy)
- ❌ Nepřiřazuj přímo vlastnosti na Mockery objekty (používej getAttribute)
- ❌ Nikdy netestuj Eloquent statické metody v unit testech (používej feature testy)
- ❌ Nikdy nevytvářej všechny práva ručně v každém testu (používej helpery nebo minimální setup)
- ❌ Nikdy nepoužívej `$this->actingAs($user)` bez správného guardu
- ❌ Nikdy nečekej 401 pro web aplikace, vždy 302 redirect

---
---
mode: 'agent'
description: 'Prompt for creating comprehensive tests for any component'
---



# Quick Testing & Docker Reference

## 🚨 CRITICAL RULES - ALWAYS FOLLOW

### Docker Container
- **ALWAYS** use `docker exec INVOICE-php-fpm php artisan [command]`
- **NEVER** run artisan commands directly on host
- **NEVER** use `-v` or `--verbose` with test commands

### Modern PHPUnit
- **ALWAYS** use `#[Test]` attribute (not `test` prefix)
- **ALWAYS** use `ReflectionClass` (not `ReflectionMethod`)
- **ALWAYS** test return types with reflection
- **ALWAYS** create unique test data with `uniqid()`

### Unit Test Isolation - CRITICAL
- **Unit tests MUST NOT depend on Laravel framework** (database, container, boot methods)
- **Avoid `new Model()` in Unit tests** - triggers boot methods requiring database
- **Move Laravel-dependent tests to Feature tests**: relationships, database ops, auth
- **Unit tests for pure business logic only**: calculations, transformations

### Request Test Structure
1. `request_extends_form_request()`
2. `authorize_uses_backpack_auth()` (test false/true scenarios)
3. `authorize_method_has_correct_return_type()`
4. `rules_returns_correct_validation_rules()`
5. `rules_method_has_correct_return_type()`
6. `attributes_returns_correct_custom_attributes()`
7. `attributes_method_has_correct_return_type()`
8. `messages_returns_correct_custom_messages()`
9. `messages_method_has_correct_return_type()`
10. `validation_passes_with_valid_data()`
11. `validation_fails_when_required_fields_missing()`
12. Additional business logic tests and edge cases

### Permission Setup
```php
// Always use firstOrCreate to avoid conflicts
Permission::firstOrCreate(['name' => 'permission_name', 'guard_name' => 'backpack']);

// Test authorization properly
$user = User::factory()->create();
$user->givePermissionTo('permission_name');
$this->actingAs($user, 'backpack');
```

### Required Return Types in Request Classes
```php
public function authorize(): bool
public function rules(): array
public function attributes(): array
public function messages(): array
```

## ✅ Correct Commands
```bash
docker exec INVOICE-php-fpm php artisan test tests/Unit/Http/Requests/Admin/SomeTest.php
docker exec INVOICE-php-fpm php artisan test tests/Unit/Http/Requests/Admin/
docker exec INVOICE-php-fpm php artisan test --filter=RequestTest
```

## ❌ Wrong Commands (Will Fail)
```bash
# Missing docker container
php artisan test file.php

# Using verbose options
docker exec INVOICE-php-fpm php artisan test file.php -v
docker exec INVOICE-php-fpm php artisan test file.php --verbose
```

# Create Tests for Component

## 🚨 CRITICAL: Test Patterns, Authentication & Naming

### 1. Rozhodovací matice: Jaký vzor použít?

| Typ testu                | Database trait              | Environment trait                | Autentizace                        | Hlavní helper/vzor                |
|--------------------------|-----------------------------|----------------------------------|-------------------------------------|-----------------------------------|
| **Service + DB**         | RefreshDatabaseWithData     | žádný (ručně práva)              | Auth::login($user)                  | Viz Service Test Template         |
| **Admin Controller**     | RefreshDatabase             | CreatesAdminTestEnvironment      | $this->actingAs($this->adminUser, 'backpack') | Viz Admin Controller Template     |
| **Frontend Controller**  | RefreshDatabase             | CreatesFrontendTestEnvironment   | $this->actingAs($user, 'web')       | Viz Frontend Controller Template  |
| **Unit Test**            | žádný                      | žádný                            | žádný                              | Viz Unit Test Template            |

### 2. Anti-duplikační pravidla
- **Nikdy netvoř více testů pro stejnou třídu** (pokud netestují zásadně odlišné aspekty)
- **Vždy hledej existující testy** před tvorbou nových: `find tests/ -name "*ComponentName*Test.php"`
- **Přednost má jeden komplexní test před více malými**
- **Odstraň zastaralé testy** při tvorbě lepších verzí

### 3. Pojmenování a organizace
- **Feature testy**: vždy obsahují "Feature" v názvu (např. `ClientControllerFeatureTest.php`)
- **Unit testy**: nikdy "Unit" v názvu (např. `ClientListTest.php`)
- **Žádné duplikáty**: nikdy nevytvářej `ClassName.php` a `ClassNameUnitTest.php` současně
- **Popisné názvy**: jasně vystihují, co testují

### 4. Helpery a prostředí
- **Admin/Backpack**: vždy `CreatesAdminTestEnvironment` (automaticky práva, role, uživatelé)
- **Frontend**: vždy `CreatesFrontendTestEnvironment`
- **Service**: pouze minimální práva ručně, nikdy ne helper pro admin prostředí

### 5. Autentizace
- **Admin testy**: `$this->actingAs($this->adminUser, 'backpack')`
- **Frontend testy**: `$this->actingAs($user, 'web')`
- **Service testy**: `Auth::login($user)`
- **Vždy správný guard!**

---

## Šablony a příklady

### Service Test (business logic, DB, práva ručně)
```php
class InvoiceServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;
    private InvoiceService $service;
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvoiceService::class);
        $this->user = User::create([
            'name' => 'Test User ' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        Permission::firstOrCreate([
            'name' => 'frontend.can_create_edit_invoice',
            'guard_name' => 'web'
        ]);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
    }
    #[Test]
    public function service_method_works_with_authenticated_user(): void
    {
        Auth::login($this->user);
        $result = $this->service->someMethod();
        $this->assertNotNull($result);
    }
}
```

### Admin Controller Test (Backpack, prostředí helper)
```php
class PageCrudControllerTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
    }
    #[Test]
    public function admin_can_access_page_list(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $response = $this->get('/admin/page');
        $response->assertOk();
    }
    #[Test]
    public function unauthenticated_user_redirected(): void
    {
        $response = $this->get('/admin/page');
        $this->assertEquals(302, $response->getStatusCode());
    }
}
```

### Frontend Controller Test (prostředí helper)
```php
class FrontendControllerTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        $this->user = User::factory()->create();
    }
    #[Test]
    public function user_can_access_frontend_resource(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get('/frontend/resource');
        $response->assertOk();
    }
}
```

### Unit Test (čistá business logika, žádné DB)
```php
class ExampleModelTest extends TestCase
{
    #[Test]
    public function model_has_correct_fillable_attributes(): void
    {
        // Test class structure and methods without external dependencies
    }
}
```

---

## Další klíčové principy

- **Všechny Feature testy musí používat RefreshDatabase**
- **Všechny Service testy s DB musí používat RefreshDatabaseWithData**
- **Všechny Request testy musí mít explicitní návratové typy**
- **Všechny testy musí používat #[Test] a ReflectionClass**
- **Všechny modely musí mít factory**
- **Vždy testuj úspěch i selhání, okrajové případy, práva, status kódy**
- **Nikdy nepoužívej deprecated metody, vždy oprav warningy**

---

## Odkazy na hlavní instrukce

- `.github/prompts/instructions/testing.md` – Kompletní testovací standardy
- `.github/prompts/test-authentication.prompt.md` – Vzory autentizace a prostředí
- `.github/prompts/lessons-learned/backpack-crud-testing-refactoring.md` – Lessons learned

---

## Nejčastější chyby a jak se jim vyhnout

- ❌ Ruční vytváření všech práv v každém testu (používej helpery nebo minimální setup)
- ❌ Špatný guard v actingAs (vždy 'backpack' pro admin, 'web' pro frontend)
- ❌ Duplicitní testy pro stejnou třídu
- ❌ Chybějící factory nebo špatná data ve factory
- ❌ Deprecated metody nebo warningy v testech

---

## Vzor pro Request Unit Test (moderní, povinný)
```php
class SomeRequestTest extends TestCase
{
    private SomeRequest $request;
    protected function setUp(): void
    {
        parent::setUp();
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
        $this->assertFalse($this->request->authorize());
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
    // ... další testy pro rules, attributes, messages, valid/invalid data ...
}
```

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
- **ALL artisan commands MUST be run in the `INVOICE-php-fpm` docker container**
- Use: `docker exec INVOICE-php-fpm php artisan test ...`
- Never run artisan commands directly on host system

### Testing Commands - NEVER Use Verbose Options
- **NEVER use `-v` or `--verbose` options** when running unit tests
- These options cause "Unknown option" error in our testing environment
- Use standard `php artisan test` without verbose flags

### Correct Test Execution Examples
```bash
# ✅ Correct
docker exec INVOICE-php-fpm php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php
docker exec INVOICE-php-fpm php artisan test tests/Unit/Http/Requests/Admin/
docker exec INVOICE-php-fpm php artisan test --filter=RequestTest

# ❌ Wrong - will cause "Unknown option" error
docker exec INVOICE-php-fpm php artisan test file.php -v
docker exec INVOICE-php-fpm php artisan test file.php --verbose

# ❌ Wrong - missing docker container
php artisan test file.php
```

## 🚨 CRITICAL: Transaction Conflicts Resolution 

### Service Tests Database Pattern (FINALIZED)

**For Service tests that interact with database and permissions:**

```php
<?php

namespace Tests\Feature\Services;

use Tests\Traits\RefreshDatabaseWithData; // ✅ CRITICAL for Service tests
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData; // Prevents "There is already an active transaction" errors

    private Service $service;
    private User $user;
    
    // Optional: Disable seeding if test needs clean database
    protected bool $seedDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(Service::class);
        
        // Create test user with unique identifiers to prevent conflicts
        $this->user = User::create([
            'name' => 'Test User ' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Create specific permissions if needed
        Permission::firstOrCreate([
            'name' => 'frontend.can_create_edit_resource',
            'guard_name' => 'web'
        ]);
        $this->user->givePermissionTo('frontend.can_create_edit_resource');
    }
    
    #[Test]
    public function service_functionality_works(): void
    {
        $result = $this->service->someMethod($this->user);
        $this->assertNotNull($result);
    }
}
```

### RefreshDatabaseWithData Features:
- ✅ **Eliminates transaction conflicts** with Spatie Permission package
- ✅ **Prevents database locking** through DB::disconnect()
- ✅ **Complete test isolation** with fresh migrations for each test
- ✅ **Optional seeding** - set `$seedDatabase = false` to disable automatic seeding
- ✅ **Performance optimized** for Service test requirements

### Environment Traits for Complex Tests

**For comprehensive permission testing:**

#### Admin Tests (Backpack)
```php
<?php

namespace Tests\Feature\Http\Requests\Admin;

use Tests\Traits\CreatesAdminTestEnvironment;
use Tests\TestCase;

class AdminRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        // Provides $this->adminUser and $this->regularUser automatically
    }

    #[Test]
    public function admin_can_access_resource(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        // Test admin functionality
    }
}
```

#### Frontend Tests
```php
<?php

namespace Tests\Feature\Http\Requests;

use Tests\Traits\CreatesFrontendTestEnvironment;
use Tests\TestCase;

class FrontendRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        
        $this->user = User::factory()->create();
    }

    #[Test]
    public function user_can_access_frontend_resource(): void
    {
        $this->actingAs($this->user, 'web');
        // Test frontend functionality
    }
}
```
## 🚨 IMPORTANT: Code Quality First
**When tests fail due to missing return types or method signatures:**
1. **PREFER fixing the code** over changing tests
2. **Add explicit return types** to improve code quality and IDE support
3. **Implement missing methods** that should exist according to business logic
4. **Only modify tests** if the expectation is genuinely wrong
5. **Always test functionality** after any code changes
6. **Report any problematic code patterns** and suggest better solutions

## Test Pattern Selection Guide

### Database Trait Selection
| Test Type | Recommended Trait | Reason |
|-----------|------------------|---------|
| **Service tests** with database | `RefreshDatabaseWithData` | Prevents transaction conflicts |
| **HTTP Feature tests** | `RefreshDatabase` | Standard Laravel testing |
| **Unit tests** | None | No database needed |

### Environment Trait Selection
| Test Context | Recommended Trait | Provides |
|-------------|------------------|----------|
| **Admin/Backpack** | `CreatesAdminTestEnvironment` | Backpack permissions + admin users |
| **Frontend** | `CreatesFrontendTestEnvironment` | Frontend permissions + roles |
| **Simple Service** | None | Create minimal permissions manually |

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

### ✅ RECOMMENDED: Backpack Admin Tests with Helper Method
For admin Feature tests, use the elegant `setupBackpackAdminTest()` helper method:

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

protected function setUp(): void
{
    parent::setUp();
    
    // Create test user
    $this->user = User::factory()->create();
    
    // Create only specific permissions for this controller
    Permission::firstOrCreate(['name' => 'can_create_edit_resource', 'guard_name' => 'backpack']);
    Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
    
    // Create admin role and assign permissions
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
    $adminRole->givePermissionTo('can_create_edit_resource');
    $adminRole->givePermissionTo('backpack.access');
    $this->user->assignRole($adminRole);
}
```

### Test HTTP Requests with Authentication (UPDATED)
```php
public function test_authenticated_request(): void
{
    $this->setupBackpackAdminTest($this->user); // One-liner authentication!

    $data = ['name' => 'Test Data'];
    
    $response = $this->postJson('/admin/resource', $data);
    
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
}

public function test_unauthenticated_request(): void
{
    // No authentication = test unauthenticated access
    $data = ['name' => 'Test Data'];
    
    $response = $this->postJson('/admin/resource', $data);
    
    $response->assertStatus(302); // Redirect to login for admin
}
```

### Key Authentication Points (UPDATED):
1. **Use helper method**: `$this->setupBackpackAdminTest($user)` handles all menu permissions
2. **Create specific permissions**: Only what your controller actually needs
3. **Use 'backpack' guard**: All admin permissions must use 'backpack' guard  
4. **Expect 302**: Unauthenticated admin requests redirect to login
5. **Maintainable**: No dependency on menu permission changes

### ❌ DEPRECATED: Manual Permission Creation
**No longer recommended:** Creating all menu permissions manually in each test:
```php
// ❌ DEPRECATED - Creates maintenance burden
private function createRequiredPermissions(): void
{
    $permissions = [
        'can_create_edit_user', 'can_create_edit_invoice', // ... 15+ permissions
    ];
    // Complex setup code that breaks when menu changes...
}
```

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
1. **Always use RefreshDatabase or RefreshDatabaseWithData for Feature tests**
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
