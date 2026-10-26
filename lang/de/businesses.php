<?php

return [
    'entity' => [
        'singular' => 'Firma',
        'plural' => 'Meine Firmen',
    ],
    'fields' => [
        'logo' => 'Logo',
        'default' => 'Standard',
        'new_default' => 'Neue Standardfirma',
    ],
    'hints' => [
        'logo' => 'PNG, JPG oder WebP bis 2 MB, wird auf Rechnungen gedruckt.',
        'first_bank_account' => 'Optional. Ein angegebenes Konto wird das Standardkonto der Firma; weitere Konten fügen Sie in der Firmenansicht hinzu.',
    ],
    'actions' => [
        'create' => 'Firma hinzufügen',
        'back_to_list' => 'Zurück zur Liste',
        'make_default' => 'Als Standard festlegen',
        'remove_logo' => 'Logo entfernen',
    ],
    'messages' => [
        'created' => 'Die Firma wurde angelegt.',
        'updated' => 'Die Firma wurde geändert.',
        'archived' => 'Die Firma wurde archiviert.',
        'restored' => 'Die Firma wurde wiederhergestellt.',
        'default_changed' => 'Die Standardfirma wurde geändert.',
        'default_archived' => 'Eine archivierte Firma kann nicht Standard sein.',
        'default_required' => 'Wählen Sie vor dem Archivieren der Standardfirma eine andere Standardfirma.',
        'new_default_archived' => 'Die gewählte neue Standardfirma ist archiviert.',
        'limit_exceeded' => 'Sie haben die Höchstzahl aktiver Firmen erreicht. Archivieren Sie eine Firma oder erweitern Sie Ihren Tarif.',
        'confirm_archive' => 'Möchten Sie die Firma wirklich archivieren? Für neue Belege steht sie dann nicht zur Verfügung.',
        'choose_new_default' => 'Diese Firma ist die Standardfirma. Wählen Sie, welche der anderen aktiven Firmen Standard wird.',
        'no_logo' => 'Die Firma hat kein Logo.',
    ],
    'sections' => [
        'details' => 'Firmendaten',
        'first_bank_account' => 'Bankkonto',
    ],
    'titles' => [
        'index' => 'Meine Firmen',
        'create' => 'Neue Firma',
        'edit' => 'Firma bearbeiten',
        'empty' => 'Sie haben noch keine aktive Firma',
        'empty_archived' => 'Keine archivierten Firmen',
        'empty_message' => 'Fügen Sie die Firma hinzu, in deren Namen Sie Belege ausstellen.',
        'choose_new_default' => 'Neue Standardfirma',
    ],
];
