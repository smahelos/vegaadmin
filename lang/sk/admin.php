<?php

return [
    'cron_tasks' => [
        'cron_task' => 'Plánovaná úloha',
        'cron_tasks' => 'Plánované úlohy',
        
        'fields' => [
            'name' => 'Názov úlohy',
            'command' => 'Príkaz',
            'frequency' => 'Frekvencia',
            'custom_expression' => 'Vlastný Cron výraz',
            'run_at' => 'Čas spustenia',
            'day_of_week' => 'Deň v týždni',
            'day_of_month' => 'Deň v mesiaci',
            'is_active' => 'Aktívna',
            'description' => 'Popis',
            'last_run' => 'Posledný beh',
            'last_output' => 'Výstup posledného behu',
        ],
        
        'frequency' => [
            'daily' => 'Denne',
            'weekly' => 'Týždenne',
            'monthly' => 'Mesačne',
            'custom' => 'Vlastná',
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
        
        'tabs' => [
            'basic' => 'Základné informácie',
            'schedule' => 'Plán spúšťania',
            'history' => 'História',
        ],
        
        'hints' => [
            'custom_expression' => 'Zadajte cron výraz v štandardnom formáte (napr. "0 * * * *" pre spustenie každú hodinu)',
        ],
        
        'predefined' => [
            'upcoming_invoices' => 'Upomienky pred splatnosťou faktúry',
            'overdue_invoices' => 'Upomienky faktúr po splatnosti',
            'long_overdue_invoices' => 'Upomienky faktúry dlho po splatnosti',
        ],
        
        'descriptions' => [
            'upcoming_invoices' => 'Odosiela upomienky na faktúry, ktoré budú čoskoro splatné (3 dni pred splatnosťou)',
            'overdue_invoices' => 'Odosiela upomienky na faktúry, ktoré sú po splatnosti (až 7 dní)',
            'long_overdue_invoices' => 'Odosiela upomienky na faktúry, ktoré sú výrazne po splatnosti (14 a viac dní)',
        ],
    ],
];
