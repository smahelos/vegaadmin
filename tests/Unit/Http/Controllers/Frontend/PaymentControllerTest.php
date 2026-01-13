<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\PaymentController;
use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Payment\DTO\RefundResultDTO;
use App\Models\Subscription;
use App\Models\Payment;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    private PaymentController $controller;
    private PaymentApplicationServiceInterface $fakeAppService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeAppService = new class implements PaymentApplicationServiceInterface {
            public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => true]); }
            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => true, 'status' => 'completed']); }
            public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => true]); }
            public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return RefundResultDTO::fromArray(['success' => true]); }
            public function getAvailableGateways(): array { return ['gopay' => ['name' => 'GoPay']]; }
            public function cancelSubscription(Subscription $subscription): bool { return true; }
        };
        $this->controller = new PaymentController($this->fakeAppService);
    }

    #[Test]
    public function controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PaymentController::class, $this->controller);
    }

    #[Test]
    public function constructor_has_expected_signature(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $params = $reflection->getConstructor()->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('paymentApp', $params[0]->getName());
    }

    #[Test]
    public function dependency_property_set(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $prop = $reflection->getProperty('paymentApp');
        $prop->setAccessible(true);
        $this->assertSame($this->fakeAppService, $prop->getValue($this->controller));
    }

    #[Test]
    public function return_method_signature(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('return');
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('request', $method->getParameters()[0]->getName());
        $this->assertNotNull($method->getReturnType());
    }

    #[Test]
    public function notify_method_signature(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('notify');
        $this->assertCount(1, $method->getParameters());
        $this->assertEquals('request', $method->getParameters()[0]->getName());
        $this->assertNotNull($method->getReturnType());
    }

    #[Test]
    public function controller_inherits_base(): void
    {
        $this->assertInstanceOf(\App\Http\Controllers\Controller::class, $this->controller);
    }

    #[Test]
    public function controller_namespace_correct(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $this->assertEquals('App\\Http\\Controllers\\Frontend\\PaymentController', $reflection->getName());
    }
}
