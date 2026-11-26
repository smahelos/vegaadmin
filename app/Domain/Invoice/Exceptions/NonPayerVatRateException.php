<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Exceptions;

use DomainException;

/**
 * A supplier that is not a VAT payer states no VAT, so each of its invoice lines has rate 0.
 */
final class NonPayerVatRateException extends DomainException {}
