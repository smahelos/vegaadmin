<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exceptions;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use DomainException;

/**
 * The year of the issue date selects the series (D-050, point 10), so the number must be of that year.
 */
final class InvoiceNumberYearException extends DomainException
{
    /**
     * @param  InvoiceNumber  $suggestion  The next free number of the issue date's series
     */
    public function __construct(public readonly InvoiceNumber $suggestion)
    {
        parent::__construct('The invoice number is not of the series of the issue date');
    }
}
