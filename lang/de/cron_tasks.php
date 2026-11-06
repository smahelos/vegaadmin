<?php

return [
    'entity' => [
        'singular' => 'Geplante Aufgabe',
        'plural' => 'Geplante Aufgaben',
    ],
    'fields' => [
        'name' => 'Name',
        'command' => 'Befehl',
        'parameters' => 'Befehlsparameter',
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
    'hints' => [
        'parameters' => 'Durch Leerzeichen getrennte Befehlsoptionen, z.B. "--days-before=5 --days-after=2" oder "--analyze". Ein Wert darf nur Buchstaben, Ziffern und die Zeichen _ . , : - enthalten',
        'custom_expression' => 'Wenn Sie benutzerdefinierten Ausdruck wählen, geben Sie einen gültigen Cron-Ausdruck ein. Beispiele: "*/5 * * * *" (alle 5 Minuten), "0 0 * * *" (täglich um Mitternacht), "0 12 * * 1-5" (werktags um 12 Uhr mittags).',
    ],
    'tabs' => [
        'basic' => 'Grundeinstellungen',
        'schedule' => 'Zeitplanung',
        'history' => 'Verlauf',
    ],
    'frequencies' => [
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
    'actions' => [
        'run_now' => 'Jetzt ausführen',
    ],
    'messages' => [
        'task_executed' => 'Aufgabe wurde erfolgreich ausgeführt',
        'execution_failed' => 'Aufgabenausführung fehlgeschlagen',
    ],
    'validation' => [
        'cron_expression' => 'Ungültiger CRON-Ausdruck. Verwenden Sie das Format: Minute Stunde Tag Monat Wochentag.',
        'parameters' => 'Die Befehlsparameter müssen durch Leerzeichen getrennte Optionen der Form --name oder --name=wert sein; ein Wert darf nur Buchstaben, Ziffern und die Zeichen _ . , : - enthalten',
    ],
    'predefined' => [
        'payment_reminders' => [
            'name' => 'Rechnungserinnerungen',
            'description' => 'Sendet Erinnerungen für Rechnungen 3 Tage vor Fälligkeit, am Fälligkeitstag und einen Tag nach Fälligkeit',
        ],
    ],
];
