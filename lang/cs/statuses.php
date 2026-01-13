<?php

return [
    // Existing status labels
    'approved' => 'Schváleno',
    'cancelled' => 'Zrušeno',
    'cancelled-expense' => 'Zrušený výdaj',
    'cancelled-status' => 'Zrušený status',
    'draft' => 'Návrh',
    'in-review' => 'V přezkoumání',
    'overdue' => 'Po splatnosti',
    'paid' => 'Zaplaceno',
    'paid-expense' => 'Zaplacený výdaj',
    'paid-status' => 'Zaplacený status',
    'partially-paid' => 'Částečně zaplaceno',
    'pending' => 'Čekající',
    'pending-payment' => 'Čekající platba',
    'rejected' => 'Zamítnuto',
    'unpaid' => 'Nezaplaceno',

    // Fields & validation used in StatusRequest tests
    'fields' => [
        'name' => 'Název',
        'slug' => 'Slug',
        'color' => 'Barva',
        'description' => 'Popis',
        'is_active' => 'Aktivní',
        'category' => 'Kategorie',
    ],

    'validation' => [
        'name_required' => 'Pole název je povinné.',
        'slug_required' => 'Pole slug je povinné.',
        'slug_unique' => 'Slug je již použit.',
    ],
];
