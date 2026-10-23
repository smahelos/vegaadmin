<?php

return [
    'entity' => [
        'singular' => 'Naplánovaná úloha',
        'plural' => 'Naplánované úlohy',
    ],
    'fields' => [
        'name' => 'Název',
        'command' => 'Příkaz',
        'base_command' => 'Základní příkaz',
        'command_params' => 'Parametry příkazu',
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
        'command_params' => 'Zadejte parametry pro příkaz, např. "--days=7 --force", nebo "--days-before=0 --days-after=14"',
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
    ],
    'predefined' => [
        'upcoming_invoices' => [
            'name' => 'Upomínky před splatností faktury',
            'description' => 'Odesílá upomínky na faktury, které budou brzy splatné (3 dny před splatností)',
        ],
        'overdue_invoices' => [
            'name' => 'Upomínky faktur po splatnosti',
            'description' => 'Odesílá upomínky na faktury, které jsou po splatnosti (až 7 dní)',
        ],
        'long_overdue_invoices' => [
            'name' => 'Upomínky faktury dlouho po splatnosti',
            'description' => 'Odesílá upomínky na faktury, které jsou výrazně po splatnosti (14 a více dní)',
        ],
    ],
];
