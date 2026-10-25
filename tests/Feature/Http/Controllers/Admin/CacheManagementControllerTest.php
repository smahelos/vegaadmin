<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Permission;
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

        $this->post(route($route))->assertRedirect(route('backpack.auth.login'));

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
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('backend_user', 'backpack'));
        $user->givePermissionTo(Permission::findByName('backpack.access', 'backpack'));

        $this->actingAs($user, 'backpack')->post(route($route))->assertForbidden();

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

        $this->actingAs($this->admin(), 'backpack')
            ->from('/admin/dashboard')
            ->post(route($route))
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas('alert_messages.success', [__($message)]);

        foreach ($commands as $command) {
            $artisan->shouldHaveReceived('call')->with($command)->once();
        }
    }

    #[Test]
    public function a_failure_shows_a_message_without_the_exception_text(): void
    {
        Artisan::shouldReceive('call')->with('cache:clear')->andThrow(new RuntimeException('Connection refused [tcp://redis:6379]'));

        $response = $this->actingAs($this->admin(), 'backpack')
            ->from('/admin/dashboard')
            ->post(route('admin.cache.clear'));

        $response->assertSessionHas('alert_messages.error', [__('admin.cache.cache_cleared_error')]);
        $this->assertStringNotContainsString('redis', json_encode(session('alert_messages')));
    }

    #[Test]
    public function cache_is_not_cleared_over_get(): void
    {
        $artisan = Artisan::spy();

        $this->actingAs($this->admin(), 'backpack')
            ->get('/admin/cache/clear')
            ->assertMethodNotAllowed();

        $artisan->shouldNotHaveReceived('call');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin', 'backpack'));

        return $user;
    }
}
