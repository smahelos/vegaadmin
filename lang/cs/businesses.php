<?php

return [
    'entity' => [
        'singular' => 'Firma',
        'plural' => 'Moje firmy',
    ],
    'fields' => [
        'logo' => 'Logo',
        'default' => 'Výchozí',
        'new_default' => 'Nová výchozí firma',
    ],
    'hints' => [
        'logo' => 'PNG, JPG nebo WebP do 2 MB, tiskne se na faktury.',
        'first_bank_account' => 'Nepovinné. Vyplněný účet se stane výchozím účtem firmy, další účty přidáte na detailu firmy.',
    ],
    'actions' => [
        'create' => 'Přidat firmu',
        'back_to_list' => 'Zpět na seznam',
        'make_default' => 'Nastavit jako výchozí',
        'remove_logo' => 'Odstranit logo',
    ],
    'messages' => [
        'created' => 'Firma byla vytvořena.',
        'updated' => 'Firma byla upravena.',
        'archived' => 'Firma byla archivována.',
        'restored' => 'Firma byla obnovena.',
        'default_changed' => 'Výchozí firma byla změněna.',
        'default_archived' => 'Archivovaná firma nemůže být výchozí.',
        'default_required' => 'Před archivací výchozí firmy zvolte jinou výchozí firmu.',
        'new_default_archived' => 'Zvolená nová výchozí firma je archivovaná.',
        'limit_exceeded' => 'Dosáhli jste limitu počtu aktivních firem. Archivujte některou firmu nebo si rozšiřte tarif.',
        'confirm_archive' => 'Opravdu chcete firmu archivovat? Na nových dokladech nebude k dispozici.',
        'choose_new_default' => 'Tato firma je výchozí. Zvolte, která z ostatních aktivních firem bude výchozí.',
        'no_logo' => 'Firma nemá logo.',
    ],
    'sections' => [
        'details' => 'Údaje firmy',
        'first_bank_account' => 'Bankovní účet',
    ],
    'titles' => [
        'index' => 'Moje firmy',
        'create' => 'Nová firma',
        'edit' => 'Upravit firmu',
        'empty' => 'Zatím nemáte žádnou aktivní firmu',
        'empty_archived' => 'Žádné archivované firmy',
        'empty_message' => 'Přidejte firmu, jejímž jménem vystavujete doklady.',
        'choose_new_default' => 'Nová výchozí firma',
    ],
];
