<?php

return [
    'system' => [
        'settings' => 'Einstellungen',
    ],

    'invoices' => [
        'invoice' => 'Rechnung',
        'invoices' => 'Rechnungen',
        'due_date' => 'Fälligkeitsdatum',
        'issue_date' => 'Ausstellungsdatum',
        'invoice_vs' => 'Rechnungsnummer/VS',
        'invoice_ks' => 'Konstantsymbol',
        'invoice_ss' => 'Spezifisches Symbol',
        'client' => 'Kunde',
        'client_id' => 'Kunden-ID',
        'status' => 'Status',
        'payment_status' => 'Zahlungsstatus',
        'payment_method' => 'Zahlungsmethode',
        'payment_date' => 'Zahlungsdatum',
        'amount' => 'Betrag',
        'currency' => 'Währung',
        'description' => 'Beschreibung',
        'tax' => 'MwSt.',
        'tax_rate' => 'MwSt.-Satz',
        'tax_amount' => 'MwSt.-Betrag',
        'total' => 'Gesamtbetrag',
    ],

    'clients' => [
        'client' => 'Kunde',
        'clients' => 'Kunden',
        'ico' => 'Handelsregisternummer',
        'dic' => 'Steuernummer',
        'company_name' => 'Firmenname',
        'street' => 'Straße',
        'city' => 'Stadt',
        'zip' => 'PLZ',
        'country' => 'Land',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'description' => 'Beschreibung',
        'status' => [
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'archived' => 'Archiviert',
            'deleted' => 'Gelöscht',
        ],
        'is_default' => 'Standard',
        'user' => 'Benutzer',
    ],

    'suppliers' => [
        'supplier' => 'Lieferant',
        'suppliers' => 'Lieferanten',
        'ico' => 'Handelsregisternummer',
        'dic' => 'Steuernummer',
        'company_name' => 'Firmenname',
        'street' => 'Straße',
        'city' => 'Stadt',
        'zip' => 'PLZ',
        'country' => 'Land',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'description' => 'Beschreibung',

        'status' => [
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'archived' => 'Archiviert',
            'deleted' => 'Gelöscht',
        ],

        'is_default' => 'Standard',
        'user' => 'Benutzer',
        'account_number' => 'Kontonummer',
        'bank_code' => 'Bankleitzahl',
        'iban' => 'IBAN',
        'swift' => 'SWIFT',
        'bank_name' => 'Bankname',
    ],

    'cron_tasks' => [
        'cron_task' => 'Geplante Aufgabe',
        'cron_tasks' => 'Geplante Aufgaben',
        
        'frequency' => [
            'daily' => 'Täglich',
            'weekly' => 'Wöchentlich',
            'monthly' => 'Monatlich',
            'custom' => 'Benutzerdefiniert',
        ],

        'days' => [
            'sunday' => 'Sonntag',
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
        ],

        'fields' => [
            'name' => 'Name',
            'command' => 'Befehl',
            'base_command' => 'Grundbefehl',
            'command_params' => 'Befehlsparameter',
            'frequency' => 'Häufigkeit',
            'custom_expression' => 'Benutzerdefinierter Cron-Ausdruck',
            'run_at' => 'Ausführungszeit',
            'day_of_week' => 'Wochentag',
            'day_of_month' => 'Tag im Monat',
            'is_active' => 'Aktiv',
            'last_run' => 'Letzte Ausführung',
            'last_output' => 'Letzte Ausgabe',
            'description' => 'Beschreibung',
            'next_run' => 'Nächste Ausführung',
        ],

        'tabs' => [
            'basic' => 'Grundeinstellungen',
            'schedule' => 'Zeitplanung',
            'advanced' => 'Erweitert',
            'history' => 'Verlauf',
        ],

        'messages' => [
            'task_executed' => 'Aufgabe wurde erfolgreich ausgeführt',
            'execution_failed' => 'Aufgabenausführung fehlgeschlagen',
        ],

        'hints' => [
            'custom_expression' => 'Wenn Sie benutzerdefinierten Ausdruck wählen, geben Sie einen gültigen Cron-Ausdruck ein.',
            'custom_expression_examples' => 'Beispiele: "*/5 * * * *" (alle 5 Minuten), "0 0 * * *" (täglich um Mitternacht), "0 12 * * 1-5" (werktags um 12 Uhr mittags).',
            'command_params' => 'Geben Sie Parameter für den Befehl ein, z.B. "--days=7 --force", oder "--days-before=0 --days-after=14"',
        ],
        
        'predefined' => [
            'upcoming_invoices' => 'Erinnerungen für bevorstehende Rechnungen',
            'overdue_invoices' => 'Erinnerungen für überfällige Rechnungen',
            'long_overdue_invoices' => 'Erinnerungen für stark überfällige Rechnungen',
        ],
        
        'descriptions' => [
            'upcoming_invoices' => 'Sendet Erinnerungen für Rechnungen, die bald fällig sind (3 Tage vor Fälligkeit)',
            'overdue_invoices' => 'Sendet Erinnerungen für überfällige Rechnungen (bis zu 7 Tage)',
            'long_overdue_invoices' => 'Sendet Erinnerungen für stark überfällige Rechnungen (14 Tage oder mehr)',
        ],

        'validation' => [
            'invalid_cron_expression' => 'Ungültiger CRON-Ausdruck. Verwenden Sie das Format: Minute Stunde Tag Monat Wochentag.',
        ],

        'buttons' => [
            'run_now' => 'Jetzt ausführen',
        ],
    ],

    'artisan_commands' => [
        'command' => 'Artisan-Befehl',
        'commands' => 'Artisan-Befehle',
        'category' => 'Befehlskategorie',
        'categories' => 'Befehlskategorien',
        'uncategorized' => 'Nicht kategorisierte Befehle',
        'uncategorized_description' => 'Automatisch erkannte Befehle ohne Kategorie',
        
        'fields' => [
            'name' => 'Name',
            'command' => 'Befehl',
            'slug' => 'Kennung (Slug)',
            'description' => 'Beschreibung',
            'parameters_description' => 'Parameterbeschreibung',
            'category' => 'Kategorie',
            'commands_count' => 'Anzahl der Befehle',
            'is_active' => 'Aktiv',
            'sort_order' => 'Sortierreihenfolge',
        ],

        'hints' => [
            'slug' => 'Eindeutige Kategoriekennung, wird im Code verwendet (z.B. "cron", "system", "admin")',
            'command' => 'Artisan-Befehl, z.B. "app:update-exchange-rates"',
            'parameters_description' => 'Beschreibung der Befehlsparameter, z.B. "--date=YYYY-MM-DD: Legt das Wechselkursdatum fest"',
            'sort_order' => 'Befehlsreihenfolge in der Liste (kleinere Zahl = höhere Position)',
        ],
    ],

    'statuses' => [
        'status_types' => 'Statustypen',
    ],

    'taxes' => [
        'tax' => 'MwSt.',
        'taxes' => 'MwSt.',
        'name' => 'Name',
        'rate' => 'Satz',
        'description' => 'Beschreibung',
        'slug' => 'Slug',
    ],

    'banks' => [
        'bank' => 'Bank',
        'banks' => 'Banken',
        'name' => 'Name',
        'code' => 'Bankleitzahl',
        'swift' => 'SWIFT',
        'country' => 'Land',
        'description' => 'Beschreibung',
        'is_active' => 'Aktiv',
        'sort_order' => 'Reihenfolge',
    ],

    'payment_methods' => [
        'payment_method' => 'Zahlungsmethode',
        'payment_methods' => 'Zahlungsmethoden',
        'name' => 'Name',
        'description' => 'Beschreibung',
        'slug' => 'Slug',
        'country' => 'Land',
        'currency' => 'Währung',
        'icon' => 'Symbol',
        'is_active' => 'Aktiv',
        'sort_order' => 'Reihenfolge',
    ],

    'products' => [
        'product' => 'Produkt',
        'products' => 'Produkte',
        'supplier' => 'Lieferant',
        'product_category' => 'Produktkategorie',
        'product_categories' => 'Produktkategorien',
        'name' => 'Name',
        'slug' => 'Slug',
        'description' => 'Beschreibung',
        'price' => 'Preis',
        'tax' => 'MwSt.',
        'category' => 'Kategorie',
        'is_default' => 'Standard',
        'user' => 'Benutzer',
        'image' => 'Bild',
        'leave_empty_for_autogeneration' => 'Leer lassen für automatische Generierung',
        'remove_image' => 'Bild entfernen',
        'image_help' => 'Unterstützte Formate: JPG, PNG, GIF (max. 2MB)',
    ],
];
