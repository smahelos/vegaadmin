<?php

declare(strict_types=1);

/*
 * Billex settings that do not change at runtime (D-047).
 */
return [
    // Requests per minute: creation per user on every path, the guest invoice per IP address, the company register
    // lookup per user or per IP address for a guest
    'rate_limits' => [
        'create' => 30,
        'guest_invoice' => 10,
        'company_registry' => 10,
    ],
];
