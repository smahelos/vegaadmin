<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SessionControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function check_auth_returns_unauthenticated_when_not_logged_in(): void
    {
        $resp = $this->getJson(route('api.auth-check'));
        $resp->assertStatus(401)->assertJson(['status' => 'unauthenticated']);
    }

    /** @test */
    public function check_auth_returns_authenticated_when_logged_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);
        $this->actingAs($user, 'web');
        $resp = $this->getJson(route('api.auth-check'));
        $resp->assertOk()->assertJson(['status' => 'authenticated']);
    }

    /** @test */
    public function refresh_session_returns_session_refreshed(): void
    {
        $resp = $this->postJson(route('api.session-refresh'));
        $resp->assertOk()->assertJson(['status' => 'session_refreshed']);
    }
}
