<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exceptions;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use DomainException;
use Throwable;

/**
 * Another document of the owner has the number (D-050, point 9).
 */
final class InvoiceNumberTakenException extends DomainException
{
    /**
     * @param  InvoiceNumber|null  $suggestion  The next free number of the series, offered instead
     */
    public function __construct(
        public readonly ?InvoiceNumber $suggestion = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            'The invoice number is used by another document of the owner',
            0,
            $previous,
        );
    }
}
