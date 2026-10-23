<?php

return [
    'entity' => [
        'singular' => 'Entitätslimit',
        'plural' => 'Entitätslimits',
    ],
    'fields' => [
        'permission_name' => 'Berechtigung',
        'entity_type' => 'Entitätstyp',
        'metric_type' => 'Limittyp',
        'period_type' => 'Zeitraum',
        'limit_value' => 'Limitwert',
        'description' => 'Beschreibung',
        'is_active' => 'Aktiv',
    ],
    'hints' => [
        'permission_name' => 'Die Berechtigung, für die das Limit gilt, oder Anonyme Benutzer für nicht angemeldete Besucher.',
        'limit_value' => 'Der höchste erlaubte Wert pro Zeitraum: Anzahl der Datensätze bei Anzahl, Geldbetrag bei Wert, Bytes bei Größe.',
        'description' => 'Optionale Beschreibung des Limits.',
        'is_active' => 'Ob das Limit derzeit angewendet wird.',
    ],
    'labels' => [
        'anonymous_users' => 'Anonyme Benutzer',
    ],
    'entity_types' => [
        'invoice' => [
            'singular' => 'Rechnung',
            'plural' => 'Rechnungen',
        ],
        'client' => [
            'singular' => 'Kunde',
            'plural' => 'Kunden',
        ],
        'supplier' => [
            'singular' => 'Lieferant',
            'plural' => 'Lieferanten',
        ],
        'product' => [
            'singular' => 'Produkt',
            'plural' => 'Produkte',
        ],
        'expense' => [
            'singular' => 'Ausgabe',
            'plural' => 'Ausgaben',
        ],
        'user' => [
            'singular' => 'Benutzer',
            'plural' => 'Benutzer',
        ],
        'page' => [
            'singular' => 'Seite',
            'plural' => 'Seiten',
        ],
    ],
    'metric_types' => [
        'count' => 'Anzahl',
        'value' => 'Geldwert',
        'size' => 'Dateigröße',
    ],
    'periods' => [
        'daily' => 'Täglich',
        'weekly' => 'Wöchentlich',
        'monthly' => 'Monatlich',
        'yearly' => 'Jährlich',
        'lifetime' => 'Gesamt',
    ],
    'statuses' => [
        'success' => 'Gut',
        'warning' => 'Nahe dem Limit',
        'danger' => 'Limit überschritten',
        'not_allowed' => 'Erstellen nicht erlaubt',
    ],
    'widget' => [
        'title' => 'Entity-Limits',
        'usage' => 'Nutzung',
        'unlimited' => 'Unbegrenzt',
        'show_details' => 'Details anzeigen',
        'limit_exceeded' => 'Limit überschritten',
        'approaching_limit' => 'Nähert sich dem Limit',
        'loading' => 'Wird geladen...',
    ],
    'modal' => [
        'title' => 'Entity-Limits Details',
        'loading' => 'Wird geladen...',
        'overview' => 'Überblick',
        'current_period' => 'Aktueller Zeitraum',
        'recent_activity' => 'Letzte Aktivität',
        'error' => 'Fehler beim Laden der Daten',
        'retry' => 'Erneut versuchen',
        'refresh' => 'Aktualisieren',
        'close' => 'Schließen',
        'current_usage' => 'Aktuelle Nutzung',
        'limit_info' => 'Limit-Informationen',
        'usage_percentage' => 'Nutzungsprozentsatz',
        'today' => 'Heute',
        'this_week' => 'Diese Woche',
        'this_month' => 'Dieser Monat',
        'total' => 'Gesamt',
        'no_activity' => 'Keine aktuelle Aktivität',
        'entity_created' => 'Entity erstellt',
    ],
    'usages' => [
        'entity' => [
            'singular' => 'Limitnutzung',
            'plural' => 'Limitnutzungen',
        ],
        'fields' => [
            'user_id' => 'Benutzer',
            'entity_type' => 'Entitätstyp',
            'metric_type' => 'Limittyp',
            'period_type' => 'Zeitraum',
            'period_start' => 'Zeitraumbeginn',
            'period_end' => 'Zeitraumende',
            'current_value' => 'Aktueller Wert',
            'last_reset_at' => 'Letzte Zurücksetzung',
            'created_at' => 'Erstellt',
            'updated_at' => 'Aktualisiert',
        ],
        'filters' => [
            'period_range' => 'Zeitraum von–bis',
        ],
        'labels' => [
            'anonymous' => 'Anonym',
            'unknown_user' => 'Unbekannter Benutzer (ID: :id)',
        ],
    ],
];
