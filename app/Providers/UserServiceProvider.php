<?php

namespace App\Providers;

use App\Domain\Shared\Hash\Contracts\HashInterface;
use App\Domain\User\Contracts\LimitedEntityCounterInterface;
use App\Domain\User\Contracts\Locale;
use App\Domain\User\Contracts\LocaleServiceInterface;
use App\Domain\User\Contracts\RoleLimitReadRepositoryInterface;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\Services\LocaleService;
use App\Domain\User\ValueObjects\UserPassword;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentLimitedEntityCounter;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentRoleLimitRepository;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserRepository;
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
            RoleLimitReadRepositoryInterface::class,
            EloquentRoleLimitRepository::class,
        );

        $this->app->bind(
            LimitedEntityCounterInterface::class,
            EloquentLimitedEntityCounter::class,
        );

        $this->app->bind(
            UserReadRepositoryInterface::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            UserWriteRepositoryInterface::class,
            EloquentUserRepository::class,
        );

        // User permissions service
        $this->app->bind(
            UserPermissionServiceInterface::class,
            LaravelUserPermissionService::class,
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
