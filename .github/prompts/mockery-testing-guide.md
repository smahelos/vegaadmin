---
mode: 'reference'
description: 'Comprehensive guide for Mockery syntax and testing strategies'
---

# Mockery & Testing Strategy Guide

## 🚀 Quick Reference

### Correct Mockery Syntax (Laravel 12 + Mockery 1.6+)
```php
// ✅ CORRECT
$mock = Mockery::mock('App\Models\User');
$mock = Mockery::mock('App\Models\Product');

// ❌ WRONG - Causes errors
$mock = Mockery::mock([User::class]);        // Constructor errors
$mock = Mockery::mock("User::class");        // Invalid class name
$mock = Mockery::mock(User::class);          // May cause warnings
```

### Eloquent Property Mocking
```php
// ✅ For $user->id access
$mockUser->shouldReceive('getAttribute')->with('id')->andReturn(123);

// ✅ For permission/role methods
$mockUser->shouldReceive('hasPermissionTo')->with('permission')->andReturn(true);
$mockUser->shouldReceive('hasRole')->with('role')->andReturn(false);
```

## 🎯 Testing Strategy Decision Tree

```
Is it business logic?
├── YES → Unit tests (with mocking)
└── NO → Feature tests (with database)

Does it use many Eloquent static calls?
├── YES → Feature tests only
└── NO → Consider unit tests

Is mocking complex (>10 lines setup)?
├── YES → Skip unit tests, use feature tests
└── NO → Unit tests OK
```

## 📊 Component Testing Matrix

| Component | Unit Tests | Feature Tests | Primary Focus |
|-----------|-----------|---------------|---------------|
| **Policies** | ✅ Required | ✅ Recommended | Business logic + Integration |
| **Repositories** | ❌ Skip | ✅ Required | Database operations |
| **Services** | ✅ Required | ✅ Optional | Business logic |
| **Models** | ✅ Recommended | ✅ Recommended | Structure + Relationships |
| **Requests** | ✅ Recommended | ✅ Required | Validation + HTTP |
| **Controllers** | ❌ Skip | ✅ Required | HTTP workflows |

## 🔧 Example Implementations

### Policy Unit Test (Recommended)
```php
<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\Product;
use App\Policies\ProductPolicy;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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

        $mockUser = Mockery::mock('App\Models\User');
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('hasPermissionTo')->never();
        $mockUser->shouldReceive('hasRole')->never();

        $mockProduct = Mockery::mock('App\Models\Product');
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($userId);
        
        $result = $this->policy->view($mockUser, $mockProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_returns_true_for_admin_permission(): void
    {
        $mockUser = Mockery::mock('App\Models\User');
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(true);
        $mockUser->shouldReceive('hasRole')->never();
        
        $result = $this->policy->viewAny($mockUser);
        
        $this->assertTrue($result);
    }
}
```

### Repository Feature Test (Recommended)
```php
<?php

namespace Tests\Feature\Repositories;

use App\Models\Client;
use App\Models\User;
use App\Repositories\ClientRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
        $otherClient = Client::factory()->create(); // Different user
        
        $this->actingAs($user);
        
        $result = $this->repository->findById($client->id);
        $nullResult = $this->repository->findById($otherClient->id);
        
        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($client->id, $result->id);
        $this->assertNull($nullResult); // Can't access other user's client
    }
}
```

## 🚫 Anti-Patterns to Avoid

### Don't: Complex Repository Unit Tests
```php
// ❌ TOO COMPLEX - Skip unit tests for this
$mockBuilder = Mockery::mock('Illuminate\Database\Eloquent\Builder');
$mockBuilder->shouldReceive('where')->with('user_id', 1)->andReturn($mockBuilder);
$mockBuilder->shouldReceive('where')->with('id', 123)->andReturn($mockBuilder);
$mockBuilder->shouldReceive('first')->andReturn($mockClient);

Client::shouldReceive('where')->with('id', 123)->andReturn($mockBuilder);
```

### Don't: Direct Property Assignment on Mocks
```php
// ❌ WRONG - Triggers setAttribute() errors
$mockUser = Mockery::mock('App\Models\User');
$mockUser->id = 123; // This fails!

// ✅ CORRECT - Use getAttribute mocking
$mockUser->shouldReceive('getAttribute')->with('id')->andReturn(123);
```

## 🎯 When to Choose Each Test Type

### Use Unit Tests When:
- Testing pure business logic
- No database dependencies
- Fast execution needed
- Testing edge cases and conditions
- Mocking setup is simple (<5 lines)

### Use Feature Tests When:
- Testing database interactions
- Testing HTTP workflows
- Integration between components
- Real-world scenarios
- Unit test mocking would be complex

### Skip Unit Tests When:
- Class is primarily Eloquent wrapper
- Mocking requires >10 lines of setup
- Feature tests already provide coverage
- Business logic is minimal

## 📝 Best Practices Summary

1. **Mockery Syntax**: Always use string namespace `'App\Models\Class'`
2. **Eloquent Properties**: Mock via `getAttribute()` method
3. **Policies**: Unit + Feature tests for complete coverage
4. **Repositories**: Feature tests only (database integration)
5. **Complexity Check**: If mocking is complex, prefer feature tests
6. **Teardown**: Always call `Mockery::close()` in tearDown()
7. **Assertions**: Test both success and failure scenarios
8. **Performance**: Unit tests for speed, feature tests for confidence

## 🔍 Troubleshooting Common Issues

### "Call to a member function connection() on null"
- **Problem**: Trying to unit test Eloquent static methods
- **Solution**: Use feature tests with RefreshDatabase instead

### "Class name contains invalid characters"
- **Problem**: Using `"Class::class"` syntax
- **Solution**: Use `'App\Models\Class'` string syntax

### "No expectations specified for getAttribute"
- **Problem**: Accessing `$model->property` without mocking `getAttribute`
- **Solution**: Add `shouldReceive('getAttribute')->with('property')`

### Constructor errors with array syntax
- **Problem**: Using `Mockery::mock([Class::class])`
- **Solution**: Use `Mockery::mock('App\Models\Class')` string syntax
