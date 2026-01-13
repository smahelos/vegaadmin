<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RequireBackpackAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequireBackpackAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    private RequireBackpackAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireBackpackAccess();
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $role->givePermissionTo('backpack.access');
    }

    #[Test]
    public function unauthenticated_json_request_gets_401(): void
    {
        $request = Request::create('/admin/dashboard', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function backpack_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');
        $request = Request::create('/admin/dashboard', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function backpack_user_with_permission_passes(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();
        $user->assignRole($role);
        $this->actingAs($user, 'backpack');
        $request = Request::create('/admin/dashboard');
        $passed = false;
        $this->middleware->handle($request, function () use (&$passed) { $passed = true; return response('OK'); });
        $this->assertTrue($passed);
    }
}
