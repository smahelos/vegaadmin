<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\SubscriptionPlanDTO;
use App\Domain\Payment\ValueObjects\SubscriptionPlanId;

interface SubscriptionPlanReadRepositoryInterface
{
    public function findById(SubscriptionPlanId $id): ?SubscriptionPlanDTO;
}
