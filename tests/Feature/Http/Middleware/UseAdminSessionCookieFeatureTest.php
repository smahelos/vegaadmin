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
    public function logging_out_of_the_admin_keeps_the_frontend_user_logged_in(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $cookie = config('session.cookie');
        $userFactory = User::factory();
        $user = $userFactory->create();
        // The frontend login form posts to the URL of the page that shows it.
        $userLogin = $this->post(
            route(
                'frontend.login',
                ['locale' => config('app.locale')],
            ),
            ['email' => $user->email, 'password' => 'password'],
        );
        $userLogin->assertCookie($cookie);
        $userSession = $userLogin->getCookie($cookie);
        // A real login, because actingAs() would make backpack the default guard of the frontend routes.
        $admin = $this->backendUser();
        $adminLogin = $this->post(
            route('backpack.auth.login'),
            ['email' => $admin->email, 'password' => 'password'],
        );
        $adminSession = $adminLogin->getCookie($cookie . '_admin');
        // A browser sends every cookie of the domain.
        $this->withCookie($cookie, $userSession->getValue());
        $this->withCookie($cookie . '_admin', $adminSession->getValue());

        $this->get(
            route('backpack.auth.logout'),
        );

        // Guards keep their user between test requests; the frontend user must come back from the session.
        $auth = $this->app['auth'];
        $auth->forgetGuards();
        $response = $this->get(
            route(
                'frontend.dashboard',
                ['locale' => config('app.locale')],
            ),
        );
        $response->assertOk();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('backpack');
    }

    #[Test]
    public function frontend_uses_the_default_session_cookie(): void
    {
        $cookie = config('session.cookie');

        $response = $this->get(
            route(
                'frontend.login',
                ['locale' => config('app.locale')],
            ),
        );

        $response->assertCookie($cookie);
        $response->assertCookieMissing($cookie . '_admin');
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
