<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enums;

/**
 * Why bank account details were refused; the value is the last segment of its validation message key.
 */
enum BankAccountProblem: string
{
    case MISSING_DETAILS = 'missing_details';
    case INVALID_IBAN = 'invalid_iban';
    case INVALID_NUMBER = 'invalid_number';
    case INVALID_BANK_CODE = 'invalid_bank_code';
    case LOCAL_NUMBER_NOT_SUPPORTED = 'local_number_not_supported';
    case IBAN_MISMATCH = 'iban_mismatch';
    case INVALID_SWIFT = 'invalid_swift';
}
