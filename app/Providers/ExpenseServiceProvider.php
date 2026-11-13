<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Expense\Contracts\ExpenseReadRepositoryInterface;
use App\Domain\Expense\Contracts\ExpenseWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Expense\Repositories\EloquentExpenseRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Expense bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
final class ExpenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerInfrastructure();
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        $this->app->bind(
            ExpenseReadRepositoryInterface::class,
            EloquentExpenseRepository::class,
        );

        $this->app->bind(
            ExpenseWriteRepositoryInterface::class,
            EloquentExpenseRepository::class,
        );
    }
}
