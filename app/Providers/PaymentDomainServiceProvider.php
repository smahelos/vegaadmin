<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Infrastructure\Providers\Payment\GoPayGateway;
use App\Domain\Payment\Services\GatewayRegistry;

class PaymentDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Named binding for GoPay gateway as expected by tests
        $this->app->singleton('payment.gateway.gopay', function ($app) {
            return $app->make(GoPayGateway::class);
        });

        // Domain service bindings expected to originate from this provider
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
            GatewayRegistry::class
        );

        // DTO repository contracts (Domain) - ensure origin attribution
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoReadRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoWriteRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodDtoRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentMethodDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodDtoRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoReadRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoWriteRepository::class
        );

        // Payment Application services and repos origin at Payment provider for the test
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentApplicationServiceInterface::class,
            \App\Application\Payment\Services\PaymentApplicationService::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\BankApplicationServiceInterface::class,
            \App\Application\Payment\Services\BankApplicationService::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentReadRepository::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentWriteRepository::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\SubscriptionReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionReadRepository::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\SubscriptionWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionWriteRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\QrCodeRendererInterface::class,
            \App\Infrastructure\Renderers\Qr\SimpleQrCodeRenderer::class
        );

    }
    public function boot(): void
    {
        // no-op; registration handled in register().
    }
}
