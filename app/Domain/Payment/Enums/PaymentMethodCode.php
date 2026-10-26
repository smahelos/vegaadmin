<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enums;

/**
 * How an invoice is paid. The value is the slug of a payment_methods row and the
 * translation key under payment_methods.methods.
 */
enum PaymentMethodCode: string
{
    case BANK_ACCOUNT = 'bankaccount';
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case OTHER = 'other';
}
