<?php

return [
    'payment_successful' => 'Zahlung war erfolgreich',
    'payment_failed' => 'Zahlung ist fehlgeschlagen',
    'payment_pending' => 'Zahlung wird verarbeitet',
    'payment_error' => 'Fehler bei der Zahlungsverarbeitung',
    'processing_failed' => 'Zahlungsverarbeitung ist fehlgeschlagen',
    'gateway_not_available' => 'Zahlungsgateway ist nicht verfügbar',
    'gateway_not_configured' => 'Zahlungsgateway ist nicht richtig konfiguriert',
    
    'status' => [
        'pending' => 'Ausstehend',
        'processing' => 'Wird verarbeitet',
        'completed' => 'Abgeschlossen',
        'failed' => 'Fehlgeschlagen',
        'cancelled' => 'Storniert',
        'refunded' => 'Erstattet',
    ],
    
    'methods' => [
        'card' => 'Kreditkarte',
        'bank_transfer' => 'Banküberweisung',
        'paypal' => 'PayPal',
        'gopay' => 'GoPay',
    ],
    'gateways' => [
        'gopay' => 'GoPay',
    ],
];
