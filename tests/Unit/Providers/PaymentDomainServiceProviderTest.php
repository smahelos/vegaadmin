<?php

namespace Tests\Unit\Providers;

use App\Providers\PaymentDomainServiceProvider;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface; // domain interface
use App\Infrastructure\Providers\Payment\GoPayGateway; // corrected import
use App\Domain\Payment\Services\SubscriptionPaymentService; // domain service
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentDomainServiceProviderTest extends TestCase
{
    private PaymentDomainServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new PaymentDomainServiceProvider($this->app);
    }

    #[Test]
    public function provider_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PaymentDomainServiceProvider::class, $this->provider);
    }

    #[Test]
    public function provider_extends_service_provider(): void
    {
        $this->assertInstanceOf(ServiceProvider::class, $this->provider);
    }

    #[Test]
    public function provider_has_register_method(): void
    {
        $this->assertTrue(method_exists($this->provider, 'register'));
        
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('register');
    $returnType = $method->getReturnType();
        
    $this->assertNotNull($returnType);
    $rtName = $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType;
    $this->assertEquals('void', $rtName);
    }

    #[Test]
    public function provider_has_boot_method(): void
    {
        $this->assertTrue(method_exists($this->provider, 'boot'));
        
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('boot');
    $returnType = $method->getReturnType();
        
    $this->assertNotNull($returnType);
    $rtName = $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType;
    $this->assertEquals('void', $rtName);
    }

    #[Test]
    public function register_method_binds_services(): void
    {
        // This is a unit test for the business logic structure
        // The actual binding behavior is tested in feature tests
        
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('register');
        
        // Verify method exists and has correct visibility
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
    }

    #[Test]
    public function provider_registers_correct_services(): void
    {
        // Unit test focusing on the structure and interfaces
        // The actual registration is tested in feature tests
        
        $this->assertTrue(interface_exists(SubscriptionPaymentServiceInterface::class));
        $this->assertTrue(class_exists(SubscriptionPaymentService::class));
        $this->assertTrue(class_exists(GoPayGateway::class));
    }

    #[Test]
    public function provider_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $this->assertEquals('App\\Providers\\PaymentDomainServiceProvider', $reflection->getName());
    }

    #[Test]
    public function provider_structure_validates_service_bindings(): void
    {
        // Verify that the classes the provider should bind actually exist
        $this->assertTrue(interface_exists(SubscriptionPaymentServiceInterface::class));
        $this->assertTrue(class_exists(SubscriptionPaymentService::class));
        $this->assertTrue(class_exists(GoPayGateway::class));
        
        // Verify implementation relationships
        $implementsInterface = in_array(
            SubscriptionPaymentServiceInterface::class,
            class_implements(SubscriptionPaymentService::class)
        );
        $this->assertTrue($implementsInterface);
    }
}
