<?php

return [
    'entity' => [
        'singular' => 'Bank',
        'plural' => 'Banks',
    ],
    'fields' => [
        'name' => 'Bank Name',
        'code' => 'Bank Code',
        'swift' => 'SWIFT Code',
        'country' => 'Country',
        'description' => 'Description',
        'active' => 'Active',
    ],
    'hints' => [
        'code' => 'Four-digit bank code, e.g. 0100.',
        'swift' => 'SWIFT/BIC code, e.g. KOMBCZPP.',
    ],
];
