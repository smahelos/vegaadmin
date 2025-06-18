<?php

namespace Tests\Feature\Traits;

use App\Models\User;
use App\Traits\HandlesFrontendApiAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HandlesFrontendApiAuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private object $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test permissions for web guard
        Permission::firstOrCreate(['name' => 'view_dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'admin_access', 'guard_name' => 'web']);
        
        // Create test role
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        
        // Create anonymous class using the trait
        $this->traitInstance = new class {
            use HandlesFrontendApiAuthentication;
            
            public function callGetFrontendUser(): ?\App\Models\User
            {
                return $this->getFrontendUser();
            }
            
            public function callFrontendUserHasPermission(string $permission): bool
            {
                return $this->frontendUserHasPermission($permission);
            }
            
            public function callFrontendUserHasAnyPermission(array $permissions): bool
            {
                return $this->frontendUserHasAnyPermission($permissions);
            }
            
            public function callGetFrontendApiLogContext(array $additionalContext = []): array
            {
                return $this->getFrontendApiLogContext($additionalContext);
            }
            
            public function callFrontendUnauthorizedResponse(?string $message = null, int $statusCode = 401)
            {
                return $this->frontendUnauthorizedResponse($message, $statusCode);
            }
        };
    }

    #[Test]
    public function getFrontendUser_returns_null_when_no_user_authenticated(): void
    {
        $user = $this->traitInstance->callGetFrontendUser();
        
        $this->assertNull($user);
    }

    #[Test]
    public function getFrontendUser_returns_authenticated_web_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $result = $this->traitInstance->callGetFrontendUser();
        
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
    }

    #[Test]
    public function getFrontendUser_only_checks_web_guard(): void
    {
        $user = User::factory()->create();
        
        // Authenticate with backpack guard (should not be detected by frontend methods)
        $this->actingAs($user, 'backpack');
        
        $result = $this->traitInstance->callGetFrontendUser();
        
        // Should return null because it only checks web guard
        $this->assertNull($result);
    }

    #[Test]
    public function frontendUserHasPermission_returns_false_when_no_user(): void
    {
        $result = $this->traitInstance->callFrontendUserHasPermission('view_dashboard');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function frontendUserHasPermission_returns_true_when_user_has_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view_dashboard');
        $this->actingAs($user, 'web');
        
        $result = $this->traitInstance->callFrontendUserHasPermission('view_dashboard');
        
        $this->assertTrue($result);
    }

    #[Test]
    public function frontendUserHasPermission_returns_false_when_user_lacks_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $result = $this->traitInstance->callFrontendUserHasPermission('admin_access');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function frontendUserHasAnyPermission_returns_false_when_no_user(): void
    {
        $result = $this->traitInstance->callFrontendUserHasAnyPermission(['view_dashboard', 'manage_users']);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function frontendUserHasAnyPermission_returns_true_when_user_has_one_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view_dashboard');
        $this->actingAs($user, 'web');
        
        $result = $this->traitInstance->callFrontendUserHasAnyPermission(['view_dashboard', 'manage_users']);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function frontendUserHasAnyPermission_returns_false_when_user_has_no_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        $result = $this->traitInstance->callFrontendUserHasAnyPermission(['admin_access', 'manage_users']);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function getFrontendApiLogContext_includes_user_information_when_authenticated(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view_dashboard');
        $user->assignRole('user');
        $this->actingAs($user, 'web');
        
        $context = $this->traitInstance->callGetFrontendApiLogContext(['custom' => 'value']);
        
        $this->assertEquals('web', $context['guard']);
        $this->assertEquals($user->id, $context['user_id']);
        $this->assertEquals($user->email, $context['user_email']);
        $this->assertArrayHasKey('route', $context);
        $this->assertArrayHasKey('path', $context);
        $this->assertArrayHasKey('ip', $context);
        $this->assertArrayHasKey('user_agent', $context);
        $this->assertArrayHasKey('session_id', $context);
        $this->assertArrayHasKey('user_roles', $context);
        $this->assertArrayHasKey('user_permissions', $context);
        $this->assertEquals('value', $context['custom']);
        
        $this->assertContains('user', $context['user_roles']);
        $this->assertContains('view_dashboard', $context['user_permissions']);
    }

    #[Test]
    public function getFrontendApiLogContext_excludes_user_specific_data_when_not_authenticated(): void
    {
        $context = $this->traitInstance->callGetFrontendApiLogContext();
        
        $this->assertEquals('web', $context['guard']);
        $this->assertNull($context['user_id']);
        $this->assertNull($context['user_email']);
        $this->assertArrayNotHasKey('user_roles', $context);
        $this->assertArrayNotHasKey('user_permissions', $context);
    }

    #[Test]
    public function frontendUnauthorizedResponse_returns_json_for_ajax_requests(): void
    {
        $this->withoutExceptionHandling();
        
        // Mock AJAX request
        $request = request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        $response = $this->traitInstance->callFrontendUnauthorizedResponse('Access denied', 403);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertEquals('Access denied', $data['error']);
        $this->assertEquals(403, $data['code']);
    }

    #[Test]
    public function frontendUnauthorizedResponse_returns_redirect_for_regular_requests(): void
    {
        $response = $this->traitInstance->callFrontendUnauthorizedResponse();
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function frontendUnauthorizedResponse_uses_default_message_when_none_provided(): void
    {
        $request = request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        $response = $this->traitInstance->callFrontendUnauthorizedResponse();
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertIsString($data['error']);
    }

    #[Test]
    public function trait_methods_log_appropriate_debug_information(): void
    {
        Log::shouldReceive('debug')
            ->with('No frontend user authenticated')
            ->once();
            
        $this->traitInstance->callGetFrontendUser();
        
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        
        Log::shouldReceive('debug')
            ->with('Frontend user authenticated', [
                'user_id' => $user->id,
                'email' => $user->email
            ])
            ->once();
            
        $this->traitInstance->callGetFrontendUser();
    }
}
