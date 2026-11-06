<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Enums;

/**
 * Layout of the invoice PDF. The value is stored in `invoices.template` and names the PDF view.
 */
enum InvoiceTemplate: string
{
    case DEFAULT = 'default';
    case MODERN = 'modern';
    case MINIMAL = 'minimal';
}
