<?php

return [
    'sections' => [
        'document' => 'Belegdaten',
        'amounts' => 'Beträge, MwSt. und Währung',
        'status' => 'Status und Kategorie',
        'other' => 'Weitere Angaben und Anhänge',
    ],
    'entity' => [
        'singular' => 'Ausgabe',
        'plural' => 'Ausgaben',
    ],
    'fields' => [
        'supplier' => 'Lieferant',
        'customer' => 'Kunde',
        'expense_date' => 'Datum',
        'amount' => 'Betrag',
        'currency' => 'Währung',
        'category_id' => 'Kategorie',
        'payment_method_id' => 'Zahlungsmethode',
        'reference_number' => 'Referenznummer',
        'description' => 'Beschreibung',
        'receipt_file' => 'Beleg',
        'attachments' => 'Anhänge',
        'tax_rate' => 'Steuersatz',
        'tax_included' => 'Steuer ist Teil des Betrags',
        'tax_amount' => 'Steuerbetrag',
        'status_id' => 'Status',
        'user_id' => 'Eigentümer',
    ],
    'hints' => [
        'reference_number' => 'Z.B. Rechnungsnummer, Belegnummer usw.',
        'attachments' => 'Laden Sie Dateien hoch, die mit diesem Aufwand verbunden sind (Bilder, PDFs, Dokumente).',
        'tax_included' => 'Aktivieren Sie diese Option, wenn die Steuer bereits im Gesamtbetrag enthalten ist',
    ],
    'messages' => [
        'created' => 'Ausgabe erstellt.',
        'updated' => 'Ausgabe aktualisiert.',
    ],
    'filters' => [
        'date_range' => 'Datumsbereich',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Ausgabenkategorie',
            'plural' => 'Ausgabenkategorien',
        ],
        'fields' => [
            'name' => 'Name',
            'slug' => 'Kennung',
            'color' => 'Farbe',
            'description' => 'Beschreibung',
            'is_active' => 'Aktiv',
        ],
        'hints' => [
            'slug' => 'Automatisch aus dem Namen generiert, wenn nicht ausgefüllt',
        ],
    ],
];
