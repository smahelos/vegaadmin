<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

trait AssertsDatabaseRefusal
{
    /**
     * Runs the statement in a savepoint, so the refused statement leaves the test transaction usable.
     */
    private function assertRefused(callable $statement, int $mysqlErrorCode): void
    {
        try {
            DB::transaction($statement);
        } catch (QueryException $e) {
            $this->assertSame(
                $mysqlErrorCode,
                $e->errorInfo[1],
                $e->getMessage(),
            );

            return;
        }

        $this->fail('The database accepted a statement it must refuse');
    }
}
