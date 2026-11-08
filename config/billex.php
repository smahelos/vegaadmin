<?php

declare(strict_types=1);

/*
 * Billex settings that do not change at runtime (D-047).
 */
return [
    // Requests per minute: creation per user on every path, the guest invoice per IP address
    'rate_limits' => [
        'create' => 30,
        'guest_invoice' => 10,
    ],
];
