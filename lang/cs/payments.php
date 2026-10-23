<?php

return [
    'fields' => [
        'created_at' => 'Datum',
        'amount' => 'Částka',
        'status' => 'Stav',
        'gateway' => 'Platební brána',
    ],
    'statuses' => [
        'pending' => 'Čeká',
        'processing' => 'Zpracovává se',
        'completed' => 'Dokončeno',
        'failed' => 'Neúspěšné',
        'cancelled' => 'Zrušeno',
        'refunded' => 'Vráceno',
    ],
    'gateways' => [
        'gopay' => 'GoPay',
    ],
    'messages' => [
        'successful' => 'Platba byla úspěšná',
        'failed' => 'Platba se nezdařila',
        'pending' => 'Platba se zpracovává',
        'error' => 'Chyba při zpracování platby',
    ],
];
