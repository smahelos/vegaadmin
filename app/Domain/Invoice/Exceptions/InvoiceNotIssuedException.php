<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exceptions;

use DomainException;

/**
 * A draft is not a tax document yet, so it has no payment status to change.
 */
final class InvoiceNotIssuedException extends DomainException {}
