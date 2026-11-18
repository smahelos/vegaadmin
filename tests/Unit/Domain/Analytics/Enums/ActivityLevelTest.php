<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\Enums;

use App\Domain\Analytics\Enums\ActivityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActivityLevelTest extends TestCase
{
    /**
     * @return array<string, array{int, ActivityLevel}>
     */
    public static function invoices(): array
    {
        return [
            'none' => [0, ActivityLevel::INACTIVE],
            'one' => [1, ActivityLevel::LOW],
            'four' => [4, ActivityLevel::LOW],
            'five' => [5, ActivityLevel::MEDIUM],
            'nineteen' => [19, ActivityLevel::MEDIUM],
            'twenty' => [20, ActivityLevel::HIGH],
        ];
    }

    #[Test]
    #[DataProvider('invoices')]
    public function the_level_follows_the_invoices_of_the_last_30_days(int $invoices, ActivityLevel $expected): void
    {
        $this->assertSame(
            $expected,
            ActivityLevel::fromInvoices($invoices),
        );
    }
}
