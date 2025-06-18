<?php

namespace Tests\Unit\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
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
    public function policy_has_correct_class_structure(): void
    {
        $this->assertInstanceOf(ProductPolicy::class, $this->policy);
        
        // Check that policy uses HandlesAuthorization trait
        $traits = class_uses($this->policy);
        $this->assertContains(HandlesAuthorization::class, $traits);
        
        // Check that all expected methods exist
        $this->assertTrue(method_exists($this->policy, 'viewAny'));
        $this->assertTrue(method_exists($this->policy, 'view'));
        $this->assertTrue(method_exists($this->policy, 'create'));
        $this->assertTrue(method_exists($this->policy, 'update'));
        $this->assertTrue(method_exists($this->policy, 'delete'));
    }

    #[Test]
    public function view_any_returns_boolean_for_admin_permission(): void
    {
        $mockUser = Mockery::mock('App\Models\User');
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(true);
        $mockUser->shouldReceive('hasRole')->never();
        
        $result = $this->policy->viewAny($mockUser);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function view_any_returns_boolean_for_frontend_user_role(): void
    {
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('frontend_user')->once()->andReturn(true);
        $mockUser->shouldReceive('hasRole')->with('admin')->never();
        
        $result = $this->policy->viewAny($mockUser);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function view_any_returns_boolean_for_admin_role(): void
    {
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('frontend_user')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('admin')->once()->andReturn(true);
        
        $result = $this->policy->viewAny($mockUser);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function view_any_returns_false_for_unauthorized_user(): void
    {
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('frontend_user')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('admin')->once()->andReturn(false);
        
        $result = $this->policy->viewAny($mockUser);
        
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    #[Test]
    public function view_checks_ownership_first(): void
    {
        $userId = 123;

        $mockUser = Mockery::mock('App\Models\User');
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('hasPermissionTo')->never();
        $mockUser->shouldReceive('hasRole')->never();

        $mockProduct = Mockery::mock('App\Models\Product');
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($userId);
        
        $result = $this->policy->view($mockUser, $mockProduct);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function view_checks_permission_when_not_owner(): void
    {
        $userId = 123;
        $otherUserId = 456;

        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(true);
        $mockUser->shouldReceive('hasRole')->never();

        $mockProduct = Mockery::mock("App\\Models\\Product");
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($otherUserId);
        
        $result = $this->policy->view($mockUser, $mockProduct);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function view_returns_false_for_unauthorized_non_owner(): void
    {
        $userId = 123;
        $otherUserId = 456;

        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('frontend_user')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('admin')->once()->andReturn(false);
        
        $mockProduct = Mockery::mock("App\\Models\\Product");
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($otherUserId);
        
        $result = $this->policy->view($mockUser, $mockProduct);
        
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    #[Test]
    public function create_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->policy);
        $method = $reflection->getMethod('create');

        // Check return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function create_returns_true_for_authorized_user(): void
    {
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(true);
        $mockUser->shouldReceive('hasRole')->never();
        
        $result = $this->policy->create($mockUser);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function create_returns_false_for_unauthorized_user(): void
    {
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('frontend_user')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('admin')->once()->andReturn(false);
        
        $result = $this->policy->create($mockUser);
        
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    #[Test]
    public function update_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(objectOrClass: $this->policy);
        $method = $reflection->getMethod('update');
        
        // Check return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
        $this->assertEquals('App\Models\Product', $parameters[1]->getType()->getName());
    }

    #[Test]
    public function update_returns_true_for_owner(): void
    {
        $userId = 789;
        
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('hasPermissionTo')->never();
        $mockUser->shouldReceive('hasRole')->never();

        $mockProduct = Mockery::mock("App\\Models\\Product");
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($userId);
        
        $result = $this->policy->update($mockUser, $mockProduct);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function delete_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(objectOrClass: $this->policy);
        $method = $reflection->getMethod('delete');
        
        // Check return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('App\Models\User', $parameters[0]->getType()->getName());
        $this->assertEquals('App\Models\Product', $parameters[1]->getType()->getName());
    }

    #[Test]
    public function delete_excludes_frontend_user_role_for_non_owners(): void
    {
        $userId = 123;
        $otherUserId = 456;

        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('admin')->once()->andReturn(false);

        $mockProduct = Mockery::mock("App\\Models\\Product");
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($otherUserId);
        
        $result = $this->policy->delete($mockUser, $mockProduct);
        
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    #[Test]
    public function delete_returns_true_for_admin_non_owner(): void
    {
        $userId = 123;
        $otherUserId = 456;

        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($userId);
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->once()->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('admin')->once()->andReturn(true);

        $mockProduct = Mockery::mock("App\\Models\\Product");
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->andReturn($otherUserId);
        
        $result = $this->policy->delete($mockUser, $mockProduct);
        
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function all_policy_methods_have_proper_visibility(): void
    {
        $reflection = new \ReflectionClass($this->policy);
        
        foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method {$methodName} should be public");
        }
    }

    #[Test]
    public function policy_uses_consistent_permission_name(): void
    {
        $expectedPermission = 'can_create_edit_product';
        
        // Mock user that responds to permission checks
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('getAttribute')->with('id')->times(2)->andReturn(123);
        $mockUser->shouldReceive('hasPermissionTo')->with($expectedPermission)->times(4)->andReturn(true);
        $mockUser->shouldReceive('hasRole')->never();

        $mockProduct = Mockery::mock("App\\Models\\Product");
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->times(2)->andReturn(999);
        
        // All these methods should use the same permission
        $this->policy->viewAny($mockUser);
        $this->policy->view($mockUser, $mockProduct);
        $this->policy->create($mockUser);
        $this->policy->update($mockUser, $mockProduct);
        
        $this->assertTrue(true); // If we get here without mockery exceptions, the test passes
    }

    #[Test]
    public function policy_uses_consistent_role_names(): void
    {
        $mockUser = Mockery::mock("App\\Models\\User");
        $mockUser->shouldReceive('getAttribute')->with('id')->times(2)->andReturn(123);
        $mockUser->shouldReceive('hasPermissionTo')->with('can_create_edit_product')->times(4)->andReturn(false);
        $mockUser->shouldReceive('hasRole')->with('frontend_user')->times(4)->andReturn(false); // all methods check frontend_user except delete
        $mockUser->shouldReceive('hasRole')->with('admin')->times(4)->andReturn(false);

        $mockProduct = Mockery::mock("App\\Models\\Product");
        $mockProduct->shouldReceive('getAttribute')->with('user_id')->times(2)->andReturn(999);
        
        // All these methods should use consistent role names
        $this->policy->viewAny($mockUser);
        $this->policy->view($mockUser, $mockProduct);
        $this->policy->create($mockUser);
        $this->policy->update($mockUser, $mockProduct);
        
        $this->assertTrue(true); // If we get here without mockery exceptions, the test passes
    }

    #[Test]
    public function policy_logical_structure_is_consistent(): void
    {
        // Test that all methods follow the pattern: ownership OR permission OR role
        $reflection = new \ReflectionClass($this->policy);
        
        // Methods that should check ownership (view, update, delete)
        $ownershipMethods = ['view', 'update', 'delete'];
        foreach ($ownershipMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $parameters = $method->getParameters();
            $this->assertCount(2, $parameters, "Method {$methodName} should have 2 parameters (User, Product)");
        }
        
        // Methods that don't check ownership (viewAny, create)
        $nonOwnershipMethods = ['viewAny', 'create'];
        foreach ($nonOwnershipMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $parameters = $method->getParameters();
            $this->assertCount(1, $parameters, "Method {$methodName} should have 1 parameter (User)");
        }
    }
}
