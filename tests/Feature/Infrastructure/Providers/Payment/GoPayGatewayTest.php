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
    public function supports_czech_crowns_and_euros(): void
    {
        $this->assertSame(['CZK', 'EUR'], $this->gateway->getSupportedCurrencies());
    }

    private function createPayment(): string
    {
        return $this->gateway->createPayment(['amount' => 25.00])['payment_id'];
    }
}
