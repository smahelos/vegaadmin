<?php

return [
    'payment_successful' => 'Platba byla úspěšná',
    'payment_failed' => 'Platba se nezdařila',
    'payment_pending' => 'Platba se zpracovává',
    'payment_error' => 'Chyba při zpracování platby',
    'processing_failed' => 'Zpracování platby se nezdařilo',
    'gateway_not_available' => 'Platební brána není dostupná',
    'gateway_not_configured' => 'Platební brána není správně nakonfigurována',
    
    'status' => [
        'pending' => 'Čeká',
        'processing' => 'Zpracovává se',
        'completed' => 'Dokončeno',
        'failed' => 'Neúspěšné',
        'cancelled' => 'Zrušeno',
        'refunded' => 'Vráceno',
    ],
    
    'methods' => [
        'card' => 'Platební karta',
        'bank_transfer' => 'Bankovní převod',
        'paypal' => 'PayPal',
        'gopay' => 'GoPay',
    ],
    'gateways' => [
        'gopay' => 'GoPay',
    ],
];
