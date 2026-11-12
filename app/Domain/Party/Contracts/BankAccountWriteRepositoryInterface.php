<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use DateTimeImmutable;

interface BankAccountWriteRepositoryInterface
{
    /**
     * @throws BusinessNotFoundException
     * @throws ContactNotFoundException when the account's owner is missing or outside the scope
     */
    public function create(
        WriteScope $scope,
        AccountOwner $owner,
        BankAccountWriteData $data,
    ): BankAccountDTO;

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
     * The database keeps the default within the owner's accounts; an owner outside the scope is left unchanged.
     */
    public function setDefaultForOwner(
        WriteScope $scope,
        AccountOwner $owner,
        ?BankAccountId $id,
    ): void;
}
