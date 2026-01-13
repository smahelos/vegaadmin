<?php

namespace Tests\Unit\Application\Analytics\Presenters;

use App\Application\Analytics\Presenters\MonthlyStatPresenter;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthlyStatPresenterTest extends TestCase
{
    #[Test]
    public function presents_monthly_stat_with_formatted_total(): void
    {
        $dto = new MonthlyStatDTO('2025-08', Money::fromString('99.99', 'EUR'));

        $formatter = new class implements MoneyFormatterInterface {
            public function format(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
            { return 'f:' . $money->toFloat() . ':' . $money->getCurrency(); }
            public function formatWithCode(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
            { return 'wc:' . $money->toFloat() . ':' . $money->getCurrency() . ':' . ($locale ?? ''); }
        };

        $presenter = new MonthlyStatPresenter($formatter);
        $result = $presenter->present($dto, 'en_US');

        $this->assertSame('2025-08', $result['month']);
        $this->assertSame(99.99, $result['total']);
        $this->assertSame('wc:99.99:EUR:en_US', $result['total_formatted']);
    }
}
