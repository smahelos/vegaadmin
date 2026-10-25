<?php

namespace App\Providers;

use App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface;
use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Application\Analytics\Contracts\ClientTotalPresenterInterface;
use App\Application\Analytics\Contracts\DashboardApplicationServiceInterface;
use App\Application\Analytics\Contracts\MonthlyStatPresenterInterface;
use App\Application\Analytics\Contracts\UserStatisticsPresenterInterface;
use App\Application\Analytics\Presenters\ClientTotalPresenter;
use App\Application\Analytics\Presenters\MonthlyStatPresenter;
use App\Application\Analytics\Presenters\UserStatisticsPresenter;
use App\Application\Analytics\Services\ApiStatisticsApplicationService;
use App\Application\Analytics\Services\DashboardApplicationService;
use App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository;
use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\Analytics\Services\DashboardService;
use App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentAnalyticsDtoReadRepository;
use App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentApiStatisticsRepository;
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
            DashboardServiceInterface::class,
            DashboardService::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Analytics repositories
        $this->app->bind(
            AnalyticsDtoReadRepository::class,
            EloquentAnalyticsDtoReadRepository::class,
        );

        $this->app->bind(
            ApiStatisticsRepository::class,
            EloquentApiStatisticsRepository::class,
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        // Analytics application services
        $this->app->bind(
            ApiStatisticsApplicationServiceInterface::class,
            ApiStatisticsApplicationService::class,
        );

        $this->app->bind(
            DashboardApplicationServiceInterface::class,
            DashboardApplicationService::class,
        );

        // Analytics presenters
        $this->app->bind(
            UserStatisticsPresenterInterface::class,
            UserStatisticsPresenter::class,
        );

        $this->app->bind(
            MonthlyStatPresenterInterface::class,
            MonthlyStatPresenter::class,
        );

        $this->app->bind(
            ClientTotalPresenterInterface::class,
            ClientTotalPresenter::class,
        );
    }
}
