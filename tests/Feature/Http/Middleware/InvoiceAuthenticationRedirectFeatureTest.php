<?php

namespace Tests\Feature\Http\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceAuthenticationRedirectFeatureTest extends TestCase
{
    #[Test]
    public function guest_web_request_redirects_to_login_on_invoice_route(): void
    {
        $locale = 'cs';
        $response = $this->get(route('frontend.invoices', ['locale' => $locale]));

        $response->assertRedirect(route('frontend.login', ['locale' => $locale]));
        $response->assertStatus(302);
    }

    #[Test]
    public function guest_json_request_gets_401_on_invoice_route(): void
    {
        $locale = 'cs';
        $response = $this->getJson(route('frontend.invoices', ['locale' => $locale]));

        $response->assertStatus(401);
        $response->assertJson([
            'error' => __('users.auth.unauthenticated'),
            'code' => 401,
        ]);
    }
}
