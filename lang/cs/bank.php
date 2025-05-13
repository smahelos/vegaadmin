<?php

return [
    'title' => 'Banka',
    'title_plural' => 'Banky',
    'list' => 'Seznam bank',
    'created_at' => 'Vytvořeno',
    'updated_at' => 'Aktualizováno',

    'actions' => [
        'edit' => 'Upravit',
        'delete' => 'Smazat',
        'view' => 'Zobrazit',
    ],

    'fields' => [
        'name' => 'Název banky',
        'code' => 'Kód banky',
        'swift' => 'SWIFT kód',
        'country' => 'Země',
        'active' => 'Aktivní',
    ],

    'validation' => [
        'name' => 'Název banky je povinný.',
        'code' => 'Kód banky je povinný.',
        'swift' => 'SWIFT kód je povinný.',
        'success_create' => 'Banka byla úspěšně vytvořena.',
        'success_update' => 'Banka byla úspěšně upravena.',
        'success_delete' => 'Banka byla úspěšně smazána.',
        'error_create' => 'Nastala chyba při vytváření banky.',
        'error_update' => 'Nastala chyba při úpravě banky.',
        'error_delete' => 'Nastala chyba při mazání banky.',
        'code_unique' => 'Kód banky již existuje.',
        'country' => 'Země je povinná.',
        'country_size' => 'Země musí mít 2 znaky.',
    ],
];
