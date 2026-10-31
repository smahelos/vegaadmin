<?php

return [
    'entity' => [
        'singular' => 'Firma',
        'plural' => 'Moje firmy',
    ],
    'fields' => [
        'logo' => 'Logo',
        'default' => 'Predvolená',
        'new_default' => 'Nová predvolená firma',
    ],
    'hints' => [
        'logo' => 'PNG, JPG alebo WebP do 2 MB, tlačí sa na faktúry.',
        'first_bank_account' => 'Nepovinné. Vyplnený účet sa stane predvoleným účtom firmy, ďalšie účty pridáte na detaile firmy.',
    ],
    'actions' => [
        'create' => 'Pridať firmu',
        'back_to_list' => 'Späť na zoznam',
        'make_default' => 'Nastaviť ako predvolenú',
        'remove_logo' => 'Odstrániť logo',
        'upload_logo' => 'Nahrať logo',
    ],
    'messages' => [
        'created' => 'Firma bola vytvorená.',
        'updated' => 'Firma bola upravená.',
        'archived' => 'Firma bola archivovaná.',
        'restored' => 'Firma bola obnovená.',
        'default_changed' => 'Predvolená firma bola zmenená.',
        'default_archived' => 'Archivovaná firma nemôže byť predvolená.',
        'default_required' => 'Pred archiváciou predvolenej firmy zvoľte inú predvolenú firmu.',
        'new_default_archived' => 'Zvolená nová predvolená firma je archivovaná.',
        'limit_exceeded' => 'Dosiahli ste limit počtu aktívnych firiem. Archivujte niektorú firmu alebo si rozšírte tarif.',
        'confirm_archive' => 'Naozaj chcete firmu archivovať? Na nových dokladoch nebude k dispozícii.',
        'choose_new_default' => 'Táto firma je predvolená. Zvoľte, ktorá z ostatných aktívnych firiem bude predvolená.',
        'no_logo' => 'Firma nemá logo.',
    ],
    'sections' => [
        'first_bank_account' => 'Bankový účet',
    ],
    'titles' => [
        'index' => 'Moje firmy',
        'create' => 'Nová firma',
        'edit' => 'Upraviť firmu',
        'empty' => 'Zatiaľ nemáte žiadnu aktívnu firmu',
        'empty_archived' => 'Žiadne archivované firmy',
        'empty_message' => 'Pridajte firmu, v mene ktorej vystavujete doklady.',
        'choose_new_default' => 'Nová predvolená firma',
    ],
];
