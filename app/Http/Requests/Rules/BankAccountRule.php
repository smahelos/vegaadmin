<?php

declare(strict_types=1);

namespace App\Http\Requests\Rules;

use App\Domain\Payment\Exceptions\InvalidBankAccountException;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Bank account details that BankAccount accepts: an IBAN or a complete local number, matching when both are given.
 * It checks the whole account, whichever field it is attached to.
 */
final class BankAccountRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $iban,
        private readonly ?string $number,
        private readonly ?string $bankCode,
        private readonly ?Country $country,
        private readonly ?string $swift,
    ) {}

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        try {
            BankAccount::fromParts(
                $this->iban,
                $this->number,
                $this->bankCode,
                $this->country,
                $this->swift,
            );
        } catch (InvalidBankAccountException $e) {
            $fail('parties.bank_accounts.validation.' . $e->problem->value)->translate();
        }
    }
}
