<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use DateTimeImmutable;

interface BankAccountWriteRepositoryInterface
{
    public function create(AccountOwner $owner, BankAccountWriteData $data): BankAccountDTO;

    public function update(BankAccountId $id, BankAccountWriteData $data): BankAccountDTO;

    public function archive(BankAccountId $id, DateTimeImmutable $archived): void;

    public function restore(BankAccountId $id): void;

    /**
     * The database keeps the default within the owner's accounts.
     */
    public function setDefaultForOwner(AccountOwner $owner, ?BankAccountId $id): void;
}
