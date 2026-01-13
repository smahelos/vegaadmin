<?php

namespace App\Infrastructure\Providers\Payment;

use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use App\Models\Subscription;
use GoPay\Api;
use GoPay\Definition\Payment\Currency;
use GoPay\Definition\Payment\PaymentInstrument;
use GoPay\Payments;
use Illuminate\Support\Facades\Log;

/**
 * GoPay payment gateway implementation
 */
class GoPayGateway implements PaymentGatewayInterface
{
    private Payments $gopay;

    public function __construct(?Payments $payments = null)
    {
        // Allow injecting a fake/stub Payments instance for behavior tests without real credentials.
        $this->gopay = $payments ?: Api::payments([
            'goid' => config('services.gopay.goid'),
            'clientId' => config('services.gopay.client_id'),
            'clientSecret' => config('services.gopay.client_secret'),
            'isProductionMode' => config('services.gopay.production', false),
            'gatewayUrl' => config('services.gopay.gateway_url', 'https://gw.sandbox.gopay.com/'),
        ]);
    }

    /**
     * Create a payment for provided payload (array only, no Subscription model)
     */
    public function createPayment(array $paymentData): array
    {
        try {
            $amount = (float)($paymentData['amount'] ?? 0.0);
            $currency = (string)($paymentData['currency'] ?? 'CZK');
            $planName = (string)($paymentData['plan_name'] ?? 'Plan');
            $orderNumber = (string)($paymentData['order_number'] ?? ($this->generateOrderNumberFromPayload($paymentData)));
            $contact = $paymentData['customer_data']['contact'] ?? [
                'first_name' => $paymentData['customer_data']['first_name'] ?? 'Test',
                'last_name' => $paymentData['customer_data']['last_name'] ?? 'User',
                'email' => $paymentData['customer_data']['email'] ?? 'test@example.com',
            ];
            $payment = [
                'amount' => (int) round($amount * 100),
                'currency' => $currency,
                'order_number' => $orderNumber,
                'order_description' => __('subscription.payment_description', [
                    'plan' => $planName,
                ]),
                'items' => [
                    [
                        'name' => $planName,
                        'amount' => (int) round($amount * 100),
                        'count' => 1,
                    ]
                ],
                'return_url' => $paymentData['return_url'] ?? route('payment.return'),
                'notify_url' => $paymentData['notify_url'] ?? route('payment.notify'),
                'lang' => $paymentData['lang'] ?? 'CS',
                'payment_instruments' => $paymentData['payment_instruments'] ?? [
                    PaymentInstrument::PAYMENT_CARD,
                    PaymentInstrument::BANK_ACCOUNT,
                ],
                'payer' => [
                    'default_payment_instrument' => PaymentInstrument::PAYMENT_CARD,
                    'allowed_payment_instruments' => $paymentData['payment_instruments'] ?? [
                        PaymentInstrument::PAYMENT_CARD,
                        PaymentInstrument::BANK_ACCOUNT,
                    ],
                    'contact' => $contact,
                ],
            ];

            $response = $this->gopay->createPayment($payment);

            if ($response->hasSucceed()) {
                $json = $response->json;
                return [
                    'success' => true,
                    'payment_id' => $json['id'],
                    'payment_url' => $json['gw_url'],
                    'data' => $json,
                ];
            }

            Log::error('GoPay payment creation failed', [
                'order_id' => $paymentData['order_id'] ?? null,
                'response' => $response->json,
            ]);

            return [
                'success' => false,
                'error' => 'Payment creation failed',
                'data' => $response->json,
            ];

        } catch (\Exception $e) {
            Log::error('GoPay payment creation exception', [
                'order_id' => $paymentData['order_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process payment return/callback
     */
    public function processPaymentReturn(array $data): array
    {
        try {
            $paymentId = $data['id'] ?? null;
            
            if (!$paymentId) {
                return [
                    'success' => false,
                    'error' => 'Missing payment ID',
                ];
            }

            return $this->verifyPayment($paymentId);

        } catch (\Exception $e) {
            Log::error('GoPay payment return processing failed', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = $this->gopay->getStatus($paymentId);

            if ($response->hasSucceed()) {
                $paymentData = $response->json;
                
                return [
                    'success' => true,
                    'status' => $this->mapGoPayStatus($paymentData['state']),
                    'payment_id' => $paymentId,
                    'data' => $paymentData,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment verification failed',
                'data' => $response->json,
            ];

        } catch (\Exception $e) {
            Log::error('GoPay payment verification failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(string $paymentId): bool
    {
        try {
            $response = $this->gopay->voidAuthorization($paymentId);
            return $response->hasSucceed();

        } catch (\Exception $e) {
            Log::error('GoPay payment cancellation failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment(string $paymentId, float $amount = null): array
    {
        try {
            $refundData = [];
            
            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $response = $this->gopay->refundPayment($paymentId, $refundData);

            if ($response->hasSucceed()) {
                return [
                    'success' => true,
                    'data' => $response->json,
                ];
            }

            return [
                'success' => false,
                'error' => 'Refund failed',
                'data' => $response->json,
            ];

        } catch (\Exception $e) {
            Log::error('GoPay refund failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): string
    {
        $result = $this->verifyPayment($paymentId);
        
        if ($result['success']) {
            return $result['status'];
        }

        return 'unknown';
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return [
            Currency::CZECH_CROWNS,
            Currency::EUROS,
        ];
    }

    /**
     * Check if gateway is available
     */
    public function isAvailable(): bool
    {
        return config('services.gopay.enabled', false) &&
               !empty(config('services.gopay.goid')) &&
               !empty(config('services.gopay.client_id')) &&
               !empty(config('services.gopay.client_secret'));
    }

    /**
     * Get gateway name
     */
    public function getName(): string
    {
        return 'GoPay';
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return __('payment.gateways.gopay');
    }

    /**
     * Generate unique order number for subscription
     */
    private function generateOrderNumberFromPayload(array $paymentData): string
    {
        return 'SUB-' . ($paymentData['order_id'] ?? 'X') . '-' . time();
    }

    /**
     * Map GoPay status to our internal status
     */
    private function mapGoPayStatus(string $goPayStatus): string
    {
        return match ($goPayStatus) {
            'CREATED', 'PAYMENT_METHOD_CHOSEN' => 'pending',
            'PAID' => 'completed',
            'CANCELED' => 'cancelled',
            'TIMEOUTED' => 'failed',
            'PARTIALLY_REFUNDED' => 'partially_refunded',
            'REFUNDED' => 'refunded',
            default => 'unknown',
        };
    }
}
