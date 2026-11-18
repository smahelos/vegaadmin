<?php

return [
    'entity' => [
        'singular' => 'Naplánovaná úloha',
        'plural' => 'Naplánované úlohy',
    ],
    'fields' => [
        'name' => 'Název',
        'command' => 'Příkaz',
        'parameters' => 'Parametry příkazu',
        'frequency' => 'Frekvence',
        'custom_expression' => 'Vlastní cron výraz',
        'run_at' => 'Čas spuštění',
        'day_of_week' => 'Den v týdnu',
        'day_of_month' => 'Den v měsíci',
        'is_active' => 'Aktivní',
        'last_run' => 'Poslední spuštění',
        'last_output' => 'Poslední výstup',
        'description' => 'Popis',
        'next_run' => 'Příští spuštění',
    ],
    'hints' => [
        'parameters' => 'Volby příkazu oddělené mezerou, např. "--days-before=5 --days-after=2" nebo "--analyze". Hodnota smí obsahovat jen písmena, číslice a znaky _ . , : -',
        'custom_expression' => 'Pokud zvolíte vlastní výraz, zadejte platný cron výraz. Příklady: "*/5 * * * *" (každých 5 minut), "0 0 * * *" (každý den o půlnoci), "0 12 * * 1-5" (každý pracovní den v poledne).',
    ],
    'tabs' => [
        'basic' => 'Základní nastavení',
        'schedule' => 'Plánování',
        'history' => 'Historie',
    ],
    'frequencies' => [
        'daily' => 'Denně',
        'weekly' => 'Týdně',
        'monthly' => 'Měsíčně',
        'custom' => 'Vlastní',
    ],
    'days' => [
        'sunday' => 'Neděle',
        'monday' => 'Pondělí',
        'tuesday' => 'Úterý',
        'wednesday' => 'Středa',
        'thursday' => 'Čtvrtek',
        'friday' => 'Pátek',
        'saturday' => 'Sobota',
    ],
    'actions' => [
        'run_now' => 'Spustit nyní',
    ],
    'messages' => [
        'task_executed' => 'Úloha byla úspěšně spuštěna',
        'execution_failed' => 'Spuštění úlohy selhalo',
    ],
    'validation' => [
        'cron_expression' => 'Neplatný CRON výraz. Použijte formát: minuta hodina den měsíc den_v_týdnu.',
        'parameters' => 'Parametry příkazu musí být volby ve tvaru --nazev nebo --nazev=hodnota oddělené mezerou; hodnota smí obsahovat jen písmena, číslice a znaky _ . , : -',
    ],
    'predefined' => [
        'payment_reminders' => [
            'name' => 'Upomínky faktur',
            'description' => 'Odesílá upomínky na faktury 3 dny před splatností, v den splatnosti a den po splatnosti',
        ],
    ],
];
