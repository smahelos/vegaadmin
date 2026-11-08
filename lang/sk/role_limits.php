<?php

return [
    'entity' => [
        'singular' => 'Limit roly',
        'plural' => 'Limity rolí',
    ],
    'fields' => [
        'role' => 'Rola',
        'invoices' => 'Faktúry za mesiac',
        'expenses' => 'Výdavky za mesiac',
        'businesses' => 'Firmy',
        'contacts' => 'Kontakty',
        'products' => 'Produkty',
    ],
    'hints' => [
        'role' => 'Len roly webu, ktoré ešte nemajú limity.',
        'quota' => 'Počet vytvorených za kalendárny mesiac, najskôr od pridelenia roly.',
        'capacity' => 'Počet aktívnych záznamov naraz.',
    ],
    'statuses' => [
        'limited' => 'Počet',
        'unlimited' => 'Neobmedzene',
        'not_allowed' => 'Nepovolené',
    ],
    'usage' => [
        'title' => 'Využitie tarifu',
        'invoices' => 'Faktúry tento mesiac',
        'expenses' => 'Výdavky tento mesiac',
        'businesses' => 'Aktívne firmy',
        'contacts' => 'Aktívne kontakty',
        'products' => 'Produkty',
        'approaching' => 'Blížite sa k limitu.',
        'reached' => 'Limit je vyčerpaný.',
        'ambiguous' => 'Používateľ má viac tarifných rolí s limitmi, ponechajte mu len jednu.',
        'unavailable' => 'Využitie tohto limitu teraz nemožno zobraziť. Kontaktujte, prosím, podporu.',
    ],
];
