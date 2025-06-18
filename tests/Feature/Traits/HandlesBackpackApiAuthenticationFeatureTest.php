<?php

namespace Tests\Feature\Traits;

use App\Models\User;
use App\Traits\HandlesBackpackApiAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HandlesBackpackApiAuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase, HandlesBackpackApiAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required permissions for backpack guard
        Permission::firstOrCreate(['name' => 'test.permission', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'another.permission', 'guard_name' => 'backpack']);
    }

    #[Test]
    public function get_backpack_user_returns_null_when_not_authenticated(): void
    {
        // Act
        $user = $this->getBackpackUser();

        // Assert
        $this->assertNull($user);
    }

    #[Test]
    public function get_backpack_user_returns_user_when_authenticated(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        // Act
        $result = $this->getBackpackUser();

        // Assert
        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
    }

    #[Test]
    public function backpack_user_has_permission_returns_false_when_not_authenticated(): void
    {
        // Act
        $result = $this->backpackUserHasPermission('test.permission');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function backpack_user_has_permission_returns_false_when_user_lacks_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        // Act
        $result = $this->backpackUserHasPermission('test.permission');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function backpack_user_has_permission_returns_true_when_user_has_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::where('name', 'test.permission')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($permission);
        $this->actingAs($user, 'backpack');

        // Act
        $result = $this->backpackUserHasPermission('test.permission');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function backpack_user_has_any_permission_returns_false_when_not_authenticated(): void
    {
        // Act
        $result = $this->backpackUserHasAnyPermission(['test.permission', 'another.permission']);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function backpack_user_has_any_permission_returns_false_when_user_lacks_all_permissions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        // Act
        $result = $this->backpackUserHasAnyPermission(['test.permission', 'another.permission']);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function backpack_user_has_any_permission_returns_true_when_user_has_one_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::where('name', 'test.permission')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($permission);
        $this->actingAs($user, 'backpack');

        // Act
        $result = $this->backpackUserHasAnyPermission(['test.permission', 'another.permission']);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function backpack_user_has_any_permission_returns_true_when_user_has_all_permissions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission1 = Permission::where('name', 'test.permission')->where('guard_name', 'backpack')->first();
        $permission2 = Permission::where('name', 'another.permission')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo([$permission1, $permission2]);
        $this->actingAs($user, 'backpack');

        // Act
        $result = $this->backpackUserHasAnyPermission(['test.permission', 'another.permission']);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function get_backpack_api_log_context_returns_basic_context_when_not_authenticated(): void
    {
        // Act
        $context = $this->getBackpackApiLogContext();

        // Assert
        $this->assertIsArray($context);
        $this->assertEquals('backpack', $context['guard']);
        $this->assertNull($context['user_id']);
        $this->assertNull($context['user_email']);
        $this->assertArrayHasKey('route', $context);
        $this->assertArrayHasKey('path', $context);
        $this->assertArrayHasKey('ip', $context);
        $this->assertArrayHasKey('user_agent', $context);
        $this->assertArrayHasKey('session_id', $context);
    }

    #[Test]
    public function get_backpack_api_log_context_returns_full_context_when_authenticated(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::where('name', 'test.permission')->where('guard_name', 'backpack')->first();
        $user->givePermissionTo($permission);
        $this->actingAs($user, 'backpack');

        // Act
        $context = $this->getBackpackApiLogContext();

        // Assert
        $this->assertIsArray($context);
        $this->assertEquals('backpack', $context['guard']);
        $this->assertEquals($user->id, $context['user_id']);
        $this->assertEquals($user->email, $context['user_email']);
        $this->assertArrayHasKey('user_roles', $context);
        $this->assertArrayHasKey('user_permissions', $context);
        $this->assertIsArray($context['user_roles']);
        $this->assertIsArray($context['user_permissions']);
        $this->assertContains('test.permission', $context['user_permissions']);
    }

    #[Test]
    public function get_backpack_api_log_context_merges_additional_context(): void
    {
        // Arrange
        $additionalContext = ['custom_key' => 'custom_value', 'another_key' => 123];

        // Act
        $context = $this->getBackpackApiLogContext($additionalContext);

        // Assert
        $this->assertArrayHasKey('custom_key', $context);
        $this->assertArrayHasKey('another_key', $context);
        $this->assertEquals('custom_value', $context['custom_key']);
        $this->assertEquals(123, $context['another_key']);
    }

    #[Test]
    public function backpack_unauthorized_response_returns_json_for_ajax_request(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->app->instance('request', $request);

        // Act
        $response = $this->backpackUnauthorizedResponse('Custom message', 403);

        // Assert
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Custom message', $data['error']);
        $this->assertEquals(403, $data['code']);
    }

    #[Test]
    public function backpack_unauthorized_response_returns_json_for_json_request(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $this->app->instance('request', $request);

        // Act
        $response = $this->backpackUnauthorizedResponse();

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    #[Test]
    public function backpack_unauthorized_response_returns_redirect_for_normal_request(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        // Act
        $response = $this->backpackUnauthorizedResponse();

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->getTargetUrl());
    }

    #[Test]
    public function methods_log_appropriate_information(): void
    {
        // Arrange
        Log::shouldReceive('debug')
            ->times(2) // getBackpackUser calls debug twice - no user found and permission check details
            ->withAnyArgs();

        // Act
        $this->backpackUserHasPermission('test.permission');

        // Assert - Logging was called (verified by shouldReceive)
        $this->assertTrue(true);
    }

    #[Test]
    public function trait_handles_edge_cases_gracefully(): void
    {
        // Test with empty permission string
        $result1 = $this->backpackUserHasPermission('');
        $this->assertFalse($result1);

        // Test with empty permissions array
        $result2 = $this->backpackUserHasAnyPermission([]);
        $this->assertFalse($result2);

        // Test log context with empty additional context
        $context = $this->getBackpackApiLogContext([]);
        $this->assertIsArray($context);
        $this->assertArrayHasKey('guard', $context);
    }
}
