<?php

return [
    'entity' => [
        'singular' => 'Kontakt',
        'plural' => 'Kontakty',
    ],
    'fields' => [
        'contact_person' => 'Kontaktní osoba',
    ],
    'hints' => [
        'contact_person' => 'Osoba, se kterou jednáte; nenahrazuje název fakturované firmy.',
    ],
    'actions' => [
        'create' => 'Přidat kontakt',
        'back_to_list' => 'Zpět na seznam',
    ],
    'messages' => [
        'created' => 'Kontakt byl vytvořen.',
        'updated' => 'Kontakt byl upraven.',
        'archived' => 'Kontakt byl archivován.',
        'restored' => 'Kontakt byl obnoven.',
        'limit_exceeded' => 'Dosáhli jste limitu počtu aktivních kontaktů. Archivujte některý kontakt nebo si rozšiřte tarif.',
        'confirm_archive' => 'Opravdu chcete kontakt archivovat? Na nových dokladech nebude k dispozici.',
    ],
    'sections' => [
        'details' => 'Údaje kontaktu',
    ],
    'titles' => [
        'index' => 'Kontakty',
        'create' => 'Nový kontakt',
        'edit' => 'Upravit kontakt',
        'empty' => 'Zatím nemáte žádné aktivní kontakty',
        'empty_archived' => 'Žádné archivované kontakty',
        'empty_message' => 'Přidejte firmy a lidi, se kterými obchodujete.',
    ],
];
