<?php

namespace App\Providers;

use App\Application\User\Contracts\CrudAccessServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface;
use App\Application\User\Contracts\UserApplicationServiceInterface;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Application\User\Services\UELSApplicationService;
use App\Application\User\Services\UserApplicationService;
use App\Domain\Shared\Hash\Contracts\HashInterface;
use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface;
use App\Domain\User\Contracts\Locale;
use App\Domain\User\Contracts\LocaleServiceInterface;
use App\Domain\User\Contracts\PermissionLimitResolverInterface;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserServiceInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\Services\LocaleService;
use App\Domain\User\Services\PermissionLimitResolver;
use App\Domain\User\Services\UniversalLimitService;
use App\Domain\User\Services\UserService;
use App\Domain\User\ValueObjects\UserPassword;
use App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter;
use App\Infrastructure\Authorization\Services\CrudAccessService;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserDtoRepository;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EntityLimitRepository;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EntityLimitUsageRepository;
use App\Infrastructure\Services\User\LaravelUserPermissionService;
use App\Infrastructure\Shared\Locale\Services\LaravelLocaleAdapter;
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
            LocaleServiceInterface::class,
            LocaleService::class,
        );

        $this->app->bind(
            UserServiceInterface::class,
            UserService::class,
        );

        $this->app->bind(
            PermissionLimitResolverInterface::class,
            PermissionLimitResolver::class,
        );

        $this->app->bind(
            UniversalLimitServiceInterface::class,
            UniversalLimitService::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Locale adapter for Domain
        $this->app->bind(
            Locale::class,
            LaravelLocaleAdapter::class,
        );

        // User repositories
        $this->app->bind(
            EntityLimitRepositoryInterface::class,
            EntityLimitRepository::class,
        );

        $this->app->bind(
            EntityLimitUsageRepositoryInterface::class,
            EntityLimitUsageRepository::class,
        );

        $this->app->bind(
            UserReadRepositoryInterface::class,
            EloquentUserDtoRepository::class,
        );

        $this->app->bind(
            UserWriteRepositoryInterface::class,
            EloquentUserDtoRepository::class,
        );

        // Authorization adapter (Backpack + Spatie) for Application layer
        $this->app->bind(
            UserAuthorizationAdapterInterface::class,
            SpatieAuthorizationAdapter::class,
        );

        // CRUD access service for Backpack
        $this->app->bind(
            CrudAccessServiceInterface::class,
            CrudAccessService::class,
        );

        // User permissions service
        $this->app->bind(
            UserPermissionServiceInterface::class,
            LaravelUserPermissionService::class,
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            UserApplicationServiceInterface::class,
            UserApplicationService::class,
        );

        $this->app->bind(
            UELSApplicationServiceInterface::class,
            UELSApplicationService::class,
        );
    }

    public function boot(): void
    {
        // Configure default hasher for UserPassword VO
        try {
            /** @var HashInterface $hasher */
            $hasher = $this->app->make(HashInterface::class);
            UserPassword::setHasher($hasher);
        } catch (\Throwable $e) {
            // During some console contexts this may fail; VO will throw if used without hasher.
        }
    }
}
