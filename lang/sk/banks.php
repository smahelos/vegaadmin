<?php

return [
    'entity' => [
        'singular' => 'Banka',
        'plural' => 'Banky',
    ],
    'fields' => [
        'name' => 'Názov banky',
        'code' => 'Kód banky',
        'swift' => 'SWIFT kód',
        'country' => 'Krajina',
        'description' => 'Popis',
        'active' => 'Aktívna',
    ],
    'hints' => [
        'code' => 'Štvormiestny kód banky, napr. 0900.',
        'swift' => 'Kód SWIFT/BIC, napr. GIBASKBX.',
    ],
];
