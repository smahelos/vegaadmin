<?php

namespace App\Models;

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
        'run_at' => 'datetime',
        'last_run' => 'datetime',
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
    ];

    /**
     * Získá základní příkaz bez parametrů.
     */
    public function getBaseCommandAttribute(): ?string
    {
        if (empty($this->command)) {
            return null;
        }

        $parts = explode(
            ' ',
            trim($this->command),
            2,
        );

        return $parts[0] ?? null;
    }

    /**
     * Získá parametry příkazu.
     */
    public function getCommandParamsAttribute(): ?string
    {
        if (empty($this->command)) {
            return null;
        }

        $parts = explode(
            ' ',
            trim($this->command),
            2,
        );

        return $parts[1] ?? '';
    }

    /**
     * Nastaví kompletní příkaz na základě základního příkazu a parametrů.
     */
    public function setCommandAttribute(string $value): void
    {
        // Pokud hodnota už obsahuje mezery (kompletní příkaz), uložíme ji přímo
        if (strpos($value, ' ') !== false) {
            $this->attributes['command'] = trim($value);

            return;
        }

        // Jinak jde o základní příkaz bez parametrů
        $baseCommand = trim($value);
        $params = $this->command_params ?? '';

        // Sestavíme kompletní příkaz
        $fullCommand = $baseCommand;
        if (! empty($params)) {
            $fullCommand .= ' ' . trim($params);
        }

        $this->attributes['command'] = trim($fullCommand);
    }

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
            if (! $expression) {
                return null; // Pokud není platný CRON výraz
            }
            $cron = new CronExpression($expression);

            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $cron->getNextRunDate()->format('Y-m-d H:i:s'),
            );
        } catch (\Exception $e) {
            \Log::error('Neplatný CRON výraz: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Simuluje spuštění úlohy.
     */
    public function simulateRun(): mixed
    {
        try {
            $startTime = microtime(true);

            // Rozlišení mezi Artisan příkazem a PHP funkcí
            if (strpos($this->command, '::') !== false) {
                // PHP funkce
                [$class, $method] = explode('::', $this->command);
                $result = app($class)->$method();
            } else {
                // Artisan příkaz
                $result = \Artisan::call($this->command);
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            // Aktualizace informací o posledním spuštění
            $this->last_run = now();
            $this->last_output = 'Výsledek: ' . (is_string($result) ? $result : \Artisan::output()) .
                                "\nDoba trvání: {$executionTime}s";
            $this->save();

            return $result;
        } catch (\Exception $e) {
            $this->last_run = now();
            $this->last_output = 'Chyba: ' . $e->getMessage();
            $this->save();

            return false;
        }
    }
}
