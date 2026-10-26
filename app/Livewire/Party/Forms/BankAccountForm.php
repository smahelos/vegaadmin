<?php

declare(strict_types=1);

namespace App\Livewire\Party\Forms;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Http\Requests\Party\BankAccountInput;
use Illuminate\Support\Str;
use Livewire\Form;

/**
 * The fields of one bank account of a business or contact.
 */
final class BankAccountForm extends Form
{
    public string $label = '';

    public string $iban = '';

    public string $number = '';

    public string $bankCode = '';

    public string $country = 'CZ';

    public string $swift = '';

    public string $bankName = '';

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return BankAccountInput::rules($this->input(), Str::camel(...));
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return BankAccountInput::attributes(Str::camel(...));
    }

    public function isFilled(): bool
    {
        return BankAccountInput::isFilled($this->input());
    }

    /**
     * Call after validate().
     */
    public function writeData(): BankAccountWriteData
    {
        return BankAccountInput::writeData($this->input());
    }

    public function fillFromAccount(BankAccountDTO $account): void
    {
        foreach (BankAccountInput::fromAccount($account) as $field => $value) {
            $this->{Str::camel($field)} = $value;
        }
    }

    /**
     * @return array<string, string>
     */
    public function input(): array
    {
        $input = [];
        foreach (BankAccountInput::FIELDS as $field) {
            $input[$field] = $this->{Str::camel($field)};
        }

        return $input;
    }
}
