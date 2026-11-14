<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTO;

use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Shared\Money\ValueObjects\Money;
use DateTimeImmutable;

final readonly class SubscriptionWriteData
{
    public function __construct(
        public int $planId,
        public SubscriptionStatus $status,
        public Money $amount,
        public ?DateTimeImmutable $starts = null,
        public ?DateTimeImmutable $ends = null,
        public ?DateTimeImmutable $trialEnds = null,
        public ?DateTimeImmutable $nextBilling = null,
        public ?string $metadata = null,
    ) {}
}
