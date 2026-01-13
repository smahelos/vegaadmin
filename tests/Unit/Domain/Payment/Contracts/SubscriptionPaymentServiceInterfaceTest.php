<?php

namespace Tests\Unit\Domain\Payment\Contracts;

use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPaymentServiceInterfaceTest extends TestCase
{
    #[Test]
    public function interface_exists(): void
    {
        $this->assertTrue(interface_exists(SubscriptionPaymentServiceInterface::class));
    }

    #[Test]
    public function interface_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $this->assertEquals('App\\Domain\\Payment\\Contracts\\SubscriptionPaymentServiceInterface', $reflection->getName());
    }

    #[Test]
    public function interface_defines_methods(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        foreach (['processSubscriptionPayment','handlePaymentCallback','cancelSubscriptionPayment','verifySubscriptionPayment','getAvailableGateways','supportsRecurringPayments'] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing method {$method}");
        }
    }

    #[Test]
    public function handle_payment_callback_has_correct_parameters(): void
    {
        $method = (new \ReflectionClass(SubscriptionPaymentServiceInterface::class))->getMethod('handlePaymentCallback');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('gateway', $parameters[0]->getName());
        $this->assertEquals('string', (string)$parameters[0]->getType());
        $this->assertEquals('callbackData', $parameters[1]->getName());
        $this->assertEquals('array', (string)$parameters[1]->getType());
    }

    #[Test]
    public function interface_defines_process_subscription_payment_method(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $this->assertTrue($reflection->hasMethod('processSubscriptionPayment'));
        
        $method = $reflection->getMethod('processSubscriptionPayment');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function interface_defines_cancel_subscription_payment_method(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $this->assertTrue($reflection->hasMethod('cancelSubscriptionPayment'));
        
        $method = $reflection->getMethod('cancelSubscriptionPayment');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function interface_defines_verify_subscription_payment_method(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $this->assertTrue($reflection->hasMethod('verifySubscriptionPayment'));
        
        $method = $reflection->getMethod('verifySubscriptionPayment');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function interface_defines_get_available_gateways_method(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $this->assertTrue($reflection->hasMethod('getAvailableGateways'));
        
        $method = $reflection->getMethod('getAvailableGateways');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function interface_defines_handle_payment_callback_method(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $this->assertTrue($reflection->hasMethod('handlePaymentCallback'));
        
        $method = $reflection->getMethod('handlePaymentCallback');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function process_subscription_payment_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $method = $reflection->getMethod('processSubscriptionPayment');
        $parameters = $method->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('subscriptionId', $parameters[0]->getName());
        $this->assertEquals('gateway', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        $this->assertEquals('paymentData', $parameters[2]->getName());
        $this->assertEquals('array', $parameters[2]->getType()->getName());
    }

    #[Test]
    public function cancel_subscription_payment_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $method = $reflection->getMethod('cancelSubscriptionPayment');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('subscriptionId', $parameters[0]->getName());
    }

    #[Test]
    public function verify_subscription_payment_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $method = $reflection->getMethod('verifySubscriptionPayment');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('paymentId', $parameters[0]->getName());
    }

    #[Test]
    public function get_available_gateways_has_no_parameters(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        $method = $reflection->getMethod('getAvailableGateways');
        $parameters = $method->getParameters();
        
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function methods_have_correct_return_types(): void
    {
        $reflection = new \ReflectionClass(SubscriptionPaymentServiceInterface::class);
        
        // processSubscriptionPayment should return array
        $processMethod = $reflection->getMethod('processSubscriptionPayment');
        $this->assertEquals('array', $processMethod->getReturnType()->getName());
        
        // cancelSubscriptionPayment should return bool
        $cancelMethod = $reflection->getMethod('cancelSubscriptionPayment');
        $this->assertEquals('bool', $cancelMethod->getReturnType()->getName());
        
        // verifySubscriptionPayment should return array
        $verifyMethod = $reflection->getMethod('verifySubscriptionPayment');
        $this->assertEquals('array', $verifyMethod->getReturnType()->getName());
        
        // getAvailableGateways should return array
        $gatewaysMethod = $reflection->getMethod('getAvailableGateways');
        $this->assertEquals('array', $gatewaysMethod->getReturnType()->getName());
        
        // handlePaymentCallback should return array
        $callbackMethod = $reflection->getMethod('handlePaymentCallback');
        $this->assertEquals('array', $callbackMethod->getReturnType()->getName());
    }
}
