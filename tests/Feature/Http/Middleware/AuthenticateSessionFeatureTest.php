<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Backpack's AuthenticateSession compares the session password hash with the user's password
 * hash. Laravel 12.69.3+ stores an HMAC of it on login, which Backpack 6 does not accept, so
 * the admin is logged out right after logging in (laravel/framework is pinned below 12.69.3).
 */
final class AuthenticateSessionFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_stays_logged_in_after_login(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK));

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect();

        $this->get('/admin/dashboard')->assertOk();
        $this->assertAuthenticatedAs($admin, PermissionCatalog::BACKPACK);
    }
}
