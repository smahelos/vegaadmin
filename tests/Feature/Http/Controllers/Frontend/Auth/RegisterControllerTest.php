<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend\Auth;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RegisterControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
    }

    #[Test]
    public function a_guest_opens_the_registration_form(): void
    {
        $response = $this->get(
            route('frontend.register'),
        );
        $response->assertOk();
        $response->assertSeeLivewire('user.registration-form');
    }

    #[Test]
    public function a_signed_in_user_is_sent_away_from_the_registration(): void
    {
        $httpClient = $this->actingAs(User::factory()->create());
        $httpResponse = $httpClient->get(
            route('frontend.register'),
        );
        $httpResponse->assertRedirect();
    }
}
