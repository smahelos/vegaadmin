<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Payment\ValueObjects\BankAccount;
use DateTimeImmutable;

final readonly class BankAccountDTO
{
    public function __construct(
        public BankAccountId $id,
        public AccountOwner $owner,
        public ?string $label,
        public BankAccount $account,
        public ?string $bankName,
        public ?DateTimeImmutable $archived,
    ) {}

    public function isArchived(): bool
    {
        return $this->archived !== null;
    }
}
