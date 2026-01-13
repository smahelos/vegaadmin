<?php

namespace Tests\Unit\Domain\Analytics\DTO;

use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserStatisticsDTOTest extends TestCase
{
    #[Test]
    public function from_array_maps_fields_correctly(): void
    {
        $raw = [
            'invoice_count' => 5,
            'client_count' => 2,
            'suppliers_count' => 1,
            'total_amount' => '123.456', // will be normalized to 123.46
        ];
        $dto = UserStatisticsDTO::fromArray($raw, 'CZK');
        $this->assertSame(5, $dto->invoiceCount);
        $this->assertSame(2, $dto->clientCount);
        $this->assertSame(1, $dto->suppliersCount);
        $this->assertInstanceOf(Money::class, $dto->totalAmount);
        $this->assertSame(123.46, $dto->totalAmount->toFloat());
    }

    #[Test]
    public function to_array_exports_expected_shape(): void
    {
        $dto = UserStatisticsDTO::fromArray([
            'invoice_count' => 0,
            'client_count' => 0,
            'suppliers_count' => 0,
            'total_amount' => 0,
        ], 'CZK');
        $arr = $dto->toArray();
        $this->assertSame(['invoice_count','client_count','suppliers_count','total_amount'], array_keys($arr));
        $this->assertSame(0, $arr['invoice_count']);
        $this->assertSame(0.0, $arr['total_amount']);
    }

    #[Test]
    public function to_formatted_array_uses_formatter(): void
    {
        $dto = UserStatisticsDTO::fromArray([
            'invoice_count' => 1,
            'client_count' => 1,
            'suppliers_count' => 0,
            'total_amount' => 10,
        ], 'CZK');
        $fakeFormatter = new class implements MoneyFormatterInterface {
            public function format(\App\Domain\Shared\Money\ValueObjects\Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string { return 'X'; }
            public function formatWithCode(\App\Domain\Shared\Money\ValueObjects\Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string { return '10.00 CZK'; }
        };
        $formatted = $dto->toFormattedArray($fakeFormatter);
        $this->assertSame(10.0, $formatted['total_amount']);
        $this->assertSame('10.00 CZK', $formatted['total_amount_formatted']);
    }

    #[Test]
    public function missing_key_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserStatisticsDTO::fromArray([
            // 'invoice_count' missing
            'client_count' => 1,
            'suppliers_count' => 0,
            'total_amount' => 5,
        ]);
    }

    #[Test]
    public function non_numeric_total_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserStatisticsDTO::fromArray([
            'invoice_count' => 1,
            'client_count' => 1,
            'suppliers_count' => 0,
            'total_amount' => 'abc',
        ]);
    }
}
