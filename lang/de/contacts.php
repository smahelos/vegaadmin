<?php

return [
    'entity' => [
        'singular' => 'Kontakt',
        'plural' => 'Kontakte',
    ],
    'fields' => [
        'contact_person' => 'Ansprechpartner',
    ],
    'hints' => [
        'contact_person' => 'Die Person, mit der Sie verhandeln; ersetzt nicht den Namen des abgerechneten Unternehmens.',
    ],
    'actions' => [
        'create' => 'Kontakt hinzufügen',
        'back_to_list' => 'Zurück zur Liste',
    ],
    'messages' => [
        'created' => 'Der Kontakt wurde angelegt.',
        'updated' => 'Der Kontakt wurde geändert.',
        'archived' => 'Der Kontakt wurde archiviert.',
        'restored' => 'Der Kontakt wurde wiederhergestellt.',
        'limit_exceeded' => 'Sie haben die Höchstzahl aktiver Kontakte erreicht. Archivieren Sie einen Kontakt oder erweitern Sie Ihren Tarif.',
        'confirm_archive' => 'Möchten Sie den Kontakt wirklich archivieren? Für neue Belege steht er dann nicht zur Verfügung.',
    ],
    'titles' => [
        'index' => 'Kontakte',
        'create' => 'Neuer Kontakt',
        'edit' => 'Kontakt bearbeiten',
        'empty' => 'Sie haben noch keine aktiven Kontakte',
        'empty_archived' => 'Keine archivierten Kontakte',
        'empty_message' => 'Fügen Sie die Unternehmen und Personen hinzu, mit denen Sie Geschäfte machen.',
    ],
];
