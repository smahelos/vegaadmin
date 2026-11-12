<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use DateTimeImmutable;

interface ContactWriteRepositoryInterface
{
    /**
     * Stores the contact with its address.
     *
     * @throws DuplicateCompanyNumberException
     */
    public function create(WriteScope $scope, ContactWriteData $data): ContactDTO;

    /**
     * Replaces the details and the address; an address of null deletes the stored one.
     *
     * @throws DuplicateCompanyNumberException
     */
    public function update(
        ContactId $id,
        WriteScope $scope,
        ContactWriteData $data,
    ): ContactDTO;

    public function archive(
        ContactId $id,
        WriteScope $scope,
        DateTimeImmutable $archived,
    ): void;

    public function restore(ContactId $id, WriteScope $scope): void;
}
