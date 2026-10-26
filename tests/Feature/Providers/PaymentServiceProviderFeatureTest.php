<?php

namespace Tests\Feature\Providers;

use App\Domain\Payment\Contracts\BankServiceInterface;
use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Services\BankService;
use App\Domain\Payment\Services\GatewayRegistry;
use App\Domain\Payment\Services\QrPaymentService;
use App\Domain\Payment\Services\SubscriptionPaymentService;
use App\Infrastructure\Providers\Payment\GoPayGateway;
use App\Providers\PaymentServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentServiceProviderFeatureTest extends TestCase
{
    #[Test]
    public function provider_registers_subscription_payment_service_interface(): void
    {
        $service = $this->app->make(SubscriptionPaymentServiceInterface::class);
        $this->assertInstanceOf(SubscriptionPaymentService::class, $service);
        $this->assertInstanceOf(SubscriptionPaymentServiceInterface::class, $service);
    }

    #[Test]
    public function provider_registers_gopay_gateway(): void
    {
        $gateway = $this->app->make('payment.gateway.gopay');
        $this->assertInstanceOf(GoPayGateway::class, $gateway);
    }

    #[Test]
    public function service_provider_is_registered_in_application(): void
    {
        $providers = $this->app->getLoadedProviders();
        $this->assertArrayHasKey(PaymentServiceProvider::class, $providers);
    }

    #[Test]
    public function service_bindings_are_singletons(): void
    {
        $service1 = $this->app->make(SubscriptionPaymentServiceInterface::class);
        $service2 = $this->app->make(SubscriptionPaymentServiceInterface::class);
        $this->assertInstanceOf(SubscriptionPaymentService::class, $service1);
        $this->assertInstanceOf(SubscriptionPaymentService::class, $service2);
    }

    #[Test]
    public function provider_boot_method_executes_without_errors(): void
    {
        $this->assertInstanceOf(QrPaymentService::class, $this->app->make(QrPaymentServiceInterface::class));
        $this->assertInstanceOf(BankService::class, $this->app->make(BankServiceInterface::class));
        $this->assertInstanceOf(GatewayRegistry::class, $this->app->make(GatewayRegistryInterface::class));
    }

    #[Test]
    public function all_bound_services_implement_correct_interfaces(): void
    {
        $service = $this->app->make(SubscriptionPaymentServiceInterface::class);
        $gateway = $this->app->make('payment.gateway.gopay');
        $this->assertInstanceOf(SubscriptionPaymentServiceInterface::class, $service);
        $interfaces = class_implements($gateway);
        $this->assertContains(PaymentGatewayInterface::class, $interfaces);
        $this->assertInstanceOf(QrPaymentService::class, $this->app->make(QrPaymentServiceInterface::class));
        $this->assertInstanceOf(BankService::class, $this->app->make(BankServiceInterface::class));
        $this->assertInstanceOf(GatewayRegistry::class, $this->app->make(GatewayRegistryInterface::class));
    }
}
