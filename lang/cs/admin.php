<?php

return [
    'cron_tasks' => [
        'cron_task' => 'Plánovač úloh',
        'cron_tasks' => 'Plánovače úloh',
        
        'frequency' => [
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

        'tabs' => [
            'basic' => 'Základní nastavení',
            'schedule' => 'Plánování',
            'advanced' => 'Pokročilé',
            'history' => 'Historie',
        ],

        'messages' => [
            'task_executed' => 'Úloha byla úspěšně spuštěna',
            'execution_failed' => 'Spuštění úlohy selhalo',
        ],

        'hints' => [
            'custom_expression' => 'Pokud zvolíte vlastní výraz, zadejte platný cron výraz.',
            'custom_expression_examples' => 'Příklady: "*/5 * * * *" (každých 5 minut), "0 0 * * *" (každý den o půlnoci), "0 12 * * 1-5" (každý pracovní den v poledne).',
            'command_params' => 'Zadejte parametry pro příkaz, např. "--days=7 --force", nebo "--days-before=0 --days-after=14"',
        ],
        
        'predefined' => [
            'upcoming_invoices' => 'Upomínky před splatností faktury',
            'overdue_invoices' => 'Upomínky faktur po splatnosti',
            'long_overdue_invoices' => 'Upomínky faktury dlouho po splatnosti',
        ],
        
        'descriptions' => [
            'upcoming_invoices' => 'Odesílá upomínky na faktury, které budou brzy splatné (3 dny před splatností)',
            'overdue_invoices' => 'Odesílá upomínky na faktury, které jsou po splatnosti (až 7 dní)',
            'long_overdue_invoices' => 'Odesílá upomínky na faktury, které jsou výrazně po splatnosti (14 a více dní)',
        ],

        'validation' => [
            'invalid_cron_expression' => 'Neplatný CRON výraz. Použijte formát: minuta hodina den měsíc den_v_týdnu.',
        ],

        'buttons' => [
            'run_now' => 'Spustit nyní',
        ],
    ],

    'artisan_commands' => [
        'command' => 'Artisan příkaz',
        'commands' => 'Artisan příkazy',
        'category' => 'Kategorie příkazů',
        'categories' => 'Kategorie příkazů',
        'uncategorized' => 'Nezařazené příkazy',
        'uncategorized_description' => 'Automaticky detekované příkazy bez kategorie',
        
        'fields' => [
            'name' => 'Název',
            'command' => 'Příkaz',
            'slug' => 'Identifikátor (slug)',
            'description' => 'Popis',
            'parameters_description' => 'Popis parametrů',
            'category' => 'Kategorie',
            'commands_count' => 'Počet příkazů',
            'is_active' => 'Aktivní',
            'sort_order' => 'Pořadí',
        ],

        'hints' => [
            'slug' => 'Jednoznačný identifikátor kategorie, používá se v kódu (např. "cron", "system", "admin")',
            'command' => 'Artisan příkaz, např. "app:update-exchange-rates"',
            'parameters_description' => 'Popis parametrů příkazu, např. "--date=YYYY-MM-DD: Nastaví datum směnného kurzu"',
            'sort_order' => 'Pořadí příkazu v seznamu (menší číslo = vyšší pozice)',
        ],
    ],
];
