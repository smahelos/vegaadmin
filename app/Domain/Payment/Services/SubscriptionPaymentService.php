<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface;
use App\Domain\Payment\Contracts\PaymentDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Infrastructure\Providers\Payment\GoPayGateway;
use App\Domain\Shared\Log\Contracts\LogInterface;
use App\Domain\Payment\DTO\PaymentWriteData;

/**
 * Service for handling subscription payments in Payment domain.
 *
 * Note: Domain method signatures use scalar IDs and arrays. Internally we still
 * operate on Eloquent models returned by repositories. Future iterations can
 * extract read repositories / data mappers to remove these model touches.
 */
class SubscriptionPaymentService implements SubscriptionPaymentServiceInterface
{
    /* @var GatewayRegistryInterface */
    private GatewayRegistryInterface $registry;

    /* @var TransactionBoundaryInterface */
    private TransactionBoundaryInterface $tx;

    /* @var PaymentDtoWriteRepositoryInterface */
    private PaymentDtoWriteRepositoryInterface $paymentsWrite;
    
    /* @var PaymentDtoReadRepositoryInterface */
    private PaymentDtoReadRepositoryInterface $paymentsRead;

    /* @var SubscriptionDtoWriteRepositoryInterface */
    private SubscriptionDtoWriteRepositoryInterface $subscriptionsWrite;

    /* @var SubscriptionDtoReadRepositoryInterface */
    private SubscriptionDtoReadRepositoryInterface $subscriptionsRead;

    /* @var LogInterface */
    private LogInterface $logger;

    public function __construct(
        GatewayRegistryInterface $registry,
        GoPayGateway $goPayGateway,
        TransactionBoundaryInterface $tx,
        PaymentDtoWriteRepositoryInterface $paymentsWrite,
        PaymentDtoReadRepositoryInterface $paymentsRead,
        SubscriptionDtoWriteRepositoryInterface $subscriptionsWrite,
        SubscriptionDtoReadRepositoryInterface $subscriptionsRead,
        LogInterface $logger
    ) {
        // Inject registry (allows test stubs) and register built-in gateways here.
        $this->registry = $registry;
        $this->registry->register('gopay', $goPayGateway);
        $this->tx = $tx;
        $this->paymentsWrite = $paymentsWrite;
        $this->paymentsRead = $paymentsRead;
        $this->subscriptionsWrite = $subscriptionsWrite;
        $this->subscriptionsRead = $subscriptionsRead;
        $this->logger = $logger;
    }

    /**
     * Process subscription payment by subscription ID and provided payload.
     */
    public function processSubscriptionPayment(int $subscriptionId, string $gateway, array $paymentData): array
    {
        try {
            return $this->tx->transaction(function () use ($subscriptionId, $gateway, $paymentData) {
                $gatewayInstance = $this->registry->get($gateway);
                if (!$gatewayInstance) {
                    throw new \InvalidArgumentException("Unsupported gateway: {$gateway}");
                }

                // Load subscription
                $subscription = $this->subscriptionsRead->findById($subscriptionId);
                if (!$subscription) {
                    return [
                        'success' => false,
                        'error' => 'Subscription not found',
                    ];
                }

                // Create payment record
                $payment = $this->paymentsWrite->create(new PaymentWriteData(
                    subscription_id: $subscriptionId,
                    amount: $paymentData['amount'] ?? (float)($subscription->amount ?? 0.0),
                    currency: $paymentData['currency'] ?? (string)($subscription->currency ?? 'CZK'),
                    gateway: $gateway,
                    status: 'pending',
                    gateway_payment_id: null,
                ));

                // Create payment through gateway
                $payload = array_merge($paymentData, [
                    'order_id' => $payment->id,
                    'plan_name' => optional($subscription->subscriptionPlan)->name,
                ]);
                $result = $gatewayInstance->createPayment($payload);

                if ($result['success'] ?? false) {
                    $this->paymentsWrite->updateById($payment->id, new PaymentWriteData(
                        gateway_payment_id: $result['payment_id'] ?? null,
                        status: $result['status'] ?? 'pending',
                    ));

                    $this->logger->log(
                        'info', 
                        'Subscription payment initiated successfully', 
                        [
                            'payment_id' => $payment->id,
                            'subscription_id' => $subscriptionId,
                            'gateway' => $gateway,
                            'amount' => $payment->amount,
                        ]
                    );

                    return [
                        'success' => true,
                        'payment_id' => $payment->id,
                        'gateway_payment_id' => $result['payment_id'] ?? null,
                        'subscription_id' => $subscriptionId,
                        'gateway' => $gateway,
                        'redirect_url' => $result['redirect_url'] ?? ($result['payment_url'] ?? null),
                        'status' => $result['status'] ?? 'pending',
                    ];
                }

                $this->logger->log(
                    'error', 
                    'Subscription payment failed', 
                    [
                        'payment_id' => $payment->id,
                        'subscription_id' => $subscriptionId,
                        'gateway' => $gateway,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]
                );

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Payment processing failed',
                ];
            });
        } catch (\Exception $e) {
            $this->logger->log(
                'error', 
                'Subscription payment processing error', 
                [
                    'subscription_id' => $subscriptionId,
                    'gateway' => $gateway,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return [
                'success' => false,
                'error' => 'Payment processing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment callback from gateway.
     */
    public function handlePaymentCallback(string $gateway, array $callbackData): array
    {
        try {
            $gatewayInstance = $this->registry->get($gateway);
            if (!$gatewayInstance) {
                throw new \InvalidArgumentException("Unsupported gateway: {$gateway}");
            }

            // Use the contract method implemented by gateways
            $result = $gatewayInstance->processPaymentReturn($callbackData);

            if (!($result['success'] ?? false)) {
                $this->logger->log(
                    'warning', 
                    'Payment callback handling failed', 
                    [
                        'gateway' => $gateway,
                        'error' => $result['error'] ?? 'Unknown error',
                        'callback_data' => $callbackData,
                    ]
                );

                return $result;
            }

            // Find payment by gateway payment ID or order ID
            $paymentId = $result['order_id'] ?? $callbackData['order_id'] ?? null;
            $gatewayPaymentId = $result['payment_id'] ?? $callbackData['payment_id'] ?? null;

            $payment = null;

            if ($paymentId) {
                $payment = $this->paymentsRead->findById((int) $paymentId);
            } elseif ($gatewayPaymentId) {
                $payment = $this->paymentsRead->findByGatewayPaymentId($gatewayPaymentId);
            }

            if (!$payment) {
                $this->logger->log(
                    'error',
                    'Payment not found for callback', 
                    [
                        'gateway' => $gateway,
                        'payment_id' => $paymentId,
                        'gateway_payment_id' => $gatewayPaymentId,
                        'callback_data' => $callbackData,
                    ]
                );

                return [
                    'success' => false,
                    'error' => 'Payment not found',
                ];
            }

            $oldStatus = $payment->status;
            $newStatus = $result['status'] ?? 'unknown';

            // Update payment status
            $this->paymentsWrite->updateById($payment->id, new PaymentWriteData(
                status: $newStatus,
                gateway_payment_id: $gatewayPaymentId ?: $payment->gateway_payment_id,
            ));

            // Handle subscription status updates
            if ($payment->subscription_id && $newStatus === 'completed' && $oldStatus !== 'completed') {
                $this->activateSubscriptionById((int) $payment->subscription_id);
            } elseif ($payment->subscription_id && $newStatus === 'failed') {
                $this->handleFailedPaymentById((int) $payment->subscription_id);
            }

            $this->logger->log(
                'info', 
                'Payment callback processed successfully', 
                [
                    'payment_id' => $payment->id,
                    'gateway' => $gateway,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]
            );

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $newStatus,
            ];
        } catch (\Exception $e) {
            $this->logger->log(
                'error',
                'Payment callback processing error',
                [
                    'gateway' => $gateway,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'callback_data' => $callbackData,
                ]
            );

            return [
                'success' => false,
                'error' => 'Callback processing error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get supported payment gateways (names only).
     */
    public function getSupportedGateways(): array
    {
        return $this->registry->names();
    }

    /**
     * Get gateway instance by name.
     */
    public function getGateway(string $gateway): ?PaymentGatewayInterface
    {
        return $this->registry->get($gateway);
    }

    /**
     * Activate subscription after successful payment using ID.
     */
    private function activateSubscriptionById(int $subscriptionId): void
    {
        // Read subscription to determine plan duration using read repository
        $subscription = $this->subscriptionsRead->findById($subscriptionId);
        if (!$subscription) {
            $this->logger->log(
                'error', 
                'Subscription not found for activation', 
                [
                    'subscription_id' => $subscriptionId,
                ]
            );
            return;
        }
        $durationDays = (int) ($subscription->subscriptionPlan->duration_days ?? 0);
        $this->subscriptionsWrite->activateById($subscriptionId, $durationDays);

        $this->logger->log(
            'info',
            'Subscription activated',
            [
                'subscription_id' => $subscriptionId,
                'plan' => optional($subscription->subscriptionPlan)->name,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
            ]
        );
    }

    /**
     * Handle failed payment for a subscription ID.
     */
    private function handleFailedPaymentById(int $subscriptionId): void
    {
        // Increment failed payment count or handle according to business logic
        $this->logger->log(
            'warning',
            'Subscription payment failed',
            [
                'subscription_id' => $subscriptionId,
            ]
        );
        // Could implement retry logic, suspension, etc.
    }

    /**
     * Cancel all pending subscription payments.
     */
    public function cancelSubscriptionPayment(int $subscriptionId): bool
    {
        try {
            $pendingPayments = $this->paymentsRead->findPendingBySubscription($subscriptionId);

            $allCancelled = true;

            foreach ($pendingPayments as $payment) {
                $gateway = $this->getGateway($payment->gateway);
                if ($gateway && $payment->gateway_payment_id) {
                    $result = $gateway->cancelPayment($payment->gateway_payment_id);
                    $success = (is_bool($result) && $result === true) || (is_array($result) && (array_key_exists('success', $result) ? (bool) $result['success'] : false));
                    if ($success) {
                        $this->paymentsWrite->updateById($payment->id, new PaymentWriteData(
                            status: 'cancelled',
                        ));
                    } else {
                        $allCancelled = false;
                        $error = is_array($result) ? ($result['error'] ?? 'Unknown error') : 'Gateway error';
                        $this->logger->log(
                            'warning',
                            'Failed to cancel payment',
                            [
                                'payment_id' => $payment->id,
                                'error' => $error,
                            ]
                        );
                    }
                } else {
                    // Mark as cancelled if no gateway payment ID
                    $this->paymentsWrite->updateById($payment->id, new PaymentWriteData(
                        status: 'cancelled',
                    ));
                }
            }

            if ($allCancelled) {
                $this->logger->log(
                    'info',
                    'All subscription payments cancelled',
                    [
                        'subscription_id' => $subscriptionId,
                        'cancelled_payments' => is_array($pendingPayments) ? count($pendingPayments) : (method_exists($pendingPayments, 'count') ? $pendingPayments->count() : 0),
                    ]
                );
            }

            return $allCancelled;
        } catch (\Exception $e) {
            $this->logger->log(
                'error', 
                'Subscription payment cancellation error', 
                [
                    'subscription_id' => $subscriptionId,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Verify a subscription payment by payment ID.
     */
    public function verifySubscriptionPayment(int $paymentId): array
    {
        try {
            $payment = $this->paymentsRead->findById($paymentId);
            if (!$payment) {
                return [
                    'success' => false,
                    'error' => 'Payment not found',
                ];
            }
            $gateway = $this->getGateway($payment->gateway);
            if (!$gateway) {
                return [
                    'success' => false,
                    'error' => 'Gateway not available',
                ];
            }

            if (!$payment->gateway_payment_id) {
                return [
                    'success' => false,
                    'error' => 'No gateway payment ID available',
                ];
            }

            $result = $gateway->verifyPayment($payment->gateway_payment_id);

            if ($result['success'] ?? false) {
                $currentStatus = $payment->status;
                $verifiedStatus = $result['status'] ?? $currentStatus;

                if ($currentStatus !== $verifiedStatus) {
                    $this->paymentsWrite->updateById($payment->id, new PaymentWriteData(
                        status: $verifiedStatus,
                    ));

                    $this->logger->log(
                        'info',
                        'Payment status updated after verification',
                        [
                            'payment_id' => $payment->id,
                            'old_status' => $currentStatus,
                            'new_status' => $verifiedStatus,
                        ]
                    );

                    if ($verifiedStatus === 'completed' && $currentStatus !== 'completed' && $payment->subscription_id) {
                        $this->activateSubscriptionById((int) $payment->subscription_id);
                    }
                }

                return [
                    'success' => true,
                    'status' => $verifiedStatus,
                    'amount' => $result['amount'] ?? $payment->amount,
                    'currency' => $result['currency'] ?? $payment->currency,
                ];
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->log(
                'error',
                'Payment verification error',
                [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]
            );

            return [
                'success' => false,
                'error' => 'Verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available payment gateways metadata.
     */
    public function getAvailableGateways(): array
    {
        $list = [];
        foreach ($this->registry->names() as $name) {
            $meta = method_exists($this->registry, 'metadata') ? $this->registry->metadata($name) : null;
            $gateway = $this->registry->get($name);
            $list[$name] = [
                'name' => $meta['display_name'] ?? ($gateway ? $gateway->getDisplayName() : $name),
                'supported_currencies' => $meta['currencies'] ?? ($gateway ? $gateway->getSupportedCurrencies() : []),
                'supports_recurring' => $meta['recurring'] ?? $this->supportsRecurringPayments($name),
            ];
        }
        return $list;
    }

    public function supportsRecurringPayments(string $gateway): bool
    {
        $gatewayInstance = $this->getGateway($gateway);
        if (!$gatewayInstance) {
            return false;
        }
        return method_exists($gatewayInstance, 'createRecurringPayment') ||
               method_exists($gatewayInstance, 'supportsRecurring');
    }

    public function getPaymentStatus(int $paymentId): array
    {
        try {
            $payment = $this->paymentsRead->findById($paymentId);
            if (!$payment) {
                throw new \RuntimeException('Payment not found');
            }

            return [
                'success' => true,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'gateway' => $payment->gateway,
                'gateway_payment_id' => $payment->gateway_payment_id,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Payment not found: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Refund subscription payment (full or partial).
     */
    public function refundSubscriptionPayment(int $paymentId, ?float $amount = null): array
    {
        try {
            $payment = $this->paymentsRead->findById($paymentId);
            if (!$payment) {
                return [
                    'success' => false,
                    'error' => 'Payment not found',
                ];
            }
            $gateway = $this->getGateway($payment->gateway);
            if (!$gateway) {
                return [
                    'success' => false,
                    'error' => 'Gateway not available',
                ];
            }
            if (!$payment->gateway_payment_id) {
                return [
                    'success' => false,
                    'error' => 'Missing gateway payment id',
                ];
            }
            if (!in_array($payment->status, ['completed', 'partially_refunded'], true)) {
                return [
                    'success' => false,
                    'error' => 'Only completed (or partially refunded) payments can be refunded',
                ];
            }

            if ($amount === null) {
                $remaining = ((float) $payment->amount) - (float) ($payment->refunded_amount ?? 0.0);
                $amount = round($remaining, 2);
            }
            if ($amount <= 0.0) {
                return [
                    'success' => false,
                    'error' => 'Refund amount must be positive',
                ];
            }
            $alreadyRefunded = (float) ($payment->refunded_amount ?? 0.0);
            if ($alreadyRefunded + $amount - (float) $payment->amount > 0.0001) {
                return [
                    'success' => false,
                    'error' => 'Refund amount exceeds remaining balance',
                ];
            }

            $result = $gateway->refundPayment($payment->gateway_payment_id, $amount);
            if (!($result['success'] ?? false)) {
                return $result + ['success' => false];
            }

            // Update local payment state (partial vs full) using Payment model helper
            $this->paymentsWrite->applyRefund($payment->id, $amount);

            $this->logger->log(
                'info',
                'Payment refunded', 
                [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'gateway_payment_id' => $payment->gateway_payment_id,
                ]
            );

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'refunded_amount' => $result['amount'] ?? $amount,
                'currency' => $payment->currency,
            ];
        } catch (\Exception $e) {
            $this->logger->log(
                'error',
                'Refund failed',
                [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]
            );
            return [
                'success' => false,
                'error' => 'Refund error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Read-only diagnostic inspection of a payment.
     */
    public function inspectPayment(int $paymentId): array
    {
        $status = $this->getPaymentStatus($paymentId);
        if (!$status['success']) {
            return $status;
        }
        $gateway = $this->getGateway($status['gateway']);
        if ($gateway && isset($status['gateway_payment_id'])) {
            try {
                $verify = $gateway->verifyPayment($status['gateway_payment_id']);
                if ($verify['success']) {
                    $status['gateway_reported_status'] = $verify['status'];
                }
            } catch (\Exception $e) {
                $status['gateway_report_error'] = $e->getMessage();
            }
        }
        return $status;
    }
}
