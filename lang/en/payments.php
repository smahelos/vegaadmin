<?php

return [
    'fields' => [
        'created_at' => 'Date',
        'amount' => 'Amount',
        'status' => 'Status',
        'gateway' => 'Payment gateway',
    ],
    'statuses' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],
    'gateways' => [
        'gopay' => 'GoPay',
    ],
    'messages' => [
        'successful' => 'Payment was successful',
        'failed' => 'Payment failed',
        'pending' => 'Payment is being processed',
        'error' => 'Payment processing error',
    ],
];
