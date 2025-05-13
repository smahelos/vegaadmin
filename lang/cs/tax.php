<?php

return [
    'title' => 'Daně',
    'create' => 'Vytvořit daň',
    'edit' => 'Upravit daň',
    'list' => 'Seznam daní',
    'delete' => 'Smazat daň',
    'name' => 'Název daně',
    'rate' => 'Sazba daně',
    'actions' => 'Akce',
    'success_create' => 'Daň byla úspěšně vytvořena.',
    'success_update' => 'Daň byla úspěšně upravena.',
    'success_delete' => 'Daň byla úspěšně smazána.',
    'error_create' => 'Nastala chyba při vytváření daně.',
    'error_update' => 'Nastala chyba při úpravě daně.',
    'error_delete' => 'Nastala chyba při mazání daně.',
    'created_at' => 'Vytvořeno',
    'updated_at' => 'Aktualizováno',

    'fields' => [
        'name' => 'Název',
        'rate' => 'Sazba',
        'slug' => 'Slug',
        'description' => 'Popis',
    ],

    'hints' => [
        'name' => 'Zadejte název daně, například DPH nebo daň z prodeje.',
        'rate' => 'Zadejte sazbu daně v procentech, například 20 pro 20% daň.',
        'description' => 'Zadejte volitelný popis daně.',
    ],
];
