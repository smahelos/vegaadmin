<?php

namespace App\Models;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Domain\Shared\Console\ValueObjects\CommandOptions;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class CronTask extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'name',
        'command',
        'parameters',
        'frequency',
        'custom_expression',
        'run_at',
        'day_of_week',
        'day_of_month',
        'is_active',
        'description',
        'last_run',
        'last_output',
    ];

    protected $casts = [
        'command' => ScheduledCommand::class,
        'run_at' => 'datetime',
        'last_run' => 'datetime',
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
    ];

    /**
     * Získá CRON výraz podle nastavené frekvence.
     */
    public function getCronExpression(): string
    {
        if ($this->frequency === 'custom') {
            return $this->custom_expression ?: '* * * * *'; // Default fallback if custom_expression is null
        }

        $runAt = $this->run_at ? Carbon::parse($this->run_at) : Carbon::parse('00:00');
        $minute = $runAt->format('i');
        $hour = $runAt->format('H');

        return match ($this->frequency) {
            'daily' => "{$minute} {$hour} * * *",
            'weekly' => "{$minute} {$hour} * * {$this->day_of_week}",
            'monthly' => "{$minute} {$hour} {$this->day_of_month} * *",
            default => '* * * * *', // Nemělo by nastat
        };
    }

    /**
     * Získá čas příštího spuštění.
     */
    public function getNextRunDate(): ?Carbon
    {
        try {
            $expression = $this->getCronExpression();
            if (!$expression) {
                return null; // Pokud není platný CRON výraz
            }
            $cron = new CronExpression($expression);

            $nextRun = $cron->getNextRunDate();

            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $nextRun->format('Y-m-d H:i:s'),
            );
        } catch (\Exception $e) {
            \Log::error('Neplatný CRON výraz: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Runs the command now and stores the time and output of the run. A failed or throwing command returns false
     * with the error in last_output.
     */
    public function simulateRun(): bool
    {
        try {
            $startTime = microtime(true);

            $options = CommandOptions::fromString($this->parameters ?? '');
            $exitCode = Artisan::call(
                $this->command->value,
                $options->input(),
            );

            $executionTime = round(
                microtime(true) - $startTime,
                2,
            );

            $this->last_run = now();
            $this->last_output = __(
                'cron_tasks.messages.run_output',
                [
                    'output' => Artisan::output(),
                    'seconds' => $executionTime,
                ],
            );
            $this->save();

            return $exitCode === Command::SUCCESS;
        } catch (\Exception $e) {
            $this->last_run = now();
            $this->last_output = __(
                'cron_tasks.messages.run_error',
                ['error' => $e->getMessage()],
            );
            $this->save();

            return false;
        }
    }
}
