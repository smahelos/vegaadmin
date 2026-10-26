<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

interface ContactWriteRepositoryInterface
{
    /**
     * Stores the contact with its address.
     *
     * @throws DuplicateCompanyNumberException
     */
    public function create(UserId $userId, ContactWriteData $data): ContactDTO;

    /**
     * Replaces the details and the address; an address of null deletes the stored one.
     *
     * @throws DuplicateCompanyNumberException
     */
    public function update(ContactId $id, ContactWriteData $data): ContactDTO;

    public function archive(ContactId $id, DateTimeImmutable $archived): void;

    public function restore(ContactId $id): void;
}
