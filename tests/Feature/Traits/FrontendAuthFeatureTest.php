<?php

namespace Tests\Feature\Traits;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FrontendAuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view_dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'admin_access', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    #[Test]
    public function web_guard_authenticates_user_and_permissions_work(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view_dashboard');
        $this->actingAs($user, 'web');

        $this->assertTrue(auth('web')->check());
        $this->assertEquals($user->id, auth('web')->id());
        $this->assertTrue($user->hasPermissionTo('view_dashboard', 'web'));
        $this->assertFalse($user->hasPermissionTo('manage_users', 'web'));
    }

    #[Test]
    public function unauthorized_json_response_shape(): void
    {
        $request = request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = response()->json(['error' => __('users.auth.unauthorized'), 'code' => 401], 401);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals(401, $data['code']);
    }

    #[Test]
    public function unauthorized_redirect_for_regular_request(): void
    {
        $response = redirect()->guest(route('frontend.login', ['locale' => app()->getLocale()]));
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
    }
}
