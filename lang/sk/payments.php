<?php

return [
    'fields' => [
        'created_at' => 'Dátum',
        'amount' => 'Suma',
        'status' => 'Stav',
        'gateway' => 'Platobná brána',
    ],
    'statuses' => [
        'pending' => 'Čaká',
        'processing' => 'Spracováva sa',
        'completed' => 'Dokončené',
        'failed' => 'Neúspešné',
        'cancelled' => 'Zrušené',
        'refunded' => 'Vrátené',
    ],
    'gateways' => [
        'gopay' => 'GoPay',
    ],
    'messages' => [
        'successful' => 'Platba bola úspešná',
        'failed' => 'Platba sa nepodarila',
        'pending' => 'Platba sa spracováva',
        'error' => 'Chyba pri spracovaní platby',
    ],
];
