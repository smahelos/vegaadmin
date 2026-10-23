<?php

return [
    'fields' => [
        'created_at' => 'Datum',
        'amount' => 'Betrag',
        'status' => 'Status',
        'gateway' => 'Zahlungsgateway',
    ],
    'statuses' => [
        'pending' => 'Ausstehend',
        'processing' => 'Wird verarbeitet',
        'completed' => 'Abgeschlossen',
        'failed' => 'Fehlgeschlagen',
        'cancelled' => 'Storniert',
        'refunded' => 'Erstattet',
        'partially_refunded' => 'Teilweise erstattet',
    ],
    'gateways' => [
        'gopay' => 'GoPay',
    ],
    'messages' => [
        'successful' => 'Zahlung war erfolgreich',
        'failed' => 'Zahlung ist fehlgeschlagen',
        'pending' => 'Zahlung wird verarbeitet',
        'error' => 'Fehler bei der Zahlungsverarbeitung',
    ],
];
