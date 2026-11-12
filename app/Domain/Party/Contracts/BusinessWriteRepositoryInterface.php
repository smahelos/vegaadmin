<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use DateTimeImmutable;

interface BusinessWriteRepositoryInterface
{
    /**
     * Stores the business with its address.
     *
     * @throws DuplicateCompanyNumberException
     */
    public function create(WriteScope $scope, BusinessWriteData $data): BusinessDTO;

    /**
     * Replaces the details and the address; an address of null deletes the stored one.
     *
     * @throws DuplicateCompanyNumberException
     */
    public function update(
        BusinessId $id,
        WriteScope $scope,
        BusinessWriteData $data,
    ): BusinessDTO;

    public function archive(
        BusinessId $id,
        WriteScope $scope,
        DateTimeImmutable $archived,
    ): void;

    public function restore(BusinessId $id, WriteScope $scope): void;

    /** The owner's own account setting. */
    public function setDefault(WriteScope $scope, ?BusinessId $id): void;
}
