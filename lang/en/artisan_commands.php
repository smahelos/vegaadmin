<?php

return [
    'entity' => [
        'singular' => 'Artisan Command',
        'plural' => 'Artisan Commands',
    ],
    'fields' => [
        'name' => 'Name',
        'command' => 'Command',
        'description' => 'Description',
        'parameters_description' => 'Parameters Description',
        'category_id' => 'Category',
        'is_active' => 'Active',
        'sort_order' => 'Sort Order',
    ],
    'hints' => [
        'command' => 'Artisan command, e.g. "app:update-exchange-rates"',
        'parameters_description' => 'Description of command parameters, e.g. "--date=YYYY-MM-DD: Sets the exchange rate date"',
        'sort_order' => 'Command order in the list (smaller number = higher position)',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Command category',
            'plural' => 'Command Categories',
        ],
        'fields' => [
            'name' => 'Name',
            'slug' => 'Identifier',
            'description' => 'Description',
            'commands_count' => 'Number of Commands',
            'is_active' => 'Active',
        ],
        'hints' => [
            'slug' => 'Unique category identifier, used in code (e.g. "cron", "system", "admin")',
        ],
        'uncategorized' => [
            'name' => 'Uncategorized Commands',
            'description' => 'Auto-detected commands without a category',
        ],
    ],
];
