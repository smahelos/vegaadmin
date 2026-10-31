<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Payment\ValueObjects\BankAccount;

/**
 * The payment account as a document stores it, a copy like its parties; the label is internal and never copied.
 */
final readonly class DocumentAccountDTO
{
    public function __construct(
        public BankAccount $account,
        public ?string $bankName,
    ) {}

    public static function fromBankAccount(BankAccountDTO $account): self
    {
        return new self($account->account, $account->bankName);
    }
}
