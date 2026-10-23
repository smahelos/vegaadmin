<?php

return [
    'entity' => [
        'singular' => 'Artisan příkaz',
        'plural' => 'Artisan příkazy',
    ],
    'fields' => [
        'name' => 'Název',
        'command' => 'Příkaz',
        'description' => 'Popis',
        'parameters_description' => 'Popis parametrů',
        'category_id' => 'Kategorie',
        'is_active' => 'Aktivní',
        'sort_order' => 'Pořadí',
    ],
    'hints' => [
        'command' => 'Artisan příkaz, např. "app:update-exchange-rates"',
        'parameters_description' => 'Popis parametrů příkazu, např. "--date=YYYY-MM-DD: Nastaví datum směnného kurzu"',
        'sort_order' => 'Pořadí příkazu v seznamu (menší číslo = vyšší pozice)',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Kategorie příkazů',
            'plural' => 'Kategorie příkazů',
        ],
        'fields' => [
            'name' => 'Název',
            'slug' => 'Identifikátor',
            'description' => 'Popis',
            'commands_count' => 'Počet příkazů',
            'is_active' => 'Aktivní',
        ],
        'hints' => [
            'slug' => 'Jednoznačný identifikátor kategorie, používá se v kódu (např. "cron", "system", "admin")',
        ],
        'uncategorized' => [
            'name' => 'Nezařazené příkazy',
            'description' => 'Automaticky detekované příkazy bez kategorie',
        ],
    ],
];
