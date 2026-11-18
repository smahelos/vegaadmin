<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\System\Contracts\CacheClearerInterface;
use App\Domain\System\Contracts\CommandRunnerInterface;
use App\Domain\System\Contracts\CronTaskReadRepositoryInterface;
use App\Domain\System\Contracts\CronTaskWriteRepositoryInterface;
use App\Infrastructure\Console\System\ArtisanCacheClearer;
use App\Infrastructure\Console\System\ArtisanCommandRunner;
use App\Infrastructure\Persistence\Eloquent\System\Repositories\EloquentCronTaskRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the System bounded context: caches and cron tasks of the admin.
 */
final class SystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerInfrastructure();
    }

    private function registerInfrastructure(): void
    {
        $this->app->bind(
            CacheClearerInterface::class,
            ArtisanCacheClearer::class,
        );
        $this->app->bind(
            CommandRunnerInterface::class,
            ArtisanCommandRunner::class,
        );
        $this->app->bind(
            CronTaskReadRepositoryInterface::class,
            EloquentCronTaskRepository::class,
        );
        $this->app->bind(
            CronTaskWriteRepositoryInterface::class,
            EloquentCronTaskRepository::class,
        );
    }
}
