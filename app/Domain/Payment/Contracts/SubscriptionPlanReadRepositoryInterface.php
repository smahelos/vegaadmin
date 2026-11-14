<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\SubscriptionPlanDTO;

interface SubscriptionPlanReadRepositoryInterface
{
    public function findById(int $id): ?SubscriptionPlanDTO;
}
