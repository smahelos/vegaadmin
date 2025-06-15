<?php

return [
    'title' => 'Bank',
    'title_plural' => 'Banken',
    'list' => 'Bankenliste',
    'created_at' => 'Erstellt am',
    'updated_at' => 'Aktualisiert am',

    'actions' => [
        'edit' => 'Bearbeiten',
        'delete' => 'Löschen',
        'view' => 'Anzeigen',
    ],

    'fields' => [
        'name' => 'Bankname',
        'code' => 'Bankcode',
        'swift' => 'SWIFT-Code',
        'country' => 'Land',
        'active' => 'Aktiv',
    ],

    'validation' => [
        'name' => 'Der Bankname ist erforderlich.',
        'code' => 'Der Bankcode ist erforderlich.',
        'swift' => 'Der SWIFT-Code ist erforderlich.',
        'success_create' => 'Bank wurde erfolgreich erstellt.',
        'success_update' => 'Bank wurde erfolgreich aktualisiert.',
        'success_delete' => 'Bank wurde erfolgreich gelöscht.',
        'error_create' => 'Beim Erstellen der Bank ist ein Fehler aufgetreten.',
        'error_update' => 'Beim Aktualisieren der Bank ist ein Fehler aufgetreten.',
        'error_delete' => 'Beim Löschen der Bank ist ein Fehler aufgetreten.',
        'code_unique' => 'Der Bankcode existiert bereits.',
        'country' => 'Das Land ist erforderlich.',
        'country_size' => 'Das Land muss aus 2 Zeichen bestehen.',
    ],
];
