<?php

namespace Tests\Unit\Domain\Party\ValueObjects;

use App\Domain\Party\ValueObjects\PartyId;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PartyIdTest extends TestCase
{
    #[Test]
    public function creates_from_positive_int(): void
    {
        $id = PartyId::fromInt(5);
        $this->assertSame(5, $id->toInt());
        $this->assertSame('5', (string)$id);
    }

    #[Test]
    public function try_from_accepts_string_digits(): void
    {
        $id = PartyId::tryFrom('12');
        $this->assertNotNull($id);
        $this->assertSame(12, $id->toInt());
    }

    #[Test]
    public function try_from_invalid_returns_null(): void
    {
        $this->assertNull(PartyId::tryFrom('abc'));
        $this->assertNull(PartyId::tryFrom(0));
        $this->assertNull(PartyId::tryFrom(-3));
    }

    #[Test]
    public function equality_compares_value(): void
    {
        $a = PartyId::fromInt(7);
        $b = PartyId::fromInt(7);
        $c = PartyId::fromInt(8);
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    #[Test]
    public function from_int_throws_on_non_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PartyId::fromInt(0);
    }
}
