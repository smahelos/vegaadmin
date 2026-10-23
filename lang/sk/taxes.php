<?php

return [
    'entity' => [
        'singular' => 'Daň',
        'plural' => 'Dane',
    ],
    'fields' => [
        'name' => 'Názov',
        'rate' => 'Sadzba',
        'slug' => 'Identifikátor',
        'description' => 'Popis',
    ],
    'hints' => [
        'name' => 'Zadajte názov dane, napríklad DPH alebo daň z predaja.',
        'rate' => 'Zadajte sadzbu dane v percentách, napríklad 20 pre 20% daň.',
        'description' => 'Zadajte voliteľný popis dane.',
        'slug' => 'Stály identifikátor, podľa ktorého aplikácia sadzbu hľadá (napr. dph). Po vytvorení nemeňte.',
    ],
];
