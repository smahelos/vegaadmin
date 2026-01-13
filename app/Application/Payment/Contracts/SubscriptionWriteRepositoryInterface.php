<?php

namespace App\Application\Payment\Contracts;

interface SubscriptionWriteRepositoryInterface
{
    public function activateById(int $subscriptionId, int $durationDays): bool;
}
