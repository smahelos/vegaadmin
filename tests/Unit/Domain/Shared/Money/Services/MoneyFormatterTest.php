<?php

namespace Tests\Unit\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\Services\MoneyFormatter;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\Contracts\Locale;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyFormatterTest extends TestCase
{
    private MoneyFormatterInterface $formatter;
    private Locale $mockLocale;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockLocale = Mockery::mock('App\Domain\User\Contracts\Locale');
        $this->formatter = new MoneyFormatter($this->mockLocale);
    }

    #[Test]
    public function format_basic_czk_cs_locale(): void
    {
        $m = Money::fromString('1234.5', 'CZK');
        $out = $this->formatter->format($m, 'cs_CZ');
        // Assert contains integer digits in some grouped form and currency marker (symbol or code)
        $this->assertTrue(str_contains($out, 'CZK') || str_contains($out, 'Kč'));
        $digits = preg_replace('/[^0-9]/', '', $out);
        $this->assertSame('123450', substr($digits, 0, 6)); // 1234.50 normalized digits sequence
    }

    #[Test]
    public function format_with_code_forces_currency_code(): void
    {
        $m = Money::fromString('99.99', 'EUR');
        $out = $this->formatter->formatWithCode($m, 'en_US');
        $this->assertStringContainsString('EUR', $out);
    }

    #[Test]
    public function format_respects_fraction_bounds(): void
    {
        $m = Money::fromString('10.123456', 'USD');
        $out = $this->formatter->format($m, 'en_US', 2, 4);
        $this->assertMatchesRegularExpression('/10\.(1234|123|12)/', $out);
    }

    #[Test]
    public function invalid_fraction_constraints_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $m = Money::fromString('1', 'USD');
        $this->formatter->format($m, 'en_US', 5, 2); // min > max
    }
}
