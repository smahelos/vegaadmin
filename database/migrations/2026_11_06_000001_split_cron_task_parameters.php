<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The commands of App\Domain\Shared\Console\Enums\ScheduledCommand when this migration was written.
     *
     * @var list<string>
     */
    private const ALLOWED_COMMANDS = [
        'invoices:check-payment-status',
        'db:optimize',
        'db:health-check',
        'db:monitor',
        'db:clean-metrics',
    ];

    /**
     * Keep the command name and its options apart, so the scheduler gets the options as separate arguments. A
     * task whose command a cron task may no longer run is deleted.
     */
    public function up(): void
    {
        Schema::table(
            'cron_tasks',
            function (Blueprint $table): void {
                $table->string('parameters')->nullable()->after('command');
            },
        );

        DB::transaction(
            function (): void {
                $taskQuery = DB::table('cron_tasks');
                $tasks = $taskQuery->get(['id', 'command']);
                foreach ($tasks as $task) {
                    $parts = preg_split(
                        '/\s+/',
                        trim($task->command),
                        2,
                    );
                    $taskQuery = DB::table('cron_tasks');
                    $taskQuery->where('id', $task->id);
                    if (
                        !in_array(
                            $parts[0],
                            self::ALLOWED_COMMANDS,
                            true,
                        )
                    ) {
                        $taskQuery->delete();

                        continue;
                    }

                    $taskQuery->update(
                        [
                            'command' => $parts[0],
                            'parameters' => $parts[1] ?? null,
                        ],
                    );
                }
            },
        );
    }

    public function down(): void
    {
        $taskQuery = DB::table('cron_tasks');
        $taskQuery->whereNotNull('parameters');
        $taskQuery->update(
            [
                'command' => DB::raw("CONCAT(command, ' ', parameters)"),
            ],
        );

        Schema::table(
            'cron_tasks',
            function (Blueprint $table): void {
                $table->dropColumn('parameters');
            },
        );
    }
};
