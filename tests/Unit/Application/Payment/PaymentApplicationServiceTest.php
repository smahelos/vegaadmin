<?php

namespace Tests\Unit\Application\Payment;

use App\Application\Payment\Services\PaymentApplicationService;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Payment\DTO\RefundResultDTO;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use App\Models\Subscription;
use App\Models\Payment;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Structure/unit test verifying thin orchestration of PaymentApplicationService.
 */
class PaymentApplicationServiceTest extends TestCase
{
    protected StubSubscriptionPaymentService $subscriptionSvc;
    protected StubQrPaymentService $qrSvc;
    protected PaymentApplicationService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionSvc = new StubSubscriptionPaymentService();
        $this->qrSvc = new StubQrPaymentService();
        $this->paymentService = new PaymentApplicationService($this->subscriptionSvc, $this->qrSvc);
    }

    #[Test]
    public function initiate_subscription_returns_dto_and_logs(): void
    {
        $subscription = new Subscription();
        $subscription->id = 123;
        $this->subscriptionSvc->processReturn = [
            'success' => true,
            'payment_id' => 555,
            'status' => 'pending',
            'payment_url' => 'https://gateway/redirect'
        ];

        $logger = new ArrayLogger();
        Log::swap($logger);

    $dto = $this->paymentService->initiateSubscription($subscription, 'gopay', ['return_url' => 'x']);
        $this->assertInstanceOf(SubscriptionPaymentResultDTO::class, $dto);
        $this->assertTrue($dto->success);
        $this->assertEquals(555, $dto->paymentId);
        $this->assertEquals('pending', $dto->status);
        $this->assertEquals('https://gateway/redirect', $dto->redirectUrl);
        $this->assertCount(1, $this->subscriptionSvc->calls['process']);

        // Logging assertions
        $start = collect($logger->logs)->firstWhere('message', 'usecase.start');
        $success = collect($logger->logs)->firstWhere('message', 'usecase.success');
        $this->assertNotNull($start);
        $this->assertNotNull($success);
        $this->assertEquals('initiate_subscription', $start['context']['use_case']);
        $this->assertEquals(123, $start['context']['subscription_id']);
        $this->assertArrayHasKey('duration_ms', $success['context']);
    }

    #[Test]
    public function handle_callback_wraps_domain_result(): void
    {
        $this->subscriptionSvc->callbackReturn = [
            'success' => true,
            'payment_id' => 77,
            'status' => 'completed'
        ];
        $dto = $this->paymentService->handleCallback('gopay', ['foo' => 'bar']);
        $this->assertTrue($dto->success);
        $this->assertEquals(77, $dto->paymentId);
        $this->assertEquals('completed', $dto->status);
        $this->assertEquals(['gopay',['foo' => 'bar']], $this->subscriptionSvc->calls['callback'][0]);
    }

    #[Test]
    public function verify_wraps_result(): void
    {
        $payment = new Payment();
        $payment->id = 999;
        $this->subscriptionSvc->verifyReturn = [
            'success' => true,
            'status' => 'completed',
            'amount' => 100.0,
            'currency' => 'CZK'
        ];
        $dto = $this->paymentService->verify($payment);
        $this->assertTrue($dto->success);
        $this->assertEquals('completed', $dto->status);
        $this->assertEquals(100.0, $dto->amount);
        $this->assertEquals('CZK', $dto->currency);
    }

    #[Test]
    public function refund_wraps_result(): void
    {
        $payment = new Payment();
        $payment->id = 42;
        $this->subscriptionSvc->refundReturn = [
            'success' => true,
            'payment_id' => 42,
            'refunded_amount' => 10.5,
            'currency' => 'CZK',
            'status' => 'partially_refunded'
        ];
        $dto = $this->paymentService->refund($payment, 10.5);
        $this->assertInstanceOf(RefundResultDTO::class, $dto);
        $this->assertTrue($dto->success);
        $this->assertEquals(42, $dto->paymentId);
        $this->assertEquals(10.5, $dto->refundedAmount);
        $this->assertEquals('partially_refunded', $dto->status);
    }

    #[Test]
    public function failure_path_propagates_error_in_dto(): void
    {
        $subscription = new Subscription();
        $subscription->id = 1;
        $this->subscriptionSvc->processReturn = [
            'success' => false,
            'error' => 'gateway_down'
        ];
        $dto = $this->paymentService->initiateSubscription($subscription, 'gopay');
        $this->assertFalse($dto->success);
        $this->assertEquals('gateway_down', $dto->error);
    }
}

/** Lightweight stub for SubscriptionPaymentServiceInterface */
class StubSubscriptionPaymentService implements SubscriptionPaymentServiceInterface
{
    public array $processReturn = ['success' => true, 'payment_id' => 1, 'status' => 'pending'];
    public array $callbackReturn = ['success' => true, 'payment_id' => 2, 'status' => 'completed'];
    public array $verifyReturn = ['success' => true, 'status' => 'completed'];
    public array $refundReturn = ['success' => true, 'payment_id' => 3, 'refunded_amount' => 5.0];
    public array $calls = [
        'process' => [], 'callback' => [], 'verify' => [], 'refund' => [], 'cancel' => []
    ];

    public function processSubscriptionPayment(int $subscriptionId, string $gateway, array $paymentData): array
    {
        $this->calls['process'][] = [$gateway, $paymentData];
        return $this->processReturn;
    }
    public function handlePaymentCallback(string $gateway, array $callbackData): array
    {
        $this->calls['callback'][] = [$gateway, $callbackData];
        return $this->callbackReturn;
    }
    public function verifySubscriptionPayment(int $paymentId): array
    {
        $this->calls['verify'][] = [$paymentId];
        return $this->verifyReturn;
    }
    public function refundSubscriptionPayment(int $paymentId, ?float $amount = null): array
    {
        $this->calls['refund'][] = [$paymentId, $amount];
        return $this->refundReturn;
    }
    public function cancelSubscriptionPayment(int $subscriptionId): bool
    {
        $this->calls['cancel'][] = [$subscriptionId];
        return true;
    }
    public function getSupportedGateways(): array { return ['gopay']; }
    public function getGateway(string $gateway): ?PaymentGatewayInterface { return null; }
    public function getAvailableGateways(): array { return [['name' => 'gopay']]; }
    public function supportsRecurringPayments(string $gateway): bool { return true; }
    public function getPaymentStatus(int $paymentId): array { return ['success' => true, 'payment_id' => $paymentId, 'status' => 'completed']; }
    public function inspectPayment(int $paymentId): array { return ['success' => true]; }
}

/** Lightweight stub for QrPaymentServiceInterface */
class StubQrPaymentService implements QrPaymentServiceInterface
{
    public function generateQrCodeBase64($invoice): ?string { return null; }
    public function hasRequiredPaymentInfo(\App\Domain\Payment\DTO\QrPaymentPayload $qrPaymentPayload): bool { return true; }
    public function generateQrStringForCountry($invoice, string $countryCode): ?string { return 'QR'; }
    public function getSupportedCountries(): array { return ['CZ']; }
}

/** In-memory logger capturing logs for assertions */
class ArrayLogger implements LoggerInterface
{
    public array $logs = [];
    public function emergency(string|\Stringable $message, array $context = []): void { $this->log('emergency', $message, $context); }
    public function alert(string|\Stringable $message, array $context = []): void { $this->log('alert', $message, $context); }
    public function critical(string|\Stringable $message, array $context = []): void { $this->log('critical', $message, $context); }
    public function error(string|\Stringable $message, array $context = []): void { $this->log('error', $message, $context); }
    public function warning(string|\Stringable $message, array $context = []): void { $this->log('warning', $message, $context); }
    public function notice(string|\Stringable $message, array $context = []): void { $this->log('notice', $message, $context); }
    public function info(string|\Stringable $message, array $context = []): void { $this->log('info', $message, $context); }
    public function debug(string|\Stringable $message, array $context = []): void { $this->log('debug', $message, $context); }
    public function log($level, string|\Stringable $message, array $context = []): void { $this->logs[] = ['level'=>$level,'message'=>(string)$message,'context'=>$context]; }
}
