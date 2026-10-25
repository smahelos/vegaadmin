<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\DTO;

use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserStatisticsDTOTest extends TestCase
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
        $this->assertSame([
            'invoice_count',
            'client_count',
            'suppliers_count',
            'total_amount',
        ], array_keys($arr));
        $this->assertSame(0, $arr['invoice_count']);
        $this->assertSame(0.0, $arr['total_amount']);
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
