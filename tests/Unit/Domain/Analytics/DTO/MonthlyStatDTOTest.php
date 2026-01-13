<?php

namespace Tests\Unit\Domain\Analytics\DTO;

use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthlyStatDTOTest extends TestCase
{
    #[Test]
    public function from_array_maps_and_normalizes_total(): void
    {
        $row = ['month' => '2025-08', 'total' => '45.678'];
        $dto = MonthlyStatDTO::fromArray($row, 'CZK');
        $this->assertSame('2025-08', $dto->month);
        $this->assertInstanceOf(Money::class, $dto->total);
        $this->assertSame(45.68, $dto->total->toFloat());
    }

    #[Test]
    public function to_array_exports_expected_shape(): void
    {
        $dto = MonthlyStatDTO::fromArray(['month' => '2025-01', 'total' => 0], 'CZK');
        $arr = $dto->toArray();
        $this->assertSame(['month','total'], array_keys($arr));
        $this->assertSame('2025-01', $arr['month']);
        $this->assertSame(0.0, $arr['total']);
    }

    #[Test]
    public function to_formatted_array_uses_formatter(): void
    {
        $dto = MonthlyStatDTO::fromArray(['month' => '2025-09', 'total' => 5], 'CZK');
        $fakeFormatter = new class implements MoneyFormatterInterface {
            public function format(\App\Domain\Shared\Money\ValueObjects\Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string { return 'X'; }
            public function formatWithCode(\App\Domain\Shared\Money\ValueObjects\Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string { return '5.00 CZK'; }
        };
        $formatted = $dto->toFormattedArray($fakeFormatter);
        $this->assertSame('2025-09', $formatted['month']);
        $this->assertSame(5.0, $formatted['total']);
        $this->assertSame('5.00 CZK', $formatted['total_formatted']);
    }

    #[Test]
    public function invalid_month_format_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MonthlyStatDTO::fromArray(['month' => '2025/09', 'total' => 1]);
    }

    #[Test]
    public function non_numeric_total_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MonthlyStatDTO::fromArray(['month' => '2025-09', 'total' => 'x']);
    }
}
