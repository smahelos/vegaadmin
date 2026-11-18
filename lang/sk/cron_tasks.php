<?php

return [
    'entity' => [
        'singular' => 'Naplánovaná úloha',
        'plural' => 'Naplánované úlohy',
    ],
    'fields' => [
        'name' => 'Názov',
        'command' => 'Príkaz',
        'parameters' => 'Parametre príkazu',
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
        'parameters' => 'Voľby príkazu oddelené medzerou, napr. "--days-before=5 --days-after=2" alebo "--analyze". Hodnota smie obsahovať len písmená, číslice a znaky _ . , : -',
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
        'parameters' => 'Parametre príkazu musia byť voľby v tvare --nazov alebo --nazov=hodnota oddelené medzerou; hodnota smie obsahovať len písmená, číslice a znaky _ . , : -',
    ],
    'predefined' => [
        'payment_reminders' => [
            'name' => 'Upomienky faktúr',
            'description' => 'Odosiela upomienky na faktúry 3 dni pred splatnosťou, v deň splatnosti a deň po splatnosti',
        ],
    ],
];
