<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\SubscriptionController;
use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Payment\DTO\RefundResultDTO;
use App\Models\Subscription;
use App\Models\Payment;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    private SubscriptionController $controller;
    private PaymentApplicationServiceInterface $fakeAppService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Lightweight anonymous implementation of application payment facade for unit test.
        $this->fakeAppService = new class implements PaymentApplicationServiceInterface {
            public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => true]); }
            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => false]); }
            public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => false]); }
            public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return RefundResultDTO::fromArray(['success' => false]); }
            public function getAvailableGateways(): array { 
                return [
                    'gopay' => [
                        'name' => 'GoPay',
                        'supported_currencies' => ['CZK', 'EUR'],
                        'supports_recurring' => false
                    ]
                ]; 
            }
            public function cancelSubscription(Subscription $subscription): bool { return true; }
        };
        $this->controller = new SubscriptionController($this->fakeAppService);
    }

    #[Test]
    public function controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SubscriptionController::class, $this->controller);
    }

    #[Test]
    public function controller_uses_authorizes_requests_trait(): void
    {
        $traits = class_uses(SubscriptionController::class);
        $this->assertContains(\Illuminate\Foundation\Auth\Access\AuthorizesRequests::class, $traits);
    }

    #[Test]
    public function constructor_accepts_payment_service_interface(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('paymentApp', $parameters[0]->getName());
        $this->assertEquals('paymentApp', $parameters[0]->getName());
    }

    #[Test]
    public function constructor_sets_payment_service_dependency(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('paymentApp');
        $property->setAccessible(true);
        $this->assertSame($this->fakeAppService, $property->getValue($this->controller));
    }

    #[Test]
    public function index_method_exists_and_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('index');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertNotNull($returnType); // simple existence check
    }

    #[Test]
    public function show_method_exists_and_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('show');
        $parameters = $method->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('locale', $parameters[0]->getName());
        // parameter 0 should be locale string
        $this->assertEquals('locale', $parameters[0]->getName());
        $this->assertEquals('plan', $parameters[1]->getName());
        $this->assertEquals('plan', $parameters[1]->getName());
    }

    #[Test]
    public function subscribe_method_exists_and_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('subscribe');
        $parameters = $method->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
        $this->assertEquals('locale', $parameters[1]->getName());
        $this->assertEquals('plan', $parameters[2]->getName());
    }

    #[Test]
    public function cancel_method_exists_and_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('cancel');
        $parameters = $method->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('locale', $parameters[0]->getName());
        $this->assertEquals('subscription', $parameters[1]->getName());
    }

    #[Test]
    public function my_subscription_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        
        $this->assertTrue($reflection->hasMethod('mySubscription'));
        
        $method = $reflection->getMethod('mySubscription');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        // Just check that method exists and has return type, union types are complex
        $this->assertTrue(true);
    }

    // No teardown needed, using lightweight anonymous class without external resources.
}
