<?php

namespace App\Providers;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\Contracts\PaymentReadRepositoryInterface;
use App\Application\Payment\Contracts\PaymentWriteRepositoryInterface;
use App\Application\Payment\Contracts\SubscriptionReadRepositoryInterface;
use App\Application\Payment\Contracts\SubscriptionWriteRepositoryInterface;
use App\Application\Payment\Services\PaymentApplicationService;
use App\Domain\Payment\Contracts\BankDtoRepository;
use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Contracts\PaymentDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\PaymentMethodDtoWriteRepositoryInterface;
use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Services\GatewayRegistry;
use App\Domain\Payment\Services\QrPaymentService;
use App\Domain\Payment\Services\SubscriptionPaymentService;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentBankDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoReadRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoWriteRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentReadRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentWriteRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoReadRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoWriteRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionReadRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionWriteRepository;
use App\Infrastructure\Providers\Payment\GoPayGateway;
use App\Infrastructure\Renderers\Qr\SimpleQrCodeRenderer;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Payment (subscriptions, gateways, QR payments, banks) bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerDomainServices();
        $this->registerInfrastructure();
        $this->registerApplicationServices();
    }

    /**
     * Domain services and domain-level factories.
     */
    private function registerDomainServices(): void
    {
        $this->app->bind(
            QrPaymentServiceInterface::class,
            QrPaymentService::class,
        );

        $this->app->bind(
            SubscriptionPaymentServiceInterface::class,
            SubscriptionPaymentService::class,
        );

        $this->app->singleton(
            GatewayRegistryInterface::class,
            GatewayRegistry::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Payment repositories
        $this->app->bind(
            PaymentWriteRepositoryInterface::class,
            EloquentPaymentWriteRepository::class,
        );

        $this->app->bind(
            PaymentReadRepositoryInterface::class,
            EloquentPaymentReadRepository::class,
        );

        $this->app->bind(
            PaymentDtoWriteRepositoryInterface::class,
            EloquentPaymentDtoWriteRepository::class,
        );

        $this->app->bind(
            PaymentDtoReadRepositoryInterface::class,
            EloquentPaymentDtoReadRepository::class,
        );

        // Subscription repositories
        $this->app->bind(
            SubscriptionWriteRepositoryInterface::class,
            EloquentSubscriptionWriteRepository::class,
        );

        $this->app->bind(
            SubscriptionReadRepositoryInterface::class,
            EloquentSubscriptionReadRepository::class,
        );

        $this->app->bind(
            SubscriptionDtoWriteRepositoryInterface::class,
            EloquentSubscriptionDtoWriteRepository::class,
        );

        $this->app->bind(
            SubscriptionDtoReadRepositoryInterface::class,
            EloquentSubscriptionDtoReadRepository::class,
        );

        // Payment Method repositories
        $this->app->bind(
            PaymentMethodDtoReadRepositoryInterface::class,
            EloquentPaymentMethodDtoRepository::class,
        );

        $this->app->bind(
            PaymentMethodDtoWriteRepositoryInterface::class,
            EloquentPaymentMethodDtoRepository::class,
        );

        $this->app->bind(
            BankDtoRepository::class,
            EloquentBankDtoRepository::class,
        );

        // Payment gateways
        $this->app->singleton(
            GoPayGateway::class,
        );

        // Named binding for GoPay gateway as expected by tests
        $this->app->singleton('payment.gateway.gopay', function ($app) {
            return $app->make(GoPayGateway::class);
        });

        // QR code rendering
        $this->app->bind(
            QrCodeRendererInterface::class,
            SimpleQrCodeRenderer::class,
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            PaymentApplicationServiceInterface::class,
            PaymentApplicationService::class,
        );
    }
}
