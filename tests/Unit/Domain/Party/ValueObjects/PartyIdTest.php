<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Party\ValueObjects;

use App\Domain\Party\ValueObjects\PartyId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PartyIdTest extends TestCase
{
    #[Test]
    public function creates_from_positive_int(): void
    {
        $id = PartyId::fromInt(5);
        $this->assertSame(5, $id->toInt());
        $this->assertSame('5', (string) $id);
    }

    #[Test]
    public function from_int_throws_on_non_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PartyId::fromInt(0);
    }
}
