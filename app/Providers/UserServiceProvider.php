<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the User (accounts, permissions, entity limits) bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class UserServiceProvider extends ServiceProvider
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
            \App\Domain\User\Contracts\LocaleServiceInterface::class,
            \App\Domain\User\Services\LocaleService::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\UserServiceInterface::class,
            \App\Domain\User\Services\UserService::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\PermissionLimitResolverInterface::class,
            \App\Domain\User\Services\PermissionLimitResolver::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\UniversalLimitServiceInterface::class,
            \App\Domain\User\Services\UniversalLimitService::class
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Locale adapter for Domain
        $this->app->bind(
            \App\Domain\User\Contracts\Locale::class,
            \App\Infrastructure\Shared\Locale\Services\LaravelLocaleAdapter::class
        );

        // User repositories
        $this->app->bind(
            \App\Domain\User\Contracts\EntityLimitRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EntityLimitRepository::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EntityLimitUsageRepository::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\UserReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\UserWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserDtoRepository::class
        );

        // Authorization adapter (Backpack + Spatie) for Application layer
        $this->app->bind(
            \App\Application\User\Contracts\UserAuthorizationAdapterInterface::class,
            \App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter::class
        );

        // CRUD access service for Backpack
        $this->app->bind(
            \App\Application\User\Contracts\CrudAccessServiceInterface::class,
            \App\Infrastructure\Authorization\Services\CrudAccessService::class
        );

        // User permissions service
        $this->app->bind(
            \App\Domain\User\Contracts\UserPermissionServiceInterface::class,
            \App\Infrastructure\Services\User\LaravelUserPermissionService::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\User\Contracts\UserApplicationServiceInterface::class,
            \App\Application\User\Services\UserApplicationService::class
        );

        $this->app->bind(
            \App\Application\User\Contracts\UELSApplicationServiceInterface::class,
            \App\Application\User\Services\UELSApplicationService::class
        );
    }

    public function boot(): void
    {
        // Configure default hasher for UserPassword VO
        try {
            /** @var \App\Domain\Shared\Hash\Contracts\HashInterface $hasher */
            $hasher = $this->app->make(\App\Domain\Shared\Hash\Contracts\HashInterface::class);
            \App\Domain\User\ValueObjects\UserPassword::setHasher($hasher);
        } catch (\Throwable $e) {
            // During some console contexts this may fail; VO will throw if used without hasher.
        }
    }
}
