<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Payment;

use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;

/**
 * Answers for the gateway work and records what the use cases passed to it.
 */
final class FakeSubscriptionPaymentService implements SubscriptionPaymentServiceInterface
{
    public const REDIRECT_URL = 'https://gate.test/1';

    /** @var list<array<string, mixed>> */
    public array $initiated = [];

    /** @var list<array<string, mixed>> */
    public array $callbacks = [];

    /** @var list<int> */
    public array $cancelled = [];

    /**
     * @param  array<string, mixed>  $payment
     * @param  array<string, mixed>  $callback
     */
    public function __construct(
        private readonly array $payment = ['success' => true, 'redirect_url' => self::REDIRECT_URL],
        private readonly array $callback = ['success' => true, 'status' => 'completed'],
    ) {}

    public function processSubscriptionPayment(
        int $subscriptionId,
        string $gateway,
        array $paymentData,
    ): array {
        $this->initiated[] = $paymentData;

        return $this->payment;
    }

    public function handlePaymentCallback(string $gateway, array $callbackData): array
    {
        $this->callbacks[] = $callbackData;

        return $this->callback;
    }

    public function cancelSubscriptionPayment(int $subscriptionId): bool
    {
        $this->cancelled[] = $subscriptionId;

        return true;
    }

    public function getAvailableGateways(): array
    {
        return [
            'gopay' => [
                'name' => 'GoPay',
                'supported_currencies' => ['CZK', 'EUR'],
                'supports_recurring' => false,
            ],
        ];
    }
}
