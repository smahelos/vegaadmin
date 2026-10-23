<?php

namespace App\Providers;

use App\Application\Content\Contracts\PageApplicationServiceInterface;
use App\Application\Content\Contracts\PageReadRepositoryInterface;
use App\Application\Content\Contracts\PageWriteRepositoryInterface;
use App\Application\Content\Services\PageApplicationService;
use App\Domain\Content\Contracts\PageDtoReadRepositoryInterface;
use App\Domain\Content\Contracts\PageDtoWriteRepositoryInterface;
use App\Domain\Content\Contracts\PageServiceInterface;
use App\Domain\Content\Services\PageService;
use App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Content (pages) bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class ContentServiceProvider extends ServiceProvider
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
            PageServiceInterface::class,
            PageService::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Content repositories
        $this->app->bind(
            PageReadRepositoryInterface::class,
            EloquentPageRepository::class,
        );

        $this->app->bind(
            PageWriteRepositoryInterface::class,
            EloquentPageRepository::class,
        );

        $this->app->bind(
            PageDtoReadRepositoryInterface::class,
            EloquentPageDtoRepository::class,
        );

        $this->app->bind(
            PageDtoWriteRepositoryInterface::class,
            EloquentPageDtoRepository::class,
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            PageApplicationServiceInterface::class,
            PageApplicationService::class,
        );
    }
}
