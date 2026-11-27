<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exceptions;

use DomainException;

/**
 * An issued invoice never changes except its payment status, and is never deleted (D-050, point 3).
 */
final class InvoiceIssuedException extends DomainException {}
