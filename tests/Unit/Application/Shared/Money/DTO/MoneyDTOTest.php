<?php

namespace Tests\Unit\Application\Shared\Money\DTO;

use App\Application\Shared\Money\DTO\MoneyDTO;
use App\Domain\Shared\Money\Enum\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoneyDTOTest extends TestCase
{
    #[Test]
    public function it_exports_array_structure(): void
    {
        $money = Money::fromString('123.45', 'EUR');
        $dto = MoneyDTO::fromMoney($money);
        $arr = $dto->toArray();
        $this->assertSame('123.45', $arr['amount']);
        $this->assertSame('EUR', $arr['currency']);
    }

    #[Test]
    public function it_can_be_created_from_float_and_currency_enum(): void
    {
        $dto = MoneyDTO::fromFloat(10.5, Currency::CZK);
        $arr = $dto->toArray();
        $this->assertSame('10.50', $arr['amount']);
        $this->assertSame('CZK', $arr['currency']);
    }
}
