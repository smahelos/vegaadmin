<?php

namespace Tests\Unit\Domain\Payment\Providers\PaymentGateways;

use App\Infrastructure\Providers\Payment\GoPayGateway;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GoPayGatewayTest extends TestCase
{
    #[Test]
    public function implements_interface_and_namespace(): void
    {
        $r = new ReflectionClass(\App\Infrastructure\Providers\Payment\GoPayGateway::class);
        $this->assertEquals('App\\Infrastructure\\Providers\\Payment', $r->getNamespaceName());
        $this->assertContains(PaymentGatewayInterface::class, $r->getInterfaceNames());
    }

    #[Test]
    public function public_methods_and_signatures(): void
    {
    $r = new ReflectionClass(\App\Infrastructure\Providers\Payment\GoPayGateway::class);
        $expected = [
            'createPayment' => 1,
            'processPaymentReturn' => 1,
            'verifyPayment' => 1,
            'cancelPayment' => 1,
            'refundPayment' => 2,
            'getPaymentStatus' => 1,
            'getSupportedCurrencies' => 0,
            'isAvailable' => 0,
            'getName' => 0,
            'getDisplayName' => 0,
        ];

        foreach ($expected as $method => $paramCount) {
            $this->assertTrue($r->hasMethod($method), "Missing method $method");
            $m = $r->getMethod($method);
            $this->assertTrue($m->isPublic(), "$method should be public");
            $this->assertCount($paramCount, $m->getParameters(), "$method parameter count mismatch");
            $this->assertNotNull($m->getReturnType(), "$method missing return type");
        }
    }

    #[Test]
    public function private_helpers_exist(): void
    {
    $r = new ReflectionClass(\App\Infrastructure\Providers\Payment\GoPayGateway::class);
        foreach (['generateOrderNumberFromPayload', 'mapGoPayStatus'] as $method) {
            $this->assertTrue($r->hasMethod($method), "Missing private helper $method");
            $this->assertTrue($r->getMethod($method)->isPrivate(), "$method should be private");
        }
    }

    #[Test]
    public function declared_public_methods_count(): void
    {
        $r = new ReflectionClass(\App\Infrastructure\Providers\Payment\GoPayGateway::class);
        $public = array_filter(
            $r->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($m) => $m->getDeclaringClass()->getName() === \App\Infrastructure\Providers\Payment\GoPayGateway::class && $m->getName() !== '__construct'
        );
        $this->assertCount(10, $public, 'Unexpected number of public methods');
    }
}
