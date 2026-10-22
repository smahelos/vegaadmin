<?php

return [
    'entity' => [
        'singular' => 'Status',
        'plural' => 'Status',
    ],
    'fields' => [
        'name' => 'Name',
        'slug' => 'Kennung',
        'color' => 'Farbe',
        'description' => 'Beschreibung',
        'is_active' => 'Aktiv',
        'category_id' => 'Kategorie',
    ],
    'statuses' => [
        'approved' => 'Genehmigt',
        'cancelled' => 'Storniert',
        'cancelled-expense' => 'Stornierte Ausgabe',
        'cancelled-status' => 'Stornierter Status',
        'draft' => 'Entwurf',
        'in-review' => 'In Prüfung',
        'overdue' => 'Überfällig',
        'paid' => 'Bezahlt',
        'paid-expense' => 'Bezahlte Ausgabe',
        'paid-status' => 'Bezahlter Status',
        'partially-paid' => 'Teilweise bezahlt',
        'pending' => 'Ausstehend',
        'pending-payment' => 'Zahlung ausstehend',
        'rejected' => 'Abgelehnt',
        'unpaid' => 'Unbezahlt',
    ],
    'filters' => [
        'category' => 'Nach Kategorie filtern',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Statuskategorie',
            'plural' => 'Statuskategorien',
        ],
        'fields' => [
            'name' => 'Name',
            'slug' => 'Kennung',
            'description' => 'Beschreibung',
            'statuses_count' => 'Anzahl der Status',
            'statuses' => 'Liste der Status',
        ],
        'hints' => [
            'slug' => 'Wird automatisch aus dem Namen generiert, wenn nicht angegeben',
        ],
    ],
    'colors' => [
        'green' => 'Grün',
        'yellow' => 'Gelb',
        'red' => 'Rot',
        'blue' => 'Blau',
        'gray' => 'Grau',
        'purple' => 'Lila',
        'indigo' => 'Indigo',
        'pink' => 'Rosa',
    ],
];
