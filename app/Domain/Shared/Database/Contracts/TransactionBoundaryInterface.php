<?php

namespace App\Domain\Shared\Database\Contracts;

/**
 * Simple transaction boundary abstraction for domain services.
 */
interface TransactionBoundaryInterface
{
    /**
     * Execute the given callback within a transaction.
     * Implementations must commit on success and rollback on exception, rethrowing the exception.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function transaction(callable $callback);
}
