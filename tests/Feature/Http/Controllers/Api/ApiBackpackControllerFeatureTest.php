<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBackpackController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use ReflectionClass;

class ApiBackpackControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Define temporary test routes using reflection to access protected controller methods (no fake wrapper class)
        Route::middleware('web')->get('/api/test-backpack-auth-user', function() {
            $controller = app(ApiBackpackController::class);
            $ref = new ReflectionClass($controller);
            $mAuth = $ref->getMethod('getAuthenticatedUser');
            $mAuth->setAccessible(true);
            $user = $mAuth->invoke($controller);
            // Determine guard used
            $guardUsed = 'none';
            if ($user) {
                if (auth('backpack')->check() && auth('backpack')->id() === $user->id) { $guardUsed = 'backpack'; }
                elseif (auth('web')->check() && auth('web')->id() === $user->id) { $guardUsed = 'web'; }
                elseif (auth('sanctum')->check() && auth('sanctum')->id() === $user->id) { $guardUsed = 'sanctum'; }
                else { $guardUsed = 'unknown'; }
            }
            return response()->json([
                'user_id' => $user?->id ?? 'unauthenticated',
                'guard_used' => $guardUsed,
            ]);
        });
        Route::middleware('web')->get('/api/test-backpack-auth-context', function() {
            $controller = app(ApiBackpackController::class);
            $ref = new ReflectionClass($controller);
            $mCtx = $ref->getMethod('getLogContext');
            $mCtx->setAccessible(true);
            $ctx = $mCtx->invoke($controller, []);
            return response()->json(['context' => $ctx]);
        });
    }

    public function test_returns_backpack_user_when_authenticated_via_backpack_guard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        $response = $this->getJson('/api/test-backpack-auth-user');
        $response->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('guard_used', 'backpack');
    }

    public function test_falls_back_to_web_guard_when_backpack_not_authenticated(): void
    {
        $webUser = User::factory()->create();
        $this->actingAs($webUser, 'web');

        $response = $this->getJson('/api/test-backpack-auth-user');
        $response->assertOk()
            ->assertJsonPath('user_id', $webUser->id)
            ->assertJsonPath('guard_used', 'web');
    }

    public function test_returns_unauthenticated_when_no_guard_authenticated(): void
    {
        $response = $this->getJson('/api/test-backpack-auth-user');
        $response->assertOk()
            ->assertJsonPath('user_id', 'unauthenticated')
            ->assertJsonPath('guard_used', 'none');
    }

    public function test_log_context_endpoint_contains_required_keys(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

        $response = $this->getJson('/api/test-backpack-auth-context');
        $response->assertOk()
            ->assertJsonStructure([
                'context' => [
                    'user_id', 'route', 'path', 'is_admin', 'guards' => ['backpack', 'web', 'sanctum'], 'session_id', 'has_backpack_session'
                ]
            ]);
    }
}
