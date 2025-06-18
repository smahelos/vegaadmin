<?php

namespace Tests\Feature\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ProductPolicy $policy;
    private User $adminUser;
    private User $frontendUser;
    private User $authorizedUser;
    private User $unauthorizedUser;
    private Product $ownProduct;
    private Product $otherUserProduct;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new ProductPolicy();
        
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $frontendRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        
        // Create permission
        $productPermission = Permission::firstOrCreate([
            'name' => 'can_create_edit_product', 
            'guard_name' => 'web'
        ]);
        
        // Create users with different roles and permissions
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);
        
        $this->frontendUser = User::factory()->create();
        $this->frontendUser->assignRole($frontendRole);
        
        $this->authorizedUser = User::factory()->create();
        $this->authorizedUser->givePermissionTo($productPermission);
        
        $this->unauthorizedUser = User::factory()->create();
        
        // Create products with unique slugs
        $this->ownProduct = Product::factory()->create([
            'user_id' => $this->frontendUser->id,
            'slug' => 'own-product-' . uniqid()
        ]);
        
        $this->otherUserProduct = Product::factory()->create([
            'user_id' => $this->unauthorizedUser->id,
            'slug' => 'other-product-' . uniqid()
        ]);
    }

    #[Test]
    public function view_any_returns_true_for_admin_user(): void
    {
        $result = $this->policy->viewAny($this->adminUser);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_any_returns_true_for_frontend_user(): void
    {
        $result = $this->policy->viewAny($this->frontendUser);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_any_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->viewAny($this->authorizedUser);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_any_returns_false_for_unauthorized_user(): void
    {
        $result = $this->policy->viewAny($this->unauthorizedUser);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function view_returns_true_for_product_owner(): void
    {
        $result = $this->policy->view($this->frontendUser, $this->ownProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_returns_true_for_admin_user_regardless_of_ownership(): void
    {
        $result = $this->policy->view($this->adminUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_returns_true_for_user_with_permission_regardless_of_ownership(): void
    {
        $result = $this->policy->view($this->authorizedUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_returns_true_for_frontend_user_regardless_of_ownership(): void
    {
        $result = $this->policy->view($this->frontendUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function view_returns_false_for_unauthorized_user_with_others_product(): void
    {
        $result = $this->policy->view($this->unauthorizedUser, $this->ownProduct);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function create_returns_true_for_admin_user(): void
    {
        $result = $this->policy->create($this->adminUser);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function create_returns_true_for_frontend_user(): void
    {
        $result = $this->policy->create($this->frontendUser);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function create_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->create($this->authorizedUser);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function create_returns_false_for_unauthorized_user(): void
    {
        $result = $this->policy->create($this->unauthorizedUser);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function update_returns_true_for_product_owner(): void
    {
        $result = $this->policy->update($this->frontendUser, $this->ownProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function update_returns_true_for_admin_user_regardless_of_ownership(): void
    {
        $result = $this->policy->update($this->adminUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function update_returns_true_for_user_with_permission_regardless_of_ownership(): void
    {
        $result = $this->policy->update($this->authorizedUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function update_returns_true_for_frontend_user_regardless_of_ownership(): void
    {
        $result = $this->policy->update($this->frontendUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function update_returns_false_for_unauthorized_user_with_others_product(): void
    {
        $result = $this->policy->update($this->unauthorizedUser, $this->ownProduct);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function delete_returns_true_for_product_owner(): void
    {
        $result = $this->policy->delete($this->frontendUser, $this->ownProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function delete_returns_true_for_admin_user_regardless_of_ownership(): void
    {
        $result = $this->policy->delete($this->adminUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function delete_returns_true_for_user_with_permission_regardless_of_ownership(): void
    {
        $result = $this->policy->delete($this->authorizedUser, $this->otherUserProduct);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function delete_returns_false_for_frontend_user_without_admin_permission(): void
    {
        // Frontend user can update but not delete other users' products without admin role
        $frontendOnlyUser = User::factory()->create();
        $frontendRole = Role::where('name', 'frontend_user')->first();
        $frontendOnlyUser->assignRole($frontendRole);
        
        $result = $this->policy->delete($frontendOnlyUser, $this->otherUserProduct);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function delete_returns_false_for_unauthorized_user_with_others_product(): void
    {
        $result = $this->policy->delete($this->unauthorizedUser, $this->ownProduct);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function policy_works_with_different_product_owners(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $product1 = Product::factory()->create(['user_id' => $user1->id]);
        $product2 = Product::factory()->create(['user_id' => $user2->id]);
        
        // User1 can view their own product but not user2's product
        $this->assertFalse($this->policy->view($user1, $product2));
        $this->assertFalse($this->policy->view($user2, $product1));
        
        // But can view their own
        $this->assertTrue($this->policy->view($user1, $product1));
        $this->assertTrue($this->policy->view($user2, $product2));
    }

    #[Test]
    public function policy_handles_multiple_roles_correctly(): void
    {
        $multiRoleUser = User::factory()->create();
        
        // Assign both frontend_user and admin roles
        $adminRole = Role::where('name', 'admin')->first();
        $frontendRole = Role::where('name', 'frontend_user')->first();
        
        $multiRoleUser->assignRole([$adminRole, $frontendRole]);
        
        // Should have admin privileges
        $this->assertTrue($this->policy->viewAny($multiRoleUser));
        $this->assertTrue($this->policy->create($multiRoleUser));
        $this->assertTrue($this->policy->view($multiRoleUser, $this->otherUserProduct));
        $this->assertTrue($this->policy->update($multiRoleUser, $this->otherUserProduct));
        $this->assertTrue($this->policy->delete($multiRoleUser, $this->otherUserProduct));
    }

    #[Test]
    public function policy_handles_permission_and_role_combination(): void
    {
        $combinedUser = User::factory()->create();
        
        // Give both role and permission
        $frontendRole = Role::where('name', 'frontend_user')->first();
        $productPermission = Permission::where('name', 'can_create_edit_product')->first();
        
        $combinedUser->assignRole($frontendRole);
        $combinedUser->givePermissionTo($productPermission);
        
        // Should work with either role or permission
        $this->assertTrue($this->policy->viewAny($combinedUser));
        $this->assertTrue($this->policy->create($combinedUser));
        $this->assertTrue($this->policy->view($combinedUser, $this->otherUserProduct));
        $this->assertTrue($this->policy->update($combinedUser, $this->otherUserProduct));
    }

    #[Test]
    public function policy_methods_return_boolean_values(): void
    {
        // Test that all policy methods return boolean values
        $viewAnyResult = $this->policy->viewAny($this->adminUser);
        $this->assertIsBool($viewAnyResult);
        
        $viewResult = $this->policy->view($this->adminUser, $this->ownProduct);
        $this->assertIsBool($viewResult);
        
        $createResult = $this->policy->create($this->adminUser);
        $this->assertIsBool($createResult);
        
        $updateResult = $this->policy->update($this->adminUser, $this->ownProduct);
        $this->assertIsBool($updateResult);
        
        $deleteResult = $this->policy->delete($this->adminUser, $this->ownProduct);
        $this->assertIsBool($deleteResult);
    }

    #[Test]
    public function policy_ownership_check_works_correctly(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        
        $product = Product::factory()->create(['user_id' => $owner->id]);
        
        // Owner should be able to view, update, and delete their product
        $this->assertTrue($this->policy->view($owner, $product));
        $this->assertTrue($this->policy->update($owner, $product));
        $this->assertTrue($this->policy->delete($owner, $product));
        
        // Non-owner without special permissions should not
        $this->assertFalse($this->policy->view($nonOwner, $product));
        $this->assertFalse($this->policy->update($nonOwner, $product));
        $this->assertFalse($this->policy->delete($nonOwner, $product));
    }
}
