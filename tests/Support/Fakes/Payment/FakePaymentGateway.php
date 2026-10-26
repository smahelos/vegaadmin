<?php

namespace Tests\Support\Fakes\Payment;

use App\Domain\Payment\Contracts\PaymentGatewayInterface;

/**
 * Simple fake gateway used for behavior tests to simulate success / failure without external API calls.
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    /** @var array<int, array<string,mixed>> */
    public array $createdPayments = [];

    /** @var array<string, string> */
    public array $statuses = [];

    public bool $shouldFailCreate = false;

    public bool $shouldFailReturn = false;

    public function createPayment(array $paymentData): array
    {
        if ($this->shouldFailCreate) {
            return [
                'success' => false,
                'error' => 'create-failed',
            ];
        }

        $id = count($this->createdPayments) + 1;
        $gatewayPaymentId = 'FAKE-' . $id;
        $this->createdPayments[$id] = [
            'subscription_id' => $paymentData['subscription_id'] ?? null,
            'amount' => $paymentData['amount'] ?? null,
            'currency' => $paymentData['currency'] ?? null,
            'gateway_payment_id' => $gatewayPaymentId,
        ];
        $this->statuses[$gatewayPaymentId] = 'pending';

        return [
            'success' => true,
            'payment_id' => $gatewayPaymentId,
            'status' => 'pending',
            'redirect_url' => 'https://fake.test/redirect/' . $gatewayPaymentId,
        ];
    }

    public function processPaymentReturn(array $data): array
    {
        if ($this->shouldFailReturn) {
            return [
                'success' => false,
                'error' => 'return-failed',
            ];
        }
        $paymentId = $data['payment_id'] ?? $data['id'] ?? null;
        if (! $paymentId) {
            return [
                'success' => false,
                'error' => 'missing-payment-id',
            ];
        }
        $status = $data['status'] ?? 'completed';
        $this->statuses[$paymentId] = $status;

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'status' => $status,
            'order_id' => $data['order_id'] ?? null,
        ];
    }

    public function cancelPayment(string $paymentId): bool
    {
        if (! isset($this->statuses[$paymentId])) {
            return false;
        }
        $this->statuses[$paymentId] = 'cancelled';

        return true;
    }

    public function getSupportedCurrencies(): array
    {
        return ['EUR', 'USD'];
    }

    public function getName(): string
    {
        return 'fake';
    }

    public function getDisplayName(): string
    {
        return 'Fake Gateway';
    }
}
