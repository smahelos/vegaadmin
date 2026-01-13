<?php

namespace Tests\Unit\Domain\Payment\Services;

use App\Domain\Payment\Services\SubscriptionPaymentService;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SubscriptionPaymentServiceTest extends TestCase
{

    #[Test]
    public function class_implements_interface(): void
    {
        $r = new ReflectionClass(SubscriptionPaymentService::class);
        $interfaces = $r->getInterfaceNames();
        $this->assertContains(SubscriptionPaymentServiceInterface::class, $interfaces);
    }

    #[Test]
    public function class_namespace_and_structure(): void
    {
    $r = new ReflectionClass(SubscriptionPaymentService::class);
        $this->assertEquals('App\\Domain\\Payment\\Services', $r->getNamespaceName());
        $this->assertTrue($r->isInstantiable());
        $this->assertFalse($r->isAbstract());
    }

    #[Test]
    public function public_method_signatures(): void
    {
    $r = new ReflectionClass(SubscriptionPaymentService::class);
        $expected = [
            'processSubscriptionPayment' => 3,
            'handlePaymentCallback' => 2,
            'cancelSubscriptionPayment' => 1,
            'verifySubscriptionPayment' => 1,
            'getAvailableGateways' => 0,
            'supportsRecurringPayments' => 1,
            'getPaymentStatus' => 1,
            'getSupportedGateways' => 0,
            'getGateway' => 1,
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
    public function private_helper_methods_exist(): void
    {
        $r = new ReflectionClass(SubscriptionPaymentService::class);
        // Only current private helpers should be asserted
        foreach (['activateSubscriptionById', 'handleFailedPaymentById'] as $method) {
            $this->assertTrue($r->hasMethod($method), "Missing helper $method");
            $this->assertTrue($r->getMethod($method)->isPrivate(), "$method should be private");
        }
        // Removed legacy registerGateways method should not exist anymore
        $this->assertFalse($r->hasMethod('registerGateways'));
    }

    #[Test]
    public function public_methods_count(): void
    {
        $r = new ReflectionClass(SubscriptionPaymentService::class);
        $public = array_filter(
            $r->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($m) => $m->getDeclaringClass()->getName() === SubscriptionPaymentService::class && $m->getName() !== '__construct'
        );
        // Current explicitly defined public methods (excluding constructor)
        $expected = [
            'processSubscriptionPayment',
            'handlePaymentCallback',
            'getSupportedGateways',
            'getGateway',
            'cancelSubscriptionPayment',
            'verifySubscriptionPayment',
            'getAvailableGateways',
            'supportsRecurringPayments',
            'getPaymentStatus',
            'refundSubscriptionPayment',
            'inspectPayment',
        ];
        $this->assertCount(count($expected), $public, 'Unexpected number of declared public methods');
        $this->assertEqualsCanonicalizing($expected, array_map(fn($m) => $m->getName(), $public));
    }
}
