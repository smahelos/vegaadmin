<?php

return [
    'entity' => [
        'singular' => 'Limit role',
        'plural' => 'Limity rolí',
    ],
    'fields' => [
        'role' => 'Role',
        'invoices' => 'Faktury za měsíc',
        'expenses' => 'Výdaje za měsíc',
        'businesses' => 'Firmy',
        'contacts' => 'Kontakty',
        'products' => 'Produkty',
    ],
    'hints' => [
        'role' => 'Jen role webu, které ještě nemají limity.',
        'quota' => 'Počet vytvořených za kalendářní měsíc, nejdřív od přidělení role.',
        'capacity' => 'Počet aktivních záznamů najednou.',
    ],
    'statuses' => [
        'limited' => 'Počet',
        'unlimited' => 'Neomezeně',
        'not_allowed' => 'Nepovoleno',
    ],
    'usage' => [
        'title' => 'Využití tarifu',
        'invoices' => 'Faktury tento měsíc',
        'expenses' => 'Výdaje tento měsíc',
        'businesses' => 'Aktivní firmy',
        'contacts' => 'Aktivní kontakty',
        'products' => 'Produkty',
        'approaching' => 'Blížíte se limitu.',
        'reached' => 'Limit je vyčerpán.',
        'ambiguous' => 'Uživatel má víc tarifních rolí s limity, ponechte mu jen jednu.',
    ],
];
