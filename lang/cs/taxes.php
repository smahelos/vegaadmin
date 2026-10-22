<?php

return [
    'entity' => [
        'singular' => 'Daň',
        'plural' => 'Daně',
    ],
    'fields' => [
        'name' => 'Název',
        'rate' => 'Sazba',
        'slug' => 'Identifikátor',
        'description' => 'Popis',
    ],
    'hints' => [
        'name' => 'Zadejte název daně, například DPH nebo daň z prodeje.',
        'rate' => 'Zadejte sazbu daně v procentech, například 20 pro 20% daň.',
        'description' => 'Zadejte volitelný popis daně.',
        'slug' => 'Stálý identifikátor, podle kterého aplikace sazbu hledá (např. dph). Po vytvoření neměňte.',
    ],
];
