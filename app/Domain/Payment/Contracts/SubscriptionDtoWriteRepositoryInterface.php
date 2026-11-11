<?php

namespace App\Domain\Payment\Contracts;

use DateTimeImmutable;

interface SubscriptionDtoWriteRepositoryInterface
{
    public function activateById(
        int $subscriptionId,
        DateTimeImmutable $starts,
        DateTimeImmutable $ends,
    ): bool;
}
