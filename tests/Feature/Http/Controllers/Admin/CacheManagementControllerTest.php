<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class CacheManagementControllerTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>, string}>
     */
    public static function actions(): array
    {
        return [
            'cache' => [
                'admin.cache.clear',
                ['cache:clear'],
                'admin.cache.cache_cleared_success',
            ],
            'config' => [
                'admin.cache.clear-config',
                ['config:clear'],
                'admin.cache.config_cleared_success',
            ],
            'views' => [
                'admin.cache.clear-views',
                ['view:clear'],
                'admin.cache.views_cleared_success',
            ],
            'routes' => [
                'admin.cache.clear-routes',
                ['route:clear'],
                'admin.cache.routes_cleared_success',
            ],
            'all' => [
                'admin.cache.clear-all',
                [
                    'cache:clear',
                    'config:clear',
                    'view:clear',
                    'route:clear',
                ],
                'admin.cache.all_cleared_success',
            ],
        ];
    }

    /**
     * @param  list<string>  $commands
     */
    #[Test]
    #[DataProvider('actions')]
    public function guest_is_sent_to_the_admin_login(
        string $route,
        array $commands,
        string $message,
    ): void {
        $artisan = Artisan::spy();

        $httpResponse = $this->post(
            route($route),
        );
        $httpResponse->assertRedirect(
            route('backpack.auth.login'),
        );

        $artisan->shouldNotHaveReceived('call');
    }

    /**
     * @param  list<string>  $commands
     */
    #[Test]
    #[DataProvider('actions')]
    public function admin_without_system_configuration_permission_gets_403(
        string $route,
        array $commands,
        string $message,
    ): void {
        $artisan = Artisan::spy();
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('backend_user', 'backpack'),
        );
        $user->givePermissionTo(
            PermissionModel::findByName(Permission::BACKPACK_ACCESS->value, 'backpack'),
        );

        $httpClient = $this->actingAs($user, 'backpack');
        $httpResponse = $httpClient->post(
            route($route),
        );
        $httpResponse->assertForbidden();

        $artisan->shouldNotHaveReceived('call');
    }

    /**
     * @param  list<string>  $commands
     */
    #[Test]
    #[DataProvider('actions')]
    public function admin_clears_the_cache(
        string $route,
        array $commands,
        string $message,
    ): void {
        $artisan = Artisan::spy();

        $httpClient = $this->actingAs($this->admin(), 'backpack');
        $httpClient = $httpClient->from('/admin/dashboard');
        $httpResponse = $httpClient->post(
            route($route),
        );
        $httpResponse = $httpResponse->assertRedirect('/admin/dashboard');
        $httpResponse->assertSessionHas(
            'alert_messages.success',
            [__($message)],
        );

        foreach ($commands as $command) {
            $verification = $artisan->shouldHaveReceived('call');
            $verification = $verification->with($command);
            $verification->once();
        }
    }

    #[Test]
    public function a_failure_shows_a_message_without_the_exception_text(): void
    {
        $expectation = Artisan::shouldReceive('call');
        $expectation->with('cache:clear');
        $expectation->andThrow(
            new RuntimeException('Connection refused [tcp://redis:6379]'),
        );

        $httpClient = $this->actingAs($this->admin(), 'backpack');
        $httpClient = $httpClient->from('/admin/dashboard');
        $response = $httpClient->post(
            route('admin.cache.clear'),
        );

        $response->assertSessionHas(
            'alert_messages.error',
            [__('admin.cache.cache_cleared_error')],
        );
        $this->assertStringNotContainsString(
            'redis',
            json_encode(
                session('alert_messages'),
            ),
        );
    }

    #[Test]
    public function cache_is_not_cleared_over_get(): void
    {
        $artisan = Artisan::spy();

        $httpClient = $this->actingAs($this->admin(), 'backpack');
        $httpResponse = $httpClient->get('/admin/cache/clear');
        $httpResponse->assertMethodNotAllowed();

        $artisan->shouldNotHaveReceived('call');
    }

    private function admin(): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('admin', 'backpack'),
        );

        return $user;
    }
}
