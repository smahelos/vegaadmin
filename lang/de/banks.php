<?php

return [
    'entity' => [
        'singular' => 'Bank',
        'plural' => 'Banken',
    ],
    'fields' => [
        'name' => 'Bankname',
        'code' => 'Bankcode',
        'swift' => 'SWIFT-Code',
        'country' => 'Land',
        'description' => 'Beschreibung',
        'active' => 'Aktiv',
    ],
    'hints' => [
        'code' => 'Vierstellige Bankleitzahl, z. B. 0100.',
        'swift' => 'SWIFT/BIC-Code, z. B. KOMBCZPP.',
    ],
];
