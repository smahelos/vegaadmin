<?php

namespace Tests\Feature\Http\Controllers\Frontend\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_can_view_login_form(): void
    {
        $locale = config('app.locale');
        $response = $this->get(route('frontend.login', ['locale' => $locale]));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    #[Test]
    public function unsuccessful_login_shows_errors(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $locale = config('app.locale');
        $response = $this->from(route('frontend.login', ['locale' => $locale]))
            ->post(route('frontend.login', ['locale' => $locale]), [
                'email' => 'nouser@example.com',
                'password' => 'wrong',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('frontend.login', ['locale' => $locale]));
        // Some flows may return generic failure without validation bag; accept either validation error or flash 'error'
        if (session()->has('errors')) {
            $response->assertSessionHasErrors('email');
        } else {
            $this->assertTrue(session()->has('error'));
        }
        $this->assertGuest('web');
    }

    #[Test]
    public function successful_login_redirects_to_dashboard(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $locale = config('app.locale');
        $passwordPlain = 'Secret123!';
        $user = User::factory()->create([
            'email' => 'user_'.uniqid().'@example.com',
            'password' => Hash::make($passwordPlain),
        ]);

        $response = $this->post(route('frontend.login', ['locale' => $locale]), [
            'email' => $user->email,
            'password' => $passwordPlain,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('frontend.dashboard', ['locale' => $locale]));
        $this->assertAuthenticatedAs($user, 'web');
    }

    #[Test]
    public function logout_redirects_to_home_and_logs_out_user(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $locale = config('app.locale');
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $response = $this->post(route('frontend.logout', ['locale' => $locale]));
        $response->assertStatus(302);
        $response->assertRedirect(route('home', ['locale' => $locale]));
        $this->assertGuest('web');
    }
}
