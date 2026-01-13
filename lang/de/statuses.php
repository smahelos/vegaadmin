<?php

return [
    // Existing status labels
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

    // Fields & validation used in StatusRequest tests
    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'color' => 'Farbe',
        'description' => 'Beschreibung',
        'is_active' => 'Aktiv',
        'category' => 'Kategorie',
    ],

    'validation' => [
        'name_required' => 'Das Namensfeld ist erforderlich.',
        'slug_required' => 'Das Slug-Feld ist erforderlich.',
        'slug_unique' => 'Der Slug ist bereits vergeben.',
    ],
];
