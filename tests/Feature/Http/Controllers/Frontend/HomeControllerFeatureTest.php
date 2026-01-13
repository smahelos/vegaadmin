<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_sees_login_view_when_not_authenticated(): void
    {
        $locale = config('app.locale');
        $response = $this->get(route('frontend.home.test', ['locale' => $locale]));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    #[Test]
    public function authenticated_user_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $locale = config('app.locale');

        $response = $this->get(route('frontend.home.test', ['locale' => $locale]));
        $response->assertStatus(302);
        $response->assertRedirect(route('frontend.dashboard', ['locale' => $locale]));
    }
}
