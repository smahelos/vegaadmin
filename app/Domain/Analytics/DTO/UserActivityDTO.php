<?php

declare(strict_types=1);

namespace App\Domain\Analytics\DTO;

use App\Domain\Analytics\Enums\ActivityLevel;
use DateTimeImmutable;

final readonly class UserActivityDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public int $invoicesLast30Days,
        public int $invoicesLast7Days,
        public ?DateTimeImmutable $lastInvoice,
        public ActivityLevel $level,
    ) {}
}
