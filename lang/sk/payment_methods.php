<?php

return [
    'entity' => [
        'singular' => 'Spôsob platby',
        'plural' => 'Spôsoby platby',
    ],
    'fields' => [
        'name' => 'Názov',
        'description' => 'Popis',
        'country' => 'Krajina',
        'currency' => 'Mena',
        'icon' => 'Ikona',
        'is_active' => 'Aktívna',
        'slug' => 'Identifikátor',
    ],
    'methods' => [
        'bankaccount' => 'Bankový prevod',
        'cash' => 'Hotovosť',
        'credit_card' => 'Platobná karta',
        'paypal' => 'PayPal',
        'other' => 'Iná platobná metóda',
    ],
];
