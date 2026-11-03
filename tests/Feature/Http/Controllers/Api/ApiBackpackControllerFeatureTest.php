<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBackpackController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ApiBackpackControllerFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Define temporary test routes using reflection to access protected controller methods (no fake wrapper class)
        $routeRegistrar = Route::middleware('web');
        $routeRegistrar->get(
            '/api/test-backpack-auth-user',
            function () {
                $controller = app(ApiBackpackController::class);
                $ref = new ReflectionClass($controller);
                $mAuth = $ref->getMethod('getAuthenticatedUser');
                $mAuth->setAccessible(true);
                $user = $mAuth->invoke($controller);
                // Determine guard used
                $guardUsed = 'none';
                if ($user) {
                    $guardUsed = 'unknown';
                    $guards = [
                        'backpack',
                        'web',
                        'sanctum',
                    ];
                    foreach ($guards as $guard) {
                        $auth = auth($guard);
                        if ($auth->check() && $auth->id() === $user->id) {
                            $guardUsed = $guard;
                            break;
                        }
                    }
                }

                $responseFactory = response();

                return $responseFactory->json(
                    [
                        'user_id' => $user?->id ?? 'unauthenticated',
                        'guard_used' => $guardUsed,
                    ],
                );
            },
        );
        $routeRegistrar = Route::middleware('web');
        $routeRegistrar->get(
            '/api/test-backpack-auth-context',
            function () {
                $controller = app(ApiBackpackController::class);
                $ref = new ReflectionClass($controller);
                $mCtx = $ref->getMethod('getLogContext');
                $mCtx->setAccessible(true);
                $ctx = $mCtx->invoke($controller, []);

                $responseFactory = response();

                return $responseFactory->json(['context' => $ctx]);
            },
        );
    }

    public function test_returns_backpack_user_when_authenticated_via_backpack_guard(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'backpack');

        $response = $this->getJson('/api/test-backpack-auth-user');
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertJsonPath('user_id', $user->id);
        $assertionResponse->assertJsonPath('guard_used', 'backpack');
    }

    public function test_falls_back_to_web_guard_when_backpack_not_authenticated(): void
    {
        $userFactory = User::factory();
        $webUser = $userFactory->create();
        $this->actingAs($webUser, 'web');

        $response = $this->getJson('/api/test-backpack-auth-user');
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertJsonPath('user_id', $webUser->id);
        $assertionResponse->assertJsonPath('guard_used', 'web');
    }

    public function test_returns_unauthenticated_when_no_guard_authenticated(): void
    {
        $response = $this->getJson('/api/test-backpack-auth-user');
        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertJsonPath('user_id', 'unauthenticated');
        $assertionResponse->assertJsonPath('guard_used', 'none');
    }

    public function test_log_context_endpoint_contains_required_keys(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'backpack');

        $response = $this->getJson('/api/test-backpack-auth-context');
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertJsonStructure(
            [
                'context' => [
                    'user_id',
                    'route',
                    'path',
                    'is_admin',
                    'guards' => [
                        'backpack',
                        'web',
                        'sanctum',
                    ],
                    'session_id',
                    'has_backpack_session',
                ],
            ],
        );
    }
}
