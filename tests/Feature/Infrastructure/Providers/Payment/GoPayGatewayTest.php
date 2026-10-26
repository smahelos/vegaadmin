<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Providers\Payment;

use App\Infrastructure\Providers\Payment\GoPayGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\GoPay\FakeGoPayPayments;
use Tests\TestCase;

final class GoPayGatewayTest extends TestCase
{
    private FakeGoPayPayments $payments;

    private GoPayGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payments = new FakeGoPayPayments;
        $this->gateway = new GoPayGateway($this->payments);
    }

    #[Test]
    public function creates_a_payment_in_cents_and_returns_the_gateway_url(): void
    {
        $result = $this->gateway->createPayment([
            'amount' => 25.50,
            'currency' => 'EUR',
            'order_number' => 'SUB-1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('GP-1', $result['payment_id']);
        $this->assertSame('https://gate.gopay.test/GP-1', $result['payment_url']);
        $this->assertSame(2550, $this->payments->created[0]['amount']);
        $this->assertSame('EUR', $this->payments->created[0]['currency']);
        $this->assertSame('SUB-1', $this->payments->created[0]['order_number']);
    }

    #[Test]
    public function reports_a_payment_the_gateway_refused(): void
    {
        $this->payments->failCreate = true;

        $result = $this->gateway->createPayment(['amount' => 25.00]);

        $this->assertFalse($result['success']);
        $this->assertSame('Payment creation failed', $result['error']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function gatewayStates(): iterable
    {
        yield 'created' => ['CREATED', 'pending'];
        yield 'payment method chosen' => ['PAYMENT_METHOD_CHOSEN', 'pending'];
        yield 'paid' => ['PAID', 'completed'];
        yield 'canceled' => ['CANCELED', 'cancelled'];
        yield 'timed out' => ['TIMEOUTED', 'failed'];
        yield 'partially refunded' => ['PARTIALLY_REFUNDED', 'partially_refunded'];
        yield 'refunded' => ['REFUNDED', 'refunded'];
        yield 'unknown' => ['AUTHORIZED', 'unknown'];
    }

    #[Test]
    #[DataProvider('gatewayStates')]
    public function maps_the_gateway_state_of_a_verified_payment(string $gatewayState, string $status): void
    {
        $paymentId = $this->createPayment();
        $this->payments->setState($paymentId, $gatewayState);

        $result = $this->gateway->verifyPayment($paymentId);

        $this->assertTrue($result['success']);
        $this->assertSame($status, $result['status']);
        $this->assertSame($paymentId, $result['payment_id']);
    }

    #[Test]
    public function reports_a_verification_the_gateway_refused(): void
    {
        $paymentId = $this->createPayment();
        $this->payments->failStatus = true;

        $result = $this->gateway->verifyPayment($paymentId);

        $this->assertFalse($result['success']);
        $this->assertSame('unknown', $this->gateway->getPaymentStatus($paymentId));
    }

    #[Test]
    public function the_status_of_an_unknown_payment_is_unknown(): void
    {
        $this->assertSame('unknown', $this->gateway->getPaymentStatus('GP-404'));
    }

    #[Test]
    public function a_payment_return_asks_the_gateway_for_the_state(): void
    {
        $paymentId = $this->createPayment();
        $this->payments->setState($paymentId, 'PAID');

        $result = $this->gateway->processPaymentReturn(['id' => $paymentId, 'state' => 'CANCELED']);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['status']);
    }

    #[Test]
    public function a_payment_return_without_a_payment_id_fails(): void
    {
        $result = $this->gateway->processPaymentReturn([]);

        $this->assertFalse($result['success']);
        $this->assertSame('Missing payment ID', $result['error']);
    }

    #[Test]
    public function cancels_a_payment(): void
    {
        $paymentId = $this->createPayment();

        $this->assertTrue($this->gateway->cancelPayment($paymentId));
        $this->assertSame('cancelled', $this->gateway->getPaymentStatus($paymentId));
    }

    #[Test]
    public function reports_a_cancellation_the_gateway_refused(): void
    {
        $paymentId = $this->createPayment();
        $this->payments->failCancel = true;

        $this->assertFalse($this->gateway->cancelPayment($paymentId));
        $this->assertSame('pending', $this->gateway->getPaymentStatus($paymentId));
    }

    #[Test]
    public function partial_refunds_add_up_until_the_rest_is_refunded(): void
    {
        $paymentId = $this->createPayment();

        $this->assertTrue($this->gateway->refundPayment($paymentId, 3.00)['success']);
        $this->assertSame('partially_refunded', $this->gateway->getPaymentStatus($paymentId));

        $this->assertTrue($this->gateway->refundPayment($paymentId, 2.00)['success']);
        $this->assertSame('partially_refunded', $this->gateway->getPaymentStatus($paymentId));

        $this->assertTrue($this->gateway->refundPayment($paymentId)['success']);
        $this->assertSame('refunded', $this->gateway->getPaymentStatus($paymentId));
    }

    #[Test]
    public function reports_a_refund_the_gateway_refused(): void
    {
        $paymentId = $this->createPayment();
        $this->payments->failRefund = true;

        $result = $this->gateway->refundPayment($paymentId, 10.00);

        $this->assertFalse($result['success']);
        $this->assertSame('Refund failed', $result['error']);
    }

    #[Test]
    public function supports_czech_crowns_and_euros(): void
    {
        $this->assertSame(['CZK', 'EUR'], $this->gateway->getSupportedCurrencies());
    }

    #[Test]
    public function is_available_only_when_enabled_with_credentials(): void
    {
        config([
            'services.gopay.enabled' => true,
            'services.gopay.goid' => '8123456789',
            'services.gopay.client_id' => 'client',
            'services.gopay.client_secret' => 'secret',
        ]);
        $this->assertTrue($this->gateway->isAvailable());

        config(['services.gopay.client_secret' => '']);
        $this->assertFalse($this->gateway->isAvailable());

        config([
            'services.gopay.client_secret' => 'secret',
            'services.gopay.enabled' => false,
        ]);
        $this->assertFalse($this->gateway->isAvailable());
    }

    private function createPayment(): string
    {
        return $this->gateway->createPayment(['amount' => 25.00])['payment_id'];
    }
}
