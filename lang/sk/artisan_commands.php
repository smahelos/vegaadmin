<?php

return [
    'entity' => [
        'singular' => 'Artisan príkaz',
        'plural' => 'Artisan príkazy',
    ],
    'fields' => [
        'name' => 'Názov',
        'command' => 'Príkaz',
        'description' => 'Popis',
        'parameters_description' => 'Popis parametrov',
        'category_id' => 'Kategória',
        'is_active' => 'Aktívne',
        'sort_order' => 'Poradie',
    ],
    'hints' => [
        'command' => 'Artisan príkaz, napr. "app:update-exchange-rates"',
        'parameters_description' => 'Popis parametrov príkazu, napr. "--date=YYYY-MM-DD: Nastaví dátum výmenného kurzu"',
        'sort_order' => 'Poradie príkazu v zozname (menšie číslo = vyššia pozícia)',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Kategória príkazov',
            'plural' => 'Kategórie príkazov',
        ],
        'fields' => [
            'name' => 'Názov',
            'slug' => 'Identifikátor',
            'description' => 'Popis',
            'commands_count' => 'Počet príkazov',
            'is_active' => 'Aktívne',
        ],
        'hints' => [
            'slug' => 'Jednoznačný identifikátor kategórie, používa sa v kóde (napr. "cron", "system", "admin")',
        ],
        'uncategorized' => [
            'name' => 'Nezaradené príkazy',
            'description' => 'Automaticky detekované príkazy bez kategórie',
        ],
    ],
];
