<?php

return [
    'title' => 'Banka',
    'title_plural' => 'Banky',
    'list' => 'Zoznam bánk',
    'created_at' => 'Vytvorené',
    'updated_at' => 'Aktualizované',

    'actions' => [
        'edit' => 'Upraviť',
        'delete' => 'Odstrániť',
        'view' => 'Zobraziť',
    ],

    'fields' => [
        'name' => 'Názov banky',
        'code' => 'Kód banky',
        'swift' => 'SWIFT kód',
        'country' => 'Krajina',
        'active' => 'Aktívna',
    ],

    'validation' => [
        'name' => 'Názov banky je povinný.',
        'code' => 'Kód banky je povinný.',
        'swift' => 'SWIFT kód je povinný.',
        'success_create' => 'Banka bola úspešne vytvorená.',
        'success_update' => 'Banka bola úspešne upravená.',
        'success_delete' => 'Banka bola úspešne odstránená.',
        'error_create' => 'Nastala chyba pri vytváraní banky.',
        'error_update' => 'Nastala chyba pri úprave banky.',
        'error_delete' => 'Nastala chyba pri odstraňovaní banky.',
        'code_unique' => 'Kód banky už existuje.',
        'country' => 'Krajina je povinná.',
        'country_size' => 'Krajina musí mať 2 znaky.',
    ],
];
