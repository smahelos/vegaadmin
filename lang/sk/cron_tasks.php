<?php

return [
    'entity' => [
        'singular' => 'Naplánovaná úloha',
        'plural' => 'Naplánované úlohy',
    ],
    'fields' => [
        'name' => 'Názov',
        'command' => 'Príkaz',
        'base_command' => 'Základný príkaz',
        'command_params' => 'Parametre príkazu',
        'frequency' => 'Frekvencia',
        'custom_expression' => 'Vlastný cron výraz',
        'run_at' => 'Čas spustenia',
        'day_of_week' => 'Deň v týždni',
        'day_of_month' => 'Deň v mesiaci',
        'is_active' => 'Aktívne',
        'last_run' => 'Posledné spustenie',
        'last_output' => 'Posledný výstup',
        'description' => 'Popis',
        'next_run' => 'Ďalšie spustenie',
    ],
    'hints' => [
        'command_params' => 'Zadajte parametre pre príkaz, napr. "--days=7 --force", alebo "--days-before=0 --days-after=14"',
        'custom_expression' => 'Ak zvolíte vlastný výraz, zadajte platný cron výraz. Príklady: "*/5 * * * *" (každých 5 minút), "0 0 * * *" (každý deň o polnoci), "0 12 * * 1-5" (každý pracovný deň na poludnie).',
    ],
    'tabs' => [
        'basic' => 'Základné nastavenia',
        'schedule' => 'Plánovanie',
        'history' => 'História',
    ],
    'frequencies' => [
        'daily' => 'Denne',
        'weekly' => 'Týždenne',
        'monthly' => 'Mesačne',
        'custom' => 'Vlastné',
    ],
    'days' => [
        'sunday' => 'Nedeľa',
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
    ],
    'actions' => [
        'run_now' => 'Spustiť teraz',
    ],
    'messages' => [
        'task_executed' => 'Úloha bola úspešne spustená',
        'execution_failed' => 'Spustenie úlohy zlyhalo',
    ],
    'validation' => [
        'cron_expression' => 'Neplatný CRON výraz. Použite formát: minúta hodina deň mesiac deň_v_týždni.',
    ],
    'predefined' => [
        'upcoming_invoices' => [
            'name' => 'Upomienky pred splatnosťou faktúry',
            'description' => 'Odosiela upomienky na faktúry, ktoré budú čoskoro splatné (3 dni pred splatnosťou)',
        ],
        'overdue_invoices' => [
            'name' => 'Upomienky faktúr po splatnosti',
            'description' => 'Odosiela upomienky na faktúry, ktoré sú po splatnosti (až 7 dní)',
        ],
        'long_overdue_invoices' => [
            'name' => 'Upomienky faktúry dlho po splatnosti',
            'description' => 'Odosiela upomienky na faktúry, ktoré sú výrazne po splatnosti (14 a viac dní)',
        ],
    ],
];
