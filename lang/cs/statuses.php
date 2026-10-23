<?php

return [
    'entity' => [
        'singular' => 'Stav',
        'plural' => 'Stavy',
    ],
    'fields' => [
        'name' => 'Název',
        'slug' => 'Identifikátor',
        'color' => 'Barva',
        'description' => 'Popis',
        'is_active' => 'Aktivní',
        'category_id' => 'Kategorie',
    ],
    'statuses' => [
        'approved' => 'Schváleno',
        'cancelled' => 'Stornováno',
        'cancelled-expense' => 'Zrušený výdaj',
        'cancelled-status' => 'Zrušený status',
        'draft' => 'Návrh',
        'in-review' => 'V přezkoumání',
        'overdue' => 'Po splatnosti',
        'paid' => 'Zaplaceno',
        'paid-expense' => 'Zaplacený výdaj',
        'paid-status' => 'Zaplacený status',
        'partially-paid' => 'Částečně zaplaceno',
        'pending' => 'Čeká na zaplacení',
        'pending-payment' => 'Čekající platba',
        'rejected' => 'Zamítnuto',
        'unpaid' => 'Nezaplaceno',
    ],
    'filters' => [
        'category' => 'Filtrovat podle kategorie',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Kategorie stavu',
            'plural' => 'Kategorie stavů',
        ],
        'fields' => [
            'name' => 'Název',
            'slug' => 'Identifikátor',
            'description' => 'Popis',
            'statuses_count' => 'Počet stavů',
            'statuses' => 'Seznam stavů',
        ],
        'hints' => [
            'slug' => 'Bude automaticky vygenerován z názvu, pokud nebude zadáno',
        ],
    ],
];
