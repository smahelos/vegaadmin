<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use DateTimeImmutable;

interface BankAccountWriteRepositoryInterface
{
    /**
     * The caller has found the owner within its scope; BusinessCreator, which registration calls outside the gate,
     * has just created it.
     */
    public function create(AccountOwner $owner, BankAccountWriteData $data): BankAccountDTO;

    public function update(
        BankAccountId $id,
        WriteScope $scope,
        BankAccountWriteData $data,
    ): BankAccountDTO;

    public function archive(
        BankAccountId $id,
        WriteScope $scope,
        DateTimeImmutable $archived,
    ): void;

    public function restore(BankAccountId $id, WriteScope $scope): void;

    /**
     * The database keeps the default within the owner's accounts. The caller has found the owner, as for create().
     */
    public function setDefaultForOwner(AccountOwner $owner, ?BankAccountId $id): void;
}
