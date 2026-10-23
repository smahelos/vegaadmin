<?php

return [
    'entity' => [
        'singular' => 'Výdavok',
        'plural' => 'Výdavky',
    ],
    'fields' => [
        'expense_date' => 'Dátum',
        'amount' => 'Čiastka',
        'currency' => 'Mena',
        'supplier_id' => 'Dodávateľ',
        'category_id' => 'Kategória',
        'payment_method_id' => 'Spôsob platby',
        'reference_number' => 'Referenčné číslo',
        'description' => 'Popis',
        'receipt_file' => 'Doklad',
        'attachments' => 'Prílohy',
        'tax_rate' => 'Sadzba DPH',
        'tax_included' => 'Daň je súčasťou čiastky',
        'tax_amount' => 'DPH',
        'status_id' => 'Stav',
        'user_id' => 'Vlastník',
    ],
    'hints' => [
        'reference_number' => 'Napr. číslo faktúry, číslo dokladu atď.',
        'attachments' => 'Nahrajte súbory spojené s týmto výdajom (obrázky, PDF, dokumenty).',
        'tax_included' => 'Označte, ak je daň už zahrnutá v celkovej čiastke',
    ],
    'filters' => [
        'date_range' => 'Obdobie',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Kategória výdavku',
            'plural' => 'Kategórie výdavkov',
        ],
        'fields' => [
            'name' => 'Názov',
            'slug' => 'Identifikátor',
            'color' => 'Farba',
            'description' => 'Popis',
            'is_active' => 'Aktívny',
        ],
        'hints' => [
            'slug' => 'Automaticky generované z názvu, ak nie je vyplnené',
        ],
    ],
];
