<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Analytics bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class AnalyticsServiceProvider extends ServiceProvider
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
            \App\Domain\Analytics\Contracts\DashboardServiceInterface::class,
            \App\Domain\Analytics\Services\DashboardService::class
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Analytics repositories
        $this->app->bind(
            \App\Application\Analytics\Contracts\AnalyticsReadRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentAnalyticsReadRepository::class
        );

        $this->app->bind(
            \App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentAnalyticsDtoReadRepository::class
        );

        $this->app->bind(
            \App\Application\Analytics\Contracts\ApiStatisticsRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentApiStatisticsRepository::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        // Analytics application services
        $this->app->bind(
            \App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface::class,
            \App\Application\Analytics\Services\ApiStatisticsApplicationService::class
        );

        $this->app->bind(
            \App\Application\Analytics\Contracts\DashboardApplicationServiceInterface::class,
            \App\Application\Analytics\Services\DashboardApplicationService::class
        );

        // Analytics presenters
        $this->app->bind(
            \App\Application\Analytics\Contracts\UserStatisticsPresenterInterface::class,
            \App\Application\Analytics\Presenters\UserStatisticsPresenter::class
        );

        $this->app->bind(
            \App\Application\Analytics\Contracts\MonthlyStatPresenterInterface::class,
            \App\Application\Analytics\Presenters\MonthlyStatPresenter::class
        );

        $this->app->bind(
            \App\Application\Analytics\Contracts\ClientTotalPresenterInterface::class,
            \App\Application\Analytics\Presenters\ClientTotalPresenter::class
        );
    }
}
