<?php

return [
    'entity' => [
        'singular' => 'Artisan-Befehl',
        'plural' => 'Artisan-Befehle',
    ],
    'fields' => [
        'name' => 'Name',
        'command' => 'Befehl',
        'description' => 'Beschreibung',
        'parameters_description' => 'Parameterbeschreibung',
        'category_id' => 'Kategorie',
        'is_active' => 'Aktiv',
        'sort_order' => 'Sortierreihenfolge',
    ],
    'hints' => [
        'command' => 'Artisan-Befehl, z.B. "app:update-exchange-rates"',
        'parameters_description' => 'Beschreibung der Befehlsparameter, z.B. "--date=YYYY-MM-DD: Legt das Wechselkursdatum fest"',
        'sort_order' => 'Befehlsreihenfolge in der Liste (kleinere Zahl = höhere Position)',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Befehlskategorie',
            'plural' => 'Befehlskategorien',
        ],
        'fields' => [
            'name' => 'Name',
            'slug' => 'Kennung',
            'description' => 'Beschreibung',
            'commands_count' => 'Anzahl der Befehle',
            'is_active' => 'Aktiv',
        ],
        'hints' => [
            'slug' => 'Eindeutige Kategoriekennung, wird im Code verwendet (z.B. "cron", "system", "admin")',
        ],
        'uncategorized' => [
            'name' => 'Nicht kategorisierte Befehle',
            'description' => 'Automatisch erkannte Befehle ohne Kategorie',
        ],
    ],
];
