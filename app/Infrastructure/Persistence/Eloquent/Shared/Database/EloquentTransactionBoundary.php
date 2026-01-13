<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Database;

use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use Illuminate\Support\Facades\DB;

class EloquentTransactionBoundary implements TransactionBoundaryInterface
{
    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }
}
