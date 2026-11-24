<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\ValueObjects;

use App\Domain\Payment\Exceptions\InvalidVariableSymbolException;
use App\Domain\Payment\ValueObjects\VariableSymbol;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VariableSymbolTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function validSymbols(): array
    {
        return [
            'one digit' => ['7'],
            'leading zeros' => ['0012'],
            'ten digits' => ['2026999999'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function invalidSymbols(): array
    {
        return [
            'empty' => [''],
            'eleven digits' => ['20261000000'],
            'letters' => ['II20260001'],
            'space' => ['2026 0001'],
            'trailing newline' => ["20260001\n"],
        ];
    }

    #[Test]
    #[DataProvider('validSymbols')]
    public function keeps_up_to_ten_digits_as_given(string $value): void
    {
        $symbol = new VariableSymbol($value);

        $this->assertSame($value, $symbol->value);
    }

    #[Test]
    #[DataProvider('invalidSymbols')]
    public function refuses_anything_but_one_to_ten_digits(string $value): void
    {
        $this->expectException(InvalidVariableSymbolException::class);

        new VariableSymbol($value);
    }
}
