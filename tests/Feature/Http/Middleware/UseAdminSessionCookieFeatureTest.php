<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\TestCase;

final class UseAdminSessionCookieFeatureTest extends TestCase
{
    use BuildsBackendUsers;

    #[Test]
    public function logging_out_of_the_frontend_keeps_the_admin_logged_in(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $adminCookie = config('session.cookie') . '_admin';
        $admin = $this->backendUser();
        $adminLogin = $this->post(
            route('backpack.auth.login'),
            ['email' => $admin->email, 'password' => 'password'],
        );
        $adminLogin->assertCookie($adminCookie);
        $adminSession = $adminLogin->getCookie($adminCookie);
        $userFactory = User::factory();
        $user = $userFactory->create();
        $this->actingAs($user, 'web');
        // A browser sends every cookie of the domain, the admin one included.
        $this->withCookie($adminCookie, $adminSession->getValue());

        $this->post(
            route(
                'frontend.logout',
                ['locale' => config('app.locale')],
            ),
        );

        // Guards keep their user between test requests; the admin must come back from the session.
        $auth = $this->app['auth'];
        $auth->forgetGuards();
        $response = $this->get(
            backpack_url('dashboard'),
        );
        $response->assertOk();
        $this->assertAuthenticatedAs($admin, 'backpack');
        $this->assertGuest('web');
    }

    #[Test]
    public function admin_api_uses_the_admin_session_cookie(): void
    {
        $cookie = config('session.cookie');
        // Sanctum starts the session only for a request from the app's own pages.
        $this->withHeader(
            'Referer',
            config('app.url'),
        );

        $response = $this->getJson(
            route('api.admin.users.search'),
        );

        $response->assertCookie($cookie . '_admin');
        $response->assertCookieMissing($cookie);
    }
}
