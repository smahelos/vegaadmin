<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SessionControllerFeatureTest extends TestCase
{
    #[Test]
    public function check_auth_returns_unauthenticated_when_not_logged_in(): void
    {
        $resp = $this->getJson(
            route('api.auth-check'),
        );
        $assertionResponse = $resp->assertStatus(401);
        $assertionResponse->assertJson(['status' => 'unauthenticated']);
    }

    #[Test]
    public function check_auth_returns_authenticated_when_logged_in(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create(
            ['password' => Hash::make('secret123')],
        );
        $this->actingAs($user, 'web');
        $resp = $this->getJson(
            route('api.auth-check'),
        );
        $assertionResponse = $resp->assertOk();
        $assertionResponse->assertJson(['status' => 'authenticated']);
    }

    #[Test]
    public function refresh_session_returns_session_refreshed(): void
    {
        $resp = $this->postJson(
            route('api.session-refresh'),
        );
        $assertionResponse = $resp->assertOk();
        $assertionResponse->assertJson(['status' => 'session_refreshed']);
    }
}
