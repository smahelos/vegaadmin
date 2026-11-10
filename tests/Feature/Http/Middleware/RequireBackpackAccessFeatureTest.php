<?php

namespace Tests\Feature\Http\Middleware;

use App\Domain\User\Enums\Permission;
use App\Http\Middleware\RequireBackpackAccess;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequireBackpackAccessFeatureTest extends TestCase
{
    private RequireBackpackAccess $middleware;

    /** @var list<array{string, string, array<string, mixed>}> */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireBackpackAccess;
        PermissionModel::firstOrCreate(
            ['name' => Permission::BACKPACK_ACCESS->value, 'guard_name' => 'backpack'],
        );
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $role->givePermissionTo(Permission::BACKPACK_ACCESS);
    }

    #[Test]
    public function unauthenticated_json_request_gets_401(): void
    {
        $application = app();
        $application->setLocale('cs');
        $request = Request::create(
            '/admin/dashboard',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );
        $response = $this->middleware->handle(
            $request,
            fn () => response('OK'),
        );
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame(
            'Nejste přihlášeni.',
            $response->getData(true)['error'],
        );
    }

    #[Test]
    public function backpack_user_without_permission_gets_403(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'backpack');
        $application = app();
        $application->setLocale('cs');
        $request = Request::create(
            '/admin/dashboard',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );
        $response = $this->middleware->handle(
            $request,
            fn () => response('OK'),
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame(
            'Přístup odepřen. Nemáte oprávnění pro přístup do administrace.',
            $response->getData(true)['error'],
        );
    }

    #[Test]
    public function backpack_user_with_permission_passes(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $roleQuery = Role::where('name', 'admin');
        $role = $roleQuery->first();
        $user->assignRole($role);
        $this->actingAs($user, 'backpack');
        $request = Request::create('/admin/dashboard');
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
        $user = $this->userWithRole('admin');
        $this->actingAs($user, 'backpack');
        $this->collectLogs();

        $this->middleware->handle(
            Request::create('/admin/dashboard'),
            fn () => response('OK'),
        );

        $this->assertSame([], $this->logs);
    }

    #[Test]
    public function user_without_backpack_role_is_logged_by_id_only(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'backpack');
        $this->collectLogs();

        $this->middleware->handle(
            Request::create(
                '/admin/dashboard',
                'GET',
                [],
                [],
                [],
                ['HTTP_ACCEPT' => 'application/json'],
            ),
            fn () => response('OK'),
        );

        $this->assertSame(
            [
                [
                    'warning',
                    'User without backpack roles attempted to access admin area',
                    ['user_id' => $user->id, 'path' => 'admin/dashboard'],
                ],
            ],
            $this->logs,
        );
    }

    #[Test]
    public function backpack_role_without_access_permission_is_logged_by_id_only(): void
    {
        Role::firstOrCreate(['name' => 'backend_user', 'guard_name' => 'backpack']);
        $user = $this->userWithRole('backend_user');
        $this->actingAs($user, 'backpack');
        $this->collectLogs();

        $this->middleware->handle(
            Request::create(
                '/admin/dashboard',
                'GET',
                [],
                [],
                [],
                ['HTTP_ACCEPT' => 'application/json'],
            ),
            fn () => response('OK'),
        );

        $this->assertSame(
            [
                [
                    'warning',
                    'User with backpack role but without backpack.access permission attempted to access admin area',
                    ['user_id' => $user->id, 'path' => 'admin/dashboard'],
                ],
            ],
            $this->logs,
        );
    }

    private function userWithRole(string $role): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName($role, 'backpack'),
        );

        return $user;
    }

    private function collectLogs(): void
    {
        Event::listen(
            MessageLogged::class,
            function (MessageLogged $log): void {
                $this->logs[] = [
                    $log->level,
                    $log->message,
                    $log->context,
                ];
            },
        );
    }
}
