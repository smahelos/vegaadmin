<?php

return [
    'entity' => [
        'singular' => 'Stav',
        'plural' => 'Stavy',
    ],
    'fields' => [
        'name' => 'Názov',
        'slug' => 'Identifikátor',
        'color' => 'Farba',
        'description' => 'Popis',
        'is_active' => 'Aktívny',
        'category_id' => 'Kategória',
    ],
    'statuses' => [
        'approved' => 'Schválené',
        'cancelled' => 'Stornované',
        'cancelled-expense' => 'Zrušený výdavok',
        'cancelled-status' => 'Zrušený status',
        'draft' => 'Návrh',
        'in-review' => 'V revízii',
        'overdue' => 'Po splatnosti',
        'paid' => 'Zaplatené',
        'paid-expense' => 'Zaplatený výdavok',
        'paid-status' => 'Zaplatený status',
        'partially-paid' => 'Čiastočne zaplatené',
        'pending' => 'Čaká na zaplatenie',
        'pending-payment' => 'Čakajúca platba',
        'rejected' => 'Odmietnuté',
    ],
    'filters' => [
        'category' => 'Filtrovať podľa kategórie',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Kategória stavu',
            'plural' => 'Kategórie stavov',
        ],
        'fields' => [
            'name' => 'Názov',
            'slug' => 'Identifikátor',
            'description' => 'Popis',
            'statuses_count' => 'Počet stavov',
            'statuses' => 'Zoznam stavov',
        ],
        'hints' => [
            'slug' => 'Bude automaticky vygenerovaný z názvu, ak nebude vyplnený',
        ],
    ],
];
