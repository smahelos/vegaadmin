<?php

return [
    'entity' => [
        'singular' => 'Rollenlimit',
        'plural' => 'Rollenlimits',
    ],
    'fields' => [
        'role' => 'Rolle',
        'invoices' => 'Rechnungen pro Monat',
        'expenses' => 'Ausgaben pro Monat',
        'businesses' => 'Firmen',
        'contacts' => 'Kontakte',
        'products' => 'Produkte',
    ],
    'hints' => [
        'role' => 'Nur Web-Rollen, die noch keine Limits haben.',
        'quota' => 'Anzahl der pro Kalendermonat erstellten Einträge, frühestens ab der Rollenzuweisung gezählt.',
        'capacity' => 'Anzahl gleichzeitig aktiver Einträge.',
    ],
    'statuses' => [
        'limited' => 'Anzahl',
        'unlimited' => 'Unbegrenzt',
        'not_allowed' => 'Nicht erlaubt',
    ],
    'usage' => [
        'title' => 'Tarifnutzung',
        'invoices' => 'Rechnungen diesen Monat',
        'expenses' => 'Ausgaben diesen Monat',
        'businesses' => 'Aktive Firmen',
        'contacts' => 'Aktive Kontakte',
        'products' => 'Produkte',
        'approaching' => 'Sie nähern sich dem Limit.',
        'reached' => 'Das Limit ist erreicht.',
        'ambiguous' => 'Der Benutzer hat mehrere Tarifrollen mit Limits, lassen Sie ihm nur eine.',
        'unavailable' => 'Die Nutzung dieses Limits kann derzeit nicht angezeigt werden. Bitte wenden Sie sich an den Support.',
    ],
];
