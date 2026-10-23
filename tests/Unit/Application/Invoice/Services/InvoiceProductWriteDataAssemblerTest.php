<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Invoice\Services;

use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceProductWriteDataAssemblerTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function numericInputs(): array
    {
        return [
            'plain' => ['1.5', '1.5'],
            'leading plus' => ['+1.5', '1.5'],
            'leading dot' => ['.5', '0.5'],
            'trailing dot' => ['5.', '5'],
            'surrounding spaces' => [' 5 ', '5'],
        ];
    }

    #[Test]
    #[DataProvider('numericInputs')]
    public function normalizes_numbers_that_pass_validation(string $input, string $expected): void
    {
        $line = (new InvoiceProductWriteDataAssembler)->fromValidated([[
            'name' => 'Consulting',
            'quantity' => $input,
            'price' => $input,
            'currency' => 'CZK',
            'tax_rate' => $input,
        ]])[0];

        $this->assertSame($expected, $line->quantity);
        $this->assertSame($expected, $line->taxRate);
        $this->assertSame($expected, $line->price->getAmount());
    }

    #[Test]
    public function computes_the_line_from_a_trailing_dot_quantity_and_rate(): void
    {
        $line = (new InvoiceProductWriteDataAssembler)->fromValidated([[
            'name' => 'Consulting',
            'quantity' => '2.',
            'price' => '100',
            'currency' => 'CZK',
            'tax_rate' => '21.',
        ]])[0];

        $this->assertSame('242', $line->total()->getAmount());
    }
}
