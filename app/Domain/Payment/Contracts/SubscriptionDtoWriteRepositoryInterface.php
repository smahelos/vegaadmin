<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\DTO\SubscriptionWriteData;
use App\Domain\Payment\Exceptions\SubscriptionInUseException;
use App\Domain\Payment\Exceptions\SubscriptionNotFoundException;
use App\Domain\Payment\ValueObjects\SubscriptionId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use DateTimeImmutable;

interface SubscriptionDtoWriteRepositoryInterface
{
    public function activateById(
        int $subscriptionId,
        DateTimeImmutable $starts,
        DateTimeImmutable $ends,
    ): bool;

    public function create(WriteScope $scope, SubscriptionWriteData $data): SubscriptionDTO;

    /**
     * @throws SubscriptionNotFoundException
     */
    public function update(
        SubscriptionId $id,
        WriteScope $scope,
        SubscriptionWriteData $data,
    ): SubscriptionDTO;

    /**
     * @throws SubscriptionNotFoundException
     */
    public function cancel(SubscriptionId $id, WriteScope $scope): void;

    /**
     * @throws SubscriptionInUseException
     * @throws SubscriptionNotFoundException
     */
    public function delete(SubscriptionId $id, WriteScope $scope): void;
}
