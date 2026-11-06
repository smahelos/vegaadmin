<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Queue;

use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * The database queue is the outbox: a job is stored in the transaction of the data that caused it.
 */
final class DatabaseQueueTransactionTest extends TestCase
{
    private const QUEUE = 'transaction-test';

    #[Test]
    public function a_job_is_stored_inside_the_transaction_that_dispatches_it(): void
    {
        $transactions = $this->transactions();
        $transactions->transaction(
            function (): void {
                $this->dispatchJob();

                $this->assertSame(1, $this->storedJobs());
            },
        );

        $this->assertSame(1, $this->storedJobs());
    }

    #[Test]
    public function a_rolled_back_transaction_leaves_no_job(): void
    {
        try {
            $transactions = $this->transactions();
            $transactions->transaction(
                function (): void {
                    $this->dispatchJob();

                    throw new RuntimeException('Use case failed');
                },
            );
        } catch (RuntimeException) {
        }

        $this->assertSame(0, $this->storedJobs());
    }

    private function dispatchJob(): void
    {
        $job = new QueuedCommand(['about']);
        $job->onConnection('database');
        $job->onQueue(self::QUEUE);
        dispatch($job);
    }

    private function storedJobs(): int
    {
        $jobsQuery = DB::table('jobs');
        $jobsQuery->where('queue', self::QUEUE);

        return $jobsQuery->count();
    }

    private function transactions(): TransactionBoundaryInterface
    {
        return $this->app->make(TransactionBoundaryInterface::class);
    }
}
