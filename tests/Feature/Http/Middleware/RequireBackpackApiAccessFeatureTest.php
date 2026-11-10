<?php

namespace Tests\Feature\Http\Middleware;

use App\Domain\User\Enums\Permission;
use App\Http\Middleware\RequireBackpackApiAccess;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequireBackpackApiAccessFeatureTest extends TestCase
{
    private RequireBackpackApiAccess $middleware;

    /** @var list<array{string, string}> */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireBackpackApiAccess;
        PermissionModel::firstOrCreate(
            ['name' => Permission::API_ACCESS->value, 'guard_name' => 'backpack'],
        );
        PermissionModel::firstOrCreate(
            ['name' => Permission::BACKPACK_ACCESS->value, 'guard_name' => 'backpack'],
        );
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $role->givePermissionTo([Permission::BACKPACK_ACCESS, Permission::API_ACCESS]);
    }

    #[Test]
    public function unauthenticated_json_request_gets_401(): void
    {
        $request = Request::create(
            '/admin/api/resource',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
        );
        $response = $this->middleware->handle(
            $request,
            fn () => response('OK'),
        );
        $this->assertEquals(401, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(401, $json['code']);
    }

    #[Test]
    public function authenticated_without_permission_gets_403(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'backpack');
        $request = Request::create(
            '/admin/api/resource',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
        );
        $response = $this->middleware->handle(
            $request,
            fn () => response('OK'),
        );
        $this->assertEquals(403, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(403, $json['code']);
    }

    #[Test]
    public function authenticated_with_permission_passes(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $roleQuery = Role::where('name', 'admin');
        $roleQuery->where('guard_name', 'backpack');
        $role = $roleQuery->first();
        $user->assignRole($role);
        $this->actingAs($user, 'backpack');
        $request = Request::create('/admin/api/resource');
        $passed = false;
        $this->middleware->handle(
            $request,
            function () use (&$passed) {
                $passed = true;

                return response('OK');
            },
        );
        $this->assertTrue($passed);
    }

    #[Test]
    public function permitted_request_writes_no_log_entry(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $roleQuery = Role::where('name', 'admin');
        $roleQuery->where('guard_name', 'backpack');
        $user->assignRole($roleQuery->first());
        $this->actingAs($user, 'backpack');
        $this->collectLogs();
        $this->middleware->handle(
            Request::create('/admin/api/resource'),
            fn () => response('OK'),
        );
        $this->assertSame([], $this->logs);
    }

    #[Test]
    public function denied_request_writes_only_the_warning(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'backpack');
        $this->collectLogs();
        $this->middleware->handle(
            Request::create('/admin/api/resource'),
            fn () => response('OK'),
        );
        $this->assertSame(
            [['warning', 'Backpack user lacks API access permission']],
            $this->logs,
        );
    }

    private function collectLogs(): void
    {
        Event::listen(
            MessageLogged::class,
            function (MessageLogged $log): void {
                $this->logs[] = [$log->level, $log->message];
            },
        );
    }
}
