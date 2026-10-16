<?php

namespace App\Providers;

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
            \App\Domain\Content\Contracts\PageServiceInterface::class,
            \App\Domain\Content\Services\PageService::class
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Content repositories
        $this->app->bind(
            \App\Application\Content\Contracts\PageReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageRepository::class
        );

        $this->app->bind(
            \App\Application\Content\Contracts\PageWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageRepository::class
        );

        $this->app->bind(
            \App\Domain\Content\Contracts\PageDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Content\Contracts\PageDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageDtoRepository::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\Content\Contracts\PageApplicationServiceInterface::class,
            \App\Application\Content\Services\PageApplicationService::class
        );
    }
}
