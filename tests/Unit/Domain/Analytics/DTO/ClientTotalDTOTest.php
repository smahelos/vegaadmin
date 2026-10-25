<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\DTO;

use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientTotalDTOTest extends TestCase
{
    #[Test]
    public function from_array_rounds_the_total_to_two_decimals(): void
    {
        $dto = ClientTotalDTO::fromArray([
            'client_id' => 10,
            'client_name' => 'Test Client',
            'total' => 99.999,
        ], 'CZK');

        $this->assertSame(10, $dto->clientId);
        $this->assertSame('Test Client', $dto->clientName);
        $this->assertSame(100.00, $dto->totalAmount->toFloat());
        $this->assertSame('CZK', $dto->totalAmount->getCurrency());
    }

    #[Test]
    public function from_array_rejects_a_row_without_a_total(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ClientTotalDTO::fromArray(['client_id' => 1, 'client_name' => 'X'], 'CZK');
    }

    #[Test]
    public function to_array_exports_the_client_and_total(): void
    {
        $dto = ClientTotalDTO::fromArray([
            'client_id' => 1,
            'client_name' => 'X',
            'total' => 0,
        ], 'CZK');

        $this->assertSame([
            'client_id' => 1,
            'client_name' => 'X',
            'total_amount' => 0.0,
        ], $dto->toArray());
    }

    #[Test]
    public function to_formatted_array_adds_the_formatted_total(): void
    {
        $dto = ClientTotalDTO::fromArray([
            'client_id' => 2,
            'client_name' => 'Y',
            'total' => 0,
        ], 'CZK');
        $formatter = new class implements MoneyFormatterInterface
        {
            public function format(
                Money $money,
                ?string $locale = null,
                ?int $minFraction = null,
                ?int $maxFraction = null,
            ): string {
                return 'X';
            }

            public function formatWithCode(
                Money $money,
                ?string $locale = null,
                ?int $minFraction = null,
                ?int $maxFraction = null,
            ): string {
                return '0.00 CZK';
            }
        };

        $formatted = $dto->toFormattedArray($formatter);

        $this->assertSame('Y', $formatted['client_name']);
        $this->assertSame('0.00 CZK', $formatted['total_amount_formatted']);
    }
}
