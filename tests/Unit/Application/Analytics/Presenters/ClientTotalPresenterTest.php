<?php

namespace Tests\Unit\Application\Analytics\Presenters;

use App\Application\Analytics\Presenters\ClientTotalPresenter;
use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientTotalPresenterTest extends TestCase
{
    #[Test]
    public function presents_client_total_with_formatted_amount(): void
    {
        $dto = new ClientTotalDTO(7, 'ACME', Money::fromString('1500.00', 'USD'));

        $formatter = new class implements MoneyFormatterInterface {
            public function format(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
            { return 'fmt:' . $money->toFloat() . ':' . $money->getCurrency(); }
            public function formatWithCode(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
            { return 'fmtc:' . $money->toFloat() . ':' . $money->getCurrency() . ':' . ($locale ?? ''); }
        };

        $presenter = new ClientTotalPresenter($formatter);
        $result = $presenter->present($dto, 'sk_SK');

        $this->assertSame(7, $result['client_id']);
        $this->assertSame('ACME', $result['client_name']);
        $this->assertSame(1500.0, $result['total_amount']);
        $this->assertSame('fmtc:1500:USD:sk_SK', $result['total_amount_formatted']);
    }
}
