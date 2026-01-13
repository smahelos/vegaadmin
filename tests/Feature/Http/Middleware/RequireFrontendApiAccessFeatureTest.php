<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RequireFrontendApiAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequireFrontendApiAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    private RequireFrontendApiAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireFrontendApiAccess();
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $role->givePermissionTo('frontend.api.access');
    }

    #[Test]
    public function unauthenticated_json_request_gets_401(): void
    {
        $request = Request::create('/api/frontend/resource', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);
        $response = $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals(401, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(401, $json['code']);
    }

    #[Test]
    public function authenticated_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        $request = Request::create('/api/frontend/resource', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);
        $response = $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function authenticated_user_with_permission_passes(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'frontend_user')->first();
        $user->assignRole($role);
        $this->actingAs($user, 'web');
        $request = Request::create('/api/frontend/resource');
        $passed = false;
        $this->middleware->handle($request, function () use (&$passed) { $passed = true; return response('OK'); });
        $this->assertTrue($passed);
    }
}
