<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Models\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentControllerCallbackTest extends TestCase
{
    #[Test]
    public function return_endpoint_redirects_on_success_completed(): void
    {
        $this->swap(
            PaymentApplicationServiceInterface::class,
            new class implements PaymentApplicationServiceInterface
            {
                public function initiateSubscription(
                    Subscription $subscription,
                    string $gateway,
                    array $data = [],
                ): SubscriptionPaymentResultDTO {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => true]);
                }

                public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
                {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => true, 'status' => 'completed']);
                }

                public function getAvailableGateways(): array
                {
                    return [];
                }

                public function cancelSubscription(Subscription $subscription): bool
                {
                    return true;
                }
            },
        );

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
        $this->swap(
            PaymentApplicationServiceInterface::class,
            new class implements PaymentApplicationServiceInterface
            {
                public function initiateSubscription(
                    Subscription $subscription,
                    string $gateway,
                    array $data = [],
                ): SubscriptionPaymentResultDTO {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => true]);
                }

                public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
                {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => true, 'status' => 'failed']);
                }

                public function getAvailableGateways(): array
                {
                    return [];
                }

                public function cancelSubscription(Subscription $subscription): bool
                {
                    return true;
                }
            },
        );

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirectContains('subscriptions');
    }

    #[Test]
    public function return_endpoint_redirects_on_pending(): void
    {
        $this->swap(
            PaymentApplicationServiceInterface::class,
            new class implements PaymentApplicationServiceInterface
            {
                public function initiateSubscription(
                    Subscription $subscription,
                    string $gateway,
                    array $data = [],
                ): SubscriptionPaymentResultDTO {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => true]);
                }

                public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
                {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => true, 'status' => 'pending']);
                }

                public function getAvailableGateways(): array
                {
                    return [];
                }

                public function cancelSubscription(Subscription $subscription): bool
                {
                    return true;
                }
            },
        );

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirectContains('subscriptions');
    }

    #[Test]
    public function notify_endpoint_returns_ok_on_success(): void
    {
        $paymentApp = new class implements PaymentApplicationServiceInterface
        {
            public function initiateSubscription(
                Subscription $subscription,
                string $gateway,
                array $data = [],
            ): SubscriptionPaymentResultDTO {
                return SubscriptionPaymentResultDTO::fromArray(['success' => true]);
            }

            /** @var list<array<string, mixed>> */
            public array $payloads = [];

            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
            {
                $this->payloads[] = $payload;

                return SubscriptionPaymentResultDTO::fromArray(['success' => true, 'status' => 'completed']);
            }

            public function getAvailableGateways(): array
            {
                return [];
            }

            public function cancelSubscription(Subscription $subscription): bool
            {
                return true;
            }
        };
        $this->swap(PaymentApplicationServiceInterface::class, $paymentApp);

        $response = $this->getJson(
            '/payment/notify?gateway=gopay&id=3000006529&order_id=12&state=PAID',
        );
        $assertionResponse = $response->assertOk();
        $assertionResponse->assertJson(['status' => 'ok']);

        // Only the payment id reaches the application; its state comes from the gateway
        $this->assertSame(
            [['id' => '3000006529']],
            $paymentApp->payloads,
        );
    }

    #[Test]
    public function notify_endpoint_returns_error_on_failure(): void
    {
        $this->swap(
            PaymentApplicationServiceInterface::class,
            new class implements PaymentApplicationServiceInterface
            {
                public function initiateSubscription(
                    Subscription $subscription,
                    string $gateway,
                    array $data = [],
                ): SubscriptionPaymentResultDTO {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => true]);
                }

                public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
                {
                    return SubscriptionPaymentResultDTO::fromArray(['success' => false, 'error' => 'x']);
                }

                public function getAvailableGateways(): array
                {
                    return [];
                }

                public function cancelSubscription(Subscription $subscription): bool
                {
                    return true;
                }
            },
        );

        // GoPay repeats a notification that does not get HTTP 200
        $response = $this->getJson('/payment/notify?gateway=gopay&id=3000006529');
        $response->assertStatus(503);
        $response->assertJson(['status' => 'error']);
    }
}
