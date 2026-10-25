<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Party\DTO;

use App\Domain\Party\DTO\ClientWriteData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientWriteDataTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function icoProvider(): array
    {
        return [
            'missing' => [null, null],
            'empty' => ['', null],
            'whitespace' => ['  ', null],
            'padded' => [' 12345678 ', '12345678'],
            'zero' => ['0', '0'],
        ];
    }

    #[Test]
    #[DataProvider('icoProvider')]
    public function stores_an_empty_ico_as_null(?string $ico, ?string $expected): void
    {
        $data = new ClientWriteData(ico: $ico);

        $this->assertSame($expected, $data->ico);
    }
}
