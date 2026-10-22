<?php

return [
    'entity' => [
        'singular' => 'Zahlungsmethode',
        'plural' => 'Zahlungsmethoden',
    ],
    'fields' => [
        'name' => 'Name',
        'description' => 'Beschreibung',
        'country' => 'Land',
        'currency' => 'Währung',
        'icon' => 'Symbol',
        'is_active' => 'Aktiv',
        'slug' => 'Kennung',
    ],
    'methods' => [
        'bankaccount' => 'Banküberweisung',
        'cash' => 'Bargeld',
        'credit_card' => 'Kreditkarte',
        'paypal' => 'PayPal',
        'other' => 'Andere Zahlungsmethode',
    ],
];
