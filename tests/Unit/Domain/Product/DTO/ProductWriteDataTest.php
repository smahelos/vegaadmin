<?php

namespace Tests\Unit\Domain\Product\DTO;

use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductWriteDataTest extends TestCase
{
    #[Test]
    public function accepts_money_vo(): void
    {
        $money = Money::fromString('199.90', 'CZK');
        $data = ProductWriteData::fromArray(['price' => $money]);
        $this->assertSame('199.90', $data->price);
        $this->assertSame('CZK', $data->currency);
    }

    #[Test]
    public function accepts_price_money_array(): void
    {
        $data = ProductWriteData::fromArray(['price_money' => ['amount' => '10.5', 'currency' => 'czk']]);
        $this->assertSame('10.50', $data->price);
        $this->assertSame('CZK', $data->currency);
    }

    #[Test]
    public function accepts_amount_plus_currency(): void
    {
        $data = ProductWriteData::fromArray(['amount' => '33,3', 'currency' => 'eur']);
        $this->assertSame('33.30', $data->price);
        $this->assertSame('EUR', $data->currency);
    }

    #[Test]
    public function accepts_legacy_price_and_currency(): void
    {
        $data = ProductWriteData::fromArray(['price' => 5, 'currency' => 'USD']);
        $this->assertSame('5.00', $data->price);
        $this->assertSame('USD', $data->currency);
    }

    #[Test]
    public function price_without_currency_keeps_currency_null(): void
    {
        $data = ProductWriteData::fromArray(['price' => '7.00']);
        $this->assertSame('7.00', $data->price);
        $this->assertNull($data->currency);
    }
}
