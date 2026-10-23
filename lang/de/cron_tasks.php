<?php

return [
    'entity' => [
        'singular' => 'Geplante Aufgabe',
        'plural' => 'Geplante Aufgaben',
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
    'hints' => [
        'command_params' => 'Geben Sie Parameter für den Befehl ein, z.B. "--days=7 --force", oder "--days-before=0 --days-after=14"',
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
    ],
    'predefined' => [
        'upcoming_invoices' => [
            'name' => 'Erinnerungen für bevorstehende Rechnungen',
            'description' => 'Sendet Erinnerungen für Rechnungen, die bald fällig sind (3 Tage vor Fälligkeit)',
        ],
        'overdue_invoices' => [
            'name' => 'Erinnerungen für überfällige Rechnungen',
            'description' => 'Sendet Erinnerungen für überfällige Rechnungen (bis zu 7 Tage)',
        ],
        'long_overdue_invoices' => [
            'name' => 'Erinnerungen für stark überfällige Rechnungen',
            'description' => 'Sendet Erinnerungen für stark überfällige Rechnungen (14 Tage oder mehr)',
        ],
    ],
];
