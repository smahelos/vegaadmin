<?php

return [
    'entity' => [
        'singular' => 'Způsob platby',
        'plural' => 'Způsoby platby',
    ],
    'fields' => [
        'name' => 'Název',
        'description' => 'Popis',
        'country' => 'Země',
        'currency' => 'Měna',
        'icon' => 'Ikona',
        'is_active' => 'Aktivní',
        'slug' => 'Identifikátor',
    ],
    'methods' => [
        'bankaccount' => 'Bankovní převod',
        'cash' => 'Hotovost',
        'credit_card' => 'Platební karta',
        'paypal' => 'PayPal',
        'other' => 'Jiná platební metoda',
    ],
];
