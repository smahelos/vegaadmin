<?php

namespace App\Models;

use App\Domain\System\Enums\ScheduledCommand;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
