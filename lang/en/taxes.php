<?php

return [
    'entity' => [
        'singular' => 'Tax',
        'plural' => 'Taxes',
    ],
    'fields' => [
        'name' => 'Name',
        'rate' => 'Rate',
        'slug' => 'Identifier',
        'description' => 'Description',
    ],
    'hints' => [
        'name' => 'Enter the name of the tax.',
        'rate' => 'Enter the tax rate as a percentage.',
        'description' => 'Enter a description for the tax.',
        'slug' => 'Stable identifier the application looks the rate up by (e.g. dph). Do not change it after creating the rate.',
    ],
    'description' => 'Description',
];
