<?php

return [
    'entity' => [
        'singular' => 'Výdaj',
        'plural' => 'Výdaje',
    ],
    'fields' => [
        'expense_date' => 'Datum',
        'amount' => 'Částka',
        'currency' => 'Měna',
        'supplier_id' => 'Dodavatel',
        'category_id' => 'Kategorie',
        'payment_method_id' => 'Způsob platby',
        'reference_number' => 'Referenční číslo',
        'description' => 'Popis',
        'receipt_file' => 'Doklad',
        'attachments' => 'Přílohy',
        'tax_rate' => 'Sazba DPH',
        'tax_included' => 'Daň je součástí částky',
        'tax_amount' => 'Částka daně',
        'status_id' => 'Stav',
        'user_id' => 'Vlastník',
    ],
    'hints' => [
        'reference_number' => 'Např. číslo faktury, číslo účtenky apod.',
        'attachments' => 'Nahrajte soubory spojené s tímto výdajem (obrázky, PDF, dokumenty).',
        'tax_included' => 'Označte, pokud je daň již zahrnuta v celkové částce',
    ],
    'filters' => [
        'date_range' => 'Časové období',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Kategorie výdaje',
            'plural' => 'Kategorie výdajů',
        ],
        'fields' => [
            'name' => 'Název',
            'slug' => 'Identifikátor',
            'color' => 'Barva',
            'description' => 'Popis',
            'is_active' => 'Aktivní',
        ],
        'hints' => [
            'slug' => 'Automaticky se vygeneruje z názvu, pokud nebude vyplněno',
        ],
    ],
];
