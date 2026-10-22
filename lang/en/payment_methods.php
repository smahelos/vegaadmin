<?php

return [
    'entity' => [
        'singular' => 'Payment method',
        'plural' => 'Payment methods',
    ],
    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'country' => 'Country',
        'currency' => 'Currency',
        'icon' => 'Icon',
        'is_active' => 'Active',
        'slug' => 'Identifier',
    ],
    'methods' => [
        'bankaccount' => 'Bank Transfer',
        'cash' => 'Cash',
        'credit_card' => 'Credit Card',
        'paypal' => 'PayPal',
        'other' => 'Other Payment Method',
    ],
];
