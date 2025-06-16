---
mode: 'agent'
description: 'Prompt for creating comprehensive tests for any component'
---

# Create Tests for Component

When creating tests for any component, follow this comprehensive approach:

## Determine Test Type
1. **Models**: Split into Unit (structure, traits) and Feature (relationships, DB)
2. **Request Classes**: Split into Unit (rules, messages) and Feature (HTTP validation)
3. **Controllers**: Feature tests only (test HTTP workflows)
4. **Services**: Unit tests with mocked dependencies
5. **Repositories**: Feature tests with database interactions
6. **Middleware**: Feature tests with HTTP context

## Unit Test Template
```php
<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleModelTest extends TestCase
{
    #[Test]
    public function model_has_correct_fillable_attributes()
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
    use RefreshDatabase, WithFaker;

    #[Test]
    public function model_can_be_created_with_factory()
    {
        // Test real-world scenarios with full context
        // Use RefreshDatabase trait
        // Create explicit test data with factories
        // Test complete workflows and integrations
    }
}
```

## Required Test Patterns

### For Request Classes:
- **Unit**: Test rules(), authorize(), messages(), attributes()
- **Feature**: Test actual validation with HTTP context

### For Models:
- **Unit**: Test fillable, casts, accessors, mutators, traits
- **Feature**: Test relationships, scopes, database interactions

### For Controllers:
- **Feature**: Test all CRUD operations, permissions, responses

### For Services:
- **Unit**: Test business logic with mocked dependencies

## Test Data Setup
- Use faker for realistic test data
- Create helper methods for common setup
- Use explicit permissions and roles
- Avoid global seeders or hardcoded data

## PHPUnit Modern Syntax Requirements
- **Use attributes instead of docblock annotations**: `#[Test]` not `/** @test */`
- **Import attributes**: `use PHPUnit\Framework\Attributes\Test;`
- **Other useful attributes**: `#[DataProvider]`, `#[Depends]`, `#[Group]`
- **Method naming**: Use descriptive method names like `test_model_has_correct_fillable_attributes()`

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
