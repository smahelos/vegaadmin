<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RequireBackpackApiAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequireBackpackApiAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    private RequireBackpackApiAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireBackpackApiAccess();
        Permission::firstOrCreate(['name' => 'backpack.api.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $role->givePermissionTo(['backpack.access','backpack.api.access']);
    }

    #[Test]
    public function unauthenticated_json_request_gets_401(): void
    {
        $request = Request::create('/admin/api/resource', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);
        $response = $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals(401, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(401, $json['code']);
    }

    #[Test]
    public function authenticated_without_permission_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        $request = Request::create('/admin/api/resource', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);
        $response = $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals(403, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(403, $json['code']);
    }

    #[Test]
    public function authenticated_with_permission_passes(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name','admin')->where('guard_name','backpack')->first();
        $user->assignRole($role);
        $this->actingAs($user, 'backpack');
        $request = Request::create('/admin/api/resource');
        $passed = false;
        $this->middleware->handle($request, function () use (&$passed) { $passed = true; return response('OK'); });
        $this->assertTrue($passed);
    }
}
