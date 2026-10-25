<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseOptimizeCommandFeatureTest extends TestCase
{
    #[Test]
    public function optimizes_the_table_and_logs_the_completed_maintenance(): void
    {
        // OPTIMIZE TABLE commits the test transaction implicitly
        $this->allowImplicitCommit();

        $exitCode = Artisan::call('db:optimize', [
            '--table' => 'taxes',
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('database_maintenance_logs', [
            'task_type' => 'optimize',
            'table_name' => 'taxes',
            'status' => 'completed',
        ]);
        $this->assertSame(1, DB::table('performance_metrics')->where('table_name', 'taxes')->count());
    }
}
