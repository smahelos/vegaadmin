<?php

namespace Tests\Feature\Domain\Payment\Providers\PaymentGateways;

use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use App\Models\Subscription;
use App\Infrastructure\Providers\Payment\GoPayGateway;
use Tests\Support\Stubs\Payment\StubGoPayPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoPayGatewayFeatureTest extends TestCase
{
    use RefreshDatabase;

    private GoPayGateway $gateway;
    private StubGoPayPayments $stub;

    protected function setUp(): void
    {
    parent::setUp();
    $this->stub = new StubGoPayPayments();
    $this->gateway = new GoPayGateway($this->stub); // inject stub
    }

    #[Test]
    public function gateway_implements_payment_gateway_interface(): void
    {
        $this->assertInstanceOf(PaymentGatewayInterface::class, $this->gateway);
    }

    #[Test]
    public function gateway_has_correct_name(): void
    {
        $name = $this->gateway->getName();
        
        $this->assertIsString($name);
        $this->assertEquals('GoPay', $name);
    }

    #[Test]
    public function gateway_supports_eur_currency(): void
    {
        $currencies = $this->gateway->getSupportedCurrencies();
        
        $this->assertIsArray($currencies);
        $this->assertContains('EUR', $currencies);
    }

    #[Test]
    public function gateway_availability_check_returns_boolean(): void
    {
        $isAvailable = $this->gateway->isAvailable();
        
        $this->assertIsBool($isAvailable);
        // NOTE: Without proper GoPay credentials, this will likely return false
        // which is expected behavior for this test environment
    }

    #[Test]
    public function gateway_methods_have_correct_return_types(): void
    {
        $reflection = new \ReflectionClass($this->gateway);
        
        // Test getName method
    $method = $reflection->getMethod('getName');
    $returnType = $method->getReturnType();
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        
        // Test getSupportedCurrencies method
    $method = $reflection->getMethod('getSupportedCurrencies');
    $returnType = $method->getReturnType();
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        
        // Test isAvailable method
    $method = $reflection->getMethod('isAvailable');
    $returnType = $method->getReturnType();
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        
        // Test createPayment method
    $method = $reflection->getMethod('createPayment');
    $returnType = $method->getReturnType();
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        
        // Test getPaymentStatus method
    $method = $reflection->getMethod('getPaymentStatus');
    $returnType = $method->getReturnType();
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
    }

    #[Test]
    public function create_payment_requires_payload_data(): void
    {
        $subscription = Subscription::factory()->create();
        $paymentData = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
            'return_url' => 'https://example.com/return',
            'notify_url' => 'https://example.com/notify',
        ];

        $result = $this->gateway->createPayment($paymentData);
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('payment_url', $result);
        $this->assertArrayHasKey('payment_id', $result);
    }

    #[Test]
    public function cancel_payment_returns_boolean(): void
    {
        $subscription = Subscription::factory()->create();
        $payload = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $paymentId = $create['payment_id'];
        $result = $this->gateway->cancelPayment($paymentId);
        $this->assertIsBool($result);
        $this->assertTrue($result); // stub returns success
    }

    #[Test]
    public function verify_payment_returns_array(): void
    {
        $subscription = Subscription::factory()->create();
        $payload = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $paymentId = $create['payment_id'];
        $this->stub->nextVerifyState = 'PAID';
        $result = $this->gateway->verifyPayment($paymentId);
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('completed', $result['status']);
    }

    #[Test]
    public function get_payment_status_with_invalid_id_handles_gracefully(): void
    {
        $status = $this->gateway->getPaymentStatus('does-not-exist');
        $this->assertIsString($status);
        $this->assertEquals('unknown', $status);
    }

    #[Test]
    public function process_payment_return_requires_data_array(): void
    {
        $subscription = Subscription::factory()->create();
        $payload = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $paymentId = $create['payment_id'];
        $this->stub->nextVerifyState = 'PAID';
        $result = $this->gateway->processPaymentReturn(['id' => $paymentId]);
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('completed', $result['status']);
    }

    #[Test]
    public function create_payment_failure_flag(): void
    {
        $subscription = Subscription::factory()->create();
        $payload = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
        ];
        $this->stub->failCreate = true;
        $result = $this->gateway->createPayment($payload);
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function verify_payment_failure_flag(): void
    {
        $subscription = Subscription::factory()->create();
        $payload = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $paymentId = $create['payment_id'];
        $this->stub->failVerify = true;
        $result = $this->gateway->verifyPayment($paymentId);
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function cancel_payment_failure_flag(): void
    {
        $subscription = Subscription::factory()->create();
        $payload = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $paymentId = $create['payment_id'];
        $this->stub->failCancel = true;
        $result = $this->gateway->cancelPayment($paymentId);
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    #[Test]
    public function refund_payment_failure_flag(): void
    {
        $subscription = Subscription::factory()->create();
        $payload = [
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $paymentId = $create['payment_id'];
        $this->stub->failRefund = true;
        $result = $this->gateway->refundPayment($paymentId, 10.0);
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
