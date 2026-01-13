<?php

namespace App\Domain\Payment\Contracts;

interface SubscriptionDtoWriteRepositoryInterface
{
    public function activateById(int $subscriptionId, int $durationDays): bool;
}
