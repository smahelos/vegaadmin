<?php

namespace App\Providers;

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
            \App\Domain\Payment\Contracts\QrPaymentServiceInterface::class,
            \App\Domain\Payment\Services\QrPaymentService::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface::class,
            \App\Domain\Payment\Services\SubscriptionPaymentService::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\BankServiceInterface::class,
            \App\Domain\Payment\Services\BankService::class
        );

        $this->app->singleton(
            \App\Domain\Payment\Contracts\GatewayRegistryInterface::class,
            \App\Domain\Payment\Services\GatewayRegistry::class
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Payment repositories
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentWriteRepository::class
        );

        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentReadRepository::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoWriteRepository::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoReadRepository::class
        );

        // Subscription repositories
        $this->app->bind(
            \App\Application\Payment\Contracts\SubscriptionWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionWriteRepository::class
        );

        $this->app->bind(
            \App\Application\Payment\Contracts\SubscriptionReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionReadRepository::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoWriteRepository::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoReadRepository::class
        );

        // Payment Method repositories
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentMethodDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodDtoRepository::class
        );

        $this->app->bind(
            \App\Application\Payment\Contracts\BankRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentBankRepository::class
        );

        $this->app->bind(
            \App\Domain\Payment\Contracts\BankDtoRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentBankDtoRepository::class
        );

        // Payment gateways
        $this->app->singleton(
            \App\Infrastructure\Providers\Payment\GoPayGateway::class
        );

        // Named binding for GoPay gateway as expected by tests
        $this->app->singleton('payment.gateway.gopay', function ($app) {
            return $app->make(\App\Infrastructure\Providers\Payment\GoPayGateway::class);
        });

        // QR code rendering
        $this->app->bind(
            \App\Domain\Payment\Contracts\QrCodeRendererInterface::class,
            \App\Infrastructure\Renderers\Qr\SimpleQrCodeRenderer::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentApplicationServiceInterface::class,
            \App\Application\Payment\Services\PaymentApplicationService::class
        );

        $this->app->bind(
            \App\Application\Payment\Contracts\BankApplicationServiceInterface::class,
            \App\Application\Payment\Services\BankApplicationService::class
        );

        // Application Payment mappers
        $this->app->singleton(\App\Application\Payment\Mappers\QrPaymentPayloadMapper::class);
    }
}
