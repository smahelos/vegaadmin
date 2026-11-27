<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exceptions;

use DomainException;

/**
 * The stored PDF of an issued document is missing or its hash does not match; it is never rendered again
 * (D-050, point 8).
 */
final class InvoicePdfDamagedException extends DomainException {}
