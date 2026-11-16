<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Analytics\Contracts\AnalyticsReadRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentAnalyticsRepository;
use App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentApiStatisticsRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Analytics bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
final class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerInfrastructure();
    }

    private function registerInfrastructure(): void
    {
        // Analytics repositories
        $this->app->bind(
            AnalyticsReadRepositoryInterface::class,
            EloquentAnalyticsRepository::class,
        );

        $this->app->bind(
            ApiStatisticsRepository::class,
            EloquentApiStatisticsRepository::class,
        );
    }
}
