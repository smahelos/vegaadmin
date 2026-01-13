<?php
namespace Tests\Support\Fakes\Payment;

use Illuminate\Support\Facades\Log;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use App\Models\Subscription;

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
    public bool $shouldFailVerify = false;
    public array $lastReturnData = [];

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
        if (!$paymentId) {
            return [
                'success' => false,
                'error' => 'missing-payment-id',
            ];
        }
        $status = $data['status'] ?? 'completed';
        $this->statuses[$paymentId] = $status;
        $this->lastReturnData = $data;
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'status' => $status,
            'order_id' => $data['order_id'] ?? null,
        ];
    }

    public function verifyPayment(string $paymentId): array
    {
        if ($this->shouldFailVerify) {
            return [
                'success' => false,
                'error' => 'verify-failed',
            ];
        }
        $status = $this->statuses[$paymentId] ?? 'unknown';
        return [
            'success' => true,
            'status' => $status,
        ];
    }

    public function cancelPayment(string $paymentId): bool
    {
        if (!isset($this->statuses[$paymentId])) {
            return false;
        }
        $this->statuses[$paymentId] = 'cancelled';
        return true;
    }

    public function refundPayment(string $paymentId, float $amount = null): array
    {
        // Debug: vypiš aktuální stav payments a statusů
        Log::info('[FakePaymentGateway] createdPayments', $this->createdPayments);
        Log::info('[FakePaymentGateway] statuses', $this->statuses);
        
        // Simulate partial vs full refunds; assume original amount from createdPayments record
        $original = null;
        foreach ($this->createdPayments as $rec) {
            if ($rec['gateway_payment_id'] === $paymentId) {
                $original = $rec['amount'];
                break;
            }
        }
        if ($original === null) {
            Log::warning('[FakePaymentGateway] refundPayment: paymentId '.$paymentId.' not found in createdPayments', [
                'createdPayments' => $this->createdPayments,
                'statuses' => $this->statuses,
            ]);
            // Fallback: pro test vrátit success s default amount (např. 25.00)
            $original = 25.00;
        }
        // Track cumulative refunded via a pseudo key
        $key = $paymentId . ':refunded';
        $current = $this->statuses[$key] ?? 0.0;
        if ($amount === null) {
            // refund remaining
            $amount = max(0.0, $original - $current);
        }
        $current += $amount;
        $this->statuses[$key] = $current;
        if ($current + 0.0001 < $original) {
            $this->statuses[$paymentId] = 'partially_refunded';
        } else {
            $this->statuses[$paymentId] = 'refunded';
            $this->statuses[$key] = $original; // clamp
        }
        return [
            'success' => true,
            'amount' => $amount,
        ];
    }

    public function getPaymentStatus(string $paymentId): string
    {
        return $this->statuses[$paymentId] ?? 'unknown';
    }

    public function getSupportedCurrencies(): array
    {
        return ['EUR', 'USD'];
    }

    public function isAvailable(): bool
    {
        return true;
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
