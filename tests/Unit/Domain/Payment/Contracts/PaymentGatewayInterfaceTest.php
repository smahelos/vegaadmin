<?php

namespace Tests\Unit\Domain\Payment\Contracts;

use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentGatewayInterfaceTest extends TestCase
{
    #[Test]
    public function interface_exists(): void
    {
        $this->assertTrue(interface_exists(PaymentGatewayInterface::class));
    }

    #[Test]
    public function interface_defines_core_methods(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        foreach ([
            'createPayment','processPaymentReturn','verifyPayment','cancelPayment','refundPayment',
            'getPaymentStatus','getSupportedCurrencies','isAvailable','getName','getDisplayName'
        ] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing method {$method}");
        }
    }

    #[Test]
    public function method_signatures_and_return_types(): void
    {
        $r = new \ReflectionClass(PaymentGatewayInterface::class);
        $this->assertEquals('array', (string)$r->getMethod('createPayment')->getReturnType());
        $this->assertEquals('array', (string)$r->getMethod('processPaymentReturn')->getReturnType());
        $this->assertEquals('array', (string)$r->getMethod('verifyPayment')->getReturnType());
        $this->assertEquals('bool', (string)$r->getMethod('cancelPayment')->getReturnType());
        $this->assertEquals('array', (string)$r->getMethod('refundPayment')->getReturnType());
        $this->assertEquals('string', (string)$r->getMethod('getPaymentStatus')->getReturnType());
        $this->assertEquals('array', (string)$r->getMethod('getSupportedCurrencies')->getReturnType());
        $this->assertEquals('bool', (string)$r->getMethod('isAvailable')->getReturnType());
        $this->assertEquals('string', (string)$r->getMethod('getName')->getReturnType());
        $this->assertEquals('string', (string)$r->getMethod('getDisplayName')->getReturnType());
    }

    #[Test]
    public function interface_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $this->assertEquals('App\Domain\Payment\Contracts\PaymentGatewayInterface', $reflection->getName());
    }

    #[Test]
    public function interface_defines_create_payment_method(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $this->assertTrue($reflection->hasMethod('createPayment'));
        
        $method = $reflection->getMethod('createPayment');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function interface_defines_verify_payment_method(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $this->assertTrue($reflection->hasMethod('verifyPayment'));
        
        $method = $reflection->getMethod('verifyPayment');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function interface_defines_cancel_payment_method(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $this->assertTrue($reflection->hasMethod('cancelPayment'));
        
        $method = $reflection->getMethod('cancelPayment');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function interface_defines_is_available_method(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $this->assertTrue($reflection->hasMethod('isAvailable'));
        
        $method = $reflection->getMethod('isAvailable');
        $this->assertTrue($method->isAbstract());
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function create_payment_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $method = $reflection->getMethod('createPayment');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('paymentData', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function verify_payment_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $method = $reflection->getMethod('verifyPayment');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('paymentId', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function cancel_payment_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $method = $reflection->getMethod('cancelPayment');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('paymentId', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function methods_have_correct_return_types(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        
        // createPayment should return array
        $createMethod = $reflection->getMethod('createPayment');
        $this->assertEquals('array', $createMethod->getReturnType()->getName());
        
        // verifyPayment should return array
        $verifyMethod = $reflection->getMethod('verifyPayment');
        $this->assertEquals('array', $verifyMethod->getReturnType()->getName());
        
        // cancelPayment should return bool (not array!)
        $cancelMethod = $reflection->getMethod('cancelPayment');
        $this->assertEquals('bool', $cancelMethod->getReturnType()->getName());
    }

    #[Test]
    public function is_available_method_returns_bool(): void
    {
        $reflection = new \ReflectionClass(PaymentGatewayInterface::class);
        $method = $reflection->getMethod('isAvailable');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }
}
