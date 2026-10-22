<?php

return [
    'entity' => [
        'singular' => 'Banka',
        'plural' => 'Banky',
    ],
    'fields' => [
        'name' => 'Název banky',
        'code' => 'Kód banky',
        'swift' => 'SWIFT kód',
        'country' => 'Země',
        'description' => 'Popis',
        'active' => 'Aktivní',
    ],
    'hints' => [
        'code' => 'Čtyřmístný kód banky, např. 0100.',
        'swift' => 'Kód SWIFT/BIC, např. KOMBCZPP.',
    ],
];
