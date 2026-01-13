<?php

return [
    // Existing status labels
    'approved' => 'Schválené',
    'cancelled' => 'Zrušené',
    'cancelled-expense' => 'Zrušený výdavok',
    'cancelled-status' => 'Zrušený status',
    'draft' => 'Návrh',
    'in-review' => 'V revízii',
    'overdue' => 'Po splatnosti',
    'paid' => 'Zaplatené',
    'paid-expense' => 'Zaplatený výdavok',
    'paid-status' => 'Zaplatený status',
    'partially-paid' => 'Čiastočne zaplatené',
    'pending' => 'Čakajúce',
    'pending-payment' => 'Čakajúca platba',
    'rejected' => 'Odmietnuté',
    'unpaid' => 'Nezaplatené',

    // Fields & validation used in StatusRequest tests
    'fields' => [
        'name' => 'Názov',
        'slug' => 'Slug',
        'color' => 'Farba',
        'description' => 'Popis',
        'is_active' => 'Aktívny',
        'category' => 'Kategória',
    ],
    
    'validation' => [
        'name_required' => 'Pole názov je povinné.',
        'slug_required' => 'Pole slug je povinné.',
        'slug_unique' => 'Slug je už použitý.',
    ],
];
