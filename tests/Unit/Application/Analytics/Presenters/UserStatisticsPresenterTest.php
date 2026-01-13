<?php

namespace Tests\Unit\Application\Analytics\Presenters;

use App\Application\Analytics\Presenters\UserStatisticsPresenter;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserStatisticsPresenterTest extends TestCase
{
    #[Test]
    public function presents_user_statistics_with_formatted_amount(): void
    {
        $dto = new UserStatisticsDTO(10, 5, 2, Money::fromString('1234.50', 'CZK'));

        $formatter = new class implements MoneyFormatterInterface {
            public function format(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
            { return 'formatted:' . $money->toFloat() . ':' . $money->getCurrency(); }
            public function formatWithCode(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
            { return 'withcode:' . $money->toFloat() . ':' . $money->getCurrency() . ':' . ($locale ?? ''); }
        };

        $presenter = new UserStatisticsPresenter($formatter);
        $result = $presenter->present($dto, 'cs_CZ');

        $this->assertSame(10, $result['invoice_count']);
        $this->assertSame(5, $result['client_count']);
        $this->assertSame(2, $result['suppliers_count']);
        $this->assertSame(1234.5, $result['total_amount']);
        $this->assertSame('withcode:1234.5:CZK:cs_CZ', $result['total_amount_formatted']);
    }
}
