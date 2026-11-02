<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Backpack's AuthenticateSession compares the session password hash with the user's password
 * hash. Laravel 12.69.3+ stores an HMAC of it on login; Backpack 6 did not accept it and logged
 * the admin out right after logging in (fixed in Backpack 7, Laravel-Backpack/CRUD#6086).
 */
final class AuthenticateSessionFeatureTest extends TestCase
{
    #[Test]
    public function admin_stays_logged_in_after_login(): void
    {
        $userFactory = User::factory();
        $admin = $userFactory->create();
        $admin->assignRole(
            Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK),
        );

        $httpResponse = $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password']);
        $httpResponse->assertRedirect();

        $httpResponse2 = $this->get('/admin/dashboard');
        $httpResponse2->assertOk();
        $this->assertAuthenticatedAs($admin, PermissionCatalog::BACKPACK);
    }
}
