<?php

namespace App\Domain\Invoice\Exceptions;

/**
 * Domain exception thrown when an invoice cannot be found.
 *
 * Used when:
 * - Invoice lookup by ID fails
 * - Invoice authorization fails
 * - Invoice access is denied for user
 */
class InvoiceNotFoundException extends \DomainException {}
