<?php

return [
    'entity' => [
        'singular' => 'Kontakt',
        'plural' => 'Kontakty',
    ],
    'fields' => [
        'contact_person' => 'Kontaktná osoba',
    ],
    'hints' => [
        'contact_person' => 'Osoba, s ktorou jednáte; nenahrádza názov fakturovanej firmy.',
    ],
    'actions' => [
        'create' => 'Pridať kontakt',
        'back_to_list' => 'Späť na zoznam',
    ],
    'messages' => [
        'created' => 'Kontakt bol vytvorený.',
        'updated' => 'Kontakt bol upravený.',
        'archived' => 'Kontakt bol archivovaný.',
        'restored' => 'Kontakt bol obnovený.',
        'limit_exceeded' => 'Dosiahli ste limit počtu aktívnych kontaktov. Archivujte niektorý kontakt alebo si rozšírte tarif.',
        'confirm_archive' => 'Naozaj chcete kontakt archivovať? Na nových dokladoch nebude k dispozícii.',
    ],
    'sections' => [
        'details' => 'Údaje kontaktu',
    ],
    'titles' => [
        'index' => 'Kontakty',
        'create' => 'Nový kontakt',
        'edit' => 'Upraviť kontakt',
        'empty' => 'Zatiaľ nemáte žiadne aktívne kontakty',
        'empty_archived' => 'Žiadne archivované kontakty',
        'empty_message' => 'Pridajte firmy a ľudí, s ktorými obchodujete.',
    ],
];
