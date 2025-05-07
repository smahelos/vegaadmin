<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'day_of_month' => 'integer'
    ];

    /**
     * Vrátí čitelný název frekvence
     */
    public function getFrequencyNameAttribute()
    {
        $frequencies = [
            'daily' => __('admin.cron_tasks.frequency.daily'),
            'weekly' => __('admin.cron_tasks.frequency.weekly'),
            'monthly' => __('admin.cron_tasks.frequency.monthly'),
            'custom' => __('admin.cron_tasks.frequency.custom'),
        ];

        return $frequencies[$this->frequency] ?? $this->frequency;
    }

    /**
     * Vrátí čitelný název dne v týdnu
     */
    public function getDayOfWeekNameAttribute()
    {
        if ($this->day_of_week === null) {
            return null;
        }

        $days = [
            0 => __('admin.cron_tasks.days.sunday'),
            1 => __('admin.cron_tasks.days.monday'),
            2 => __('admin.cron_tasks.days.tuesday'),
            3 => __('admin.cron_tasks.days.wednesday'),
            4 => __('admin.cron_tasks.days.thursday'),
            5 => __('admin.cron_tasks.days.friday'),
            6 => __('admin.cron_tasks.days.saturday'),
        ];

        return $days[$this->day_of_week] ?? $this->day_of_week;
    }

    /**
     * Vrátí formátovaný čas spuštění
     */
    public function getFormattedRunAtAttribute()
    {
        return $this->run_at ? $this->run_at->format('H:i') : null;
    }

    /**
     * Get the run_at attribute.
     */
    protected function runAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $value ? \Carbon\Carbon::parse($value)->format('H:i') : null;
            },
            set: function ($value) {
                return $value ?: null;
            },
        );
    }

    /**
     * Get the run_at attribute.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getRunAtAttribute($value)
    {
        // Zde vracíme hodnotu jako řetězec ve formátu H:i
        return $value ? (is_string($value) ? $value : \Carbon\Carbon::parse($value)->format('H:i')) : null;
    }

    /**
     * Set the run_at attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setRunAtAttribute($value)
    {
        $this->attributes['run_at'] = $value;
    }

    /**
     * Získá základní příkaz bez parametrů.
     */
    public function getBaseCommandAttribute()
    {
        if (empty($this->command)) {
            return null;
        }
        
        $parts = explode(' ', trim($this->command), 2);
        return $parts[0] ?? null;
    }

    /**
     * Získá parametry příkazu.
     */
    public function getCommandParamsAttribute()
    {
        if (empty($this->command)) {
            return null;
        }
        
        $parts = explode(' ', trim($this->command), 2);
        return $parts[1] ?? '';
    }

    /**
     * Nastaví kompletní příkaz na základě základního příkazu a parametrů.
     */
    public function setCommandAttribute($value)
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
        if (!empty($params)) {
            $fullCommand .= ' ' . trim($params);
        }

        $this->attributes['command'] = trim($fullCommand);
    }

    /**
     * Získá CRON výraz podle nastavené frekvence.
     *
     * @return string
     */
    public function getCronExpression(): string
    {
        if ($this->frequency === 'custom') {
            return $this->custom_expression;
        }

        $runAt = $this->run_at ? \Carbon\Carbon::parse($this->run_at) : \Carbon\Carbon::parse('00:00');
        $minute = $runAt->format('i');
        $hour = $runAt->format('H');

        return match($this->frequency) {
            'daily' => "{$minute} {$hour} * * *",
            'weekly' => "{$minute} {$hour} * * {$this->day_of_week}",
            'monthly' => "{$minute} {$hour} {$this->day_of_month} * *",
            default => '* * * * *', // Nemělo by nastat
        };
    }

    /**
     * Získá čas příštího spuštění.
     *
     * @return \Carbon\Carbon|null
     */
    public function getNextRunDate(): ?\Carbon\Carbon
    {
        try {
            $cron = new \Cron\CronExpression($this->getCronExpression());
            return \Carbon\Carbon::createFromFormat(
                'Y-m-d H:i:s', 
                $cron->getNextRunDate()->format('Y-m-d H:i:s')
            );
        } catch (\Exception $e) {
            \Log::error('Neplatný CRON výraz: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simuluje spuštění úlohy.
     *
     * @return mixed
     */
    public function simulateRun()
    {
        try {
            $startTime = microtime(true);
            
            // Rozlišení mezi Artisan příkazem a PHP funkcí
            if (strpos($this->command, '::') !== false) {
                // PHP funkce
                list($class, $method) = explode('::', $this->command);
                $result = app($class)->$method();
            } else {
                // Artisan příkaz
                $result = \Artisan::call($this->command);
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Aktualizace informací o posledním spuštění
            $this->last_run = now();
            $this->last_output = "Výsledek: " . (is_string($result) ? $result : \Artisan::output()) . 
                                "\nDoba trvání: {$executionTime}s";
            $this->save();
            
            return $result;
        } catch (\Exception $e) {
            $this->last_run = now();
            $this->last_output = "Chyba: " . $e->getMessage();
            $this->save();
            
            return false;
        }
    }
}
