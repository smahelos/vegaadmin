<?php

return [
    'cron_tasks' => [
        'cron_task' => 'Naplánovaná úloha',
        'cron_tasks' => 'Naplánované úlohy',
        
        'frequency' => [
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

        'tabs' => [
            'basic' => 'Základné nastavenia',
            'schedule' => 'Plánovanie',
            'advanced' => 'Pokročilé',
            'history' => 'História',
        ],

        'messages' => [
            'task_executed' => 'Úloha bola úspešne spustená',
            'execution_failed' => 'Spustenie úlohy zlyhalo',
        ],

        'hints' => [
            'custom_expression' => 'Ak zvolíte vlastný výraz, zadajte platný cron výraz.',
            'custom_expression_examples' => 'Príklady: "*/5 * * * *" (každých 5 minút), "0 0 * * *" (každý deň o polnoci), "0 12 * * 1-5" (každý pracovný deň na poludnie).',
            'command_params' => 'Zadajte parametre pre príkaz, napr. "--days=7 --force", alebo "--days-before=0 --days-after=14"',
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

        'validation' => [
            'invalid_cron_expression' => 'Neplatný CRON výraz. Použite formát: minúta hodina deň mesiac deň_v_týždni.',
        ],

        'buttons' => [
            'run_now' => 'Spustiť teraz',
        ],
    ],

    'artisan_commands' => [
        'command' => 'Artisan príkaz',
        'commands' => 'Artisan príkazy',
        'category' => 'Kategória príkazov',
        'categories' => 'Kategórie príkazov',
        'uncategorized' => 'Nezaradené príkazy',
        'uncategorized_description' => 'Automaticky detekované príkazy bez kategórie',
        
        'fields' => [
            'name' => 'Názov',
            'command' => 'Príkaz',
            'slug' => 'Identifikátor (slug)',
            'description' => 'Popis',
            'parameters_description' => 'Popis parametrov',
            'category' => 'Kategória',
            'commands_count' => 'Počet príkazov',
            'is_active' => 'Aktívne',
            'sort_order' => 'Poradie',
        ],

        'hints' => [
            'slug' => 'Jednoznačný identifikátor kategórie, používa sa v kóde (napr. "cron", "system", "admin")',
            'command' => 'Artisan príkaz, napr. "app:update-exchange-rates"',
            'parameters_description' => 'Popis parametrov príkazu, napr. "--date=YYYY-MM-DD: Nastaví dátum výmenného kurzu"',
            'sort_order' => 'Poradie príkazu v zozname (menšie číslo = vyššia pozícia)',
        ],
    ],

    'statuses' => [
        'status_types' => 'Typy stavov',
    ],
];
