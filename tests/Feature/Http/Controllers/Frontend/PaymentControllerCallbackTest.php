<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\Payment\FakeSubscriptionPaymentService;
use Tests\TestCase;

final class PaymentControllerCallbackTest extends TestCase
{
    #[Test]
    public function return_endpoint_redirects_on_success_completed(): void
    {
        $this->answerCallbacksWith(['success' => true, 'status' => 'completed']);

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirect();
        $this->assertStringContainsString(
            '/my-subscription',
            $response->headers->get('Location'),
        );
    }

    #[Test]
    public function return_endpoint_redirects_on_failed(): void
    {
        $this->answerCallbacksWith(['success' => true, 'status' => 'failed']);

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirectContains('subscriptions');
    }

    #[Test]
    public function return_endpoint_redirects_on_pending(): void
    {
        $this->answerCallbacksWith(['success' => true, 'status' => 'pending']);

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirectContains('subscriptions');
    }

    #[Test]
    public function notify_endpoint_returns_ok_on_success(): void
    {
        $payments = $this->answerCallbacksWith(['success' => true, 'status' => 'completed']);

        $response = $this->getJson(
            '/payment/notify?gateway=gopay&id=3000006529&order_id=12&state=PAID',
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertJson(['status' => 'ok']);

        // Only the payment id reaches the payment service; its state comes from the gateway
        $this->assertSame(
            [['id' => '3000006529']],
            $payments->callbacks,
        );
    }

    #[Test]
    public function notify_endpoint_returns_error_on_failure(): void
    {
        $this->answerCallbacksWith(['success' => false, 'error' => 'x']);

        // GoPay repeats a notification that does not get HTTP 200
        $response = $this->getJson('/payment/notify?gateway=gopay&id=3000006529');
        $response->assertStatus(503);
        $response->assertJson(['status' => 'error']);
    }

    #[Test]
    public function notify_endpoint_refuses_a_gateway_that_is_not_a_string(): void
    {
        // The real payment service: it refuses the gateway it does not know
        $response = $this->getJson('/payment/notify?gateway[]=gopay&id=3000006529');
        $response->assertStatus(503);
        $response->assertJson(['status' => 'error']);
    }

    /**
     * @param  array<string, mixed>  $callback
     */
    private function answerCallbacksWith(array $callback): FakeSubscriptionPaymentService
    {
        $payments = new FakeSubscriptionPaymentService(callback: $callback);
        $this->app->instance(SubscriptionPaymentServiceInterface::class, $payments);

        return $payments;
    }
}
