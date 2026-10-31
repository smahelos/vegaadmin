<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Party\ValueObjects;

use App\Domain\Party\Exceptions\InvalidAddressException;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Shared\Geography\Enums\Country;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    #[Test]
    public function the_parts_are_trimmed_and_an_empty_part_is_null(): void
    {
        $address = Address::fromParts(
            ' Hlavní 1 ',
            'Brno',
            ' ',
            Country::CZ,
        );

        $this->assertSame('Hlavní 1', $address?->street);
        $this->assertSame('Brno', $address?->city);
        $this->assertNull($address?->postalCode);
    }

    #[Test]
    public function a_foreign_postal_code_keeps_its_letters(): void
    {
        $address = Address::fromParts(
            null,
            'London',
            'SW1A 1AA',
            Country::GB,
        );

        $this->assertSame('SW1A 1AA', $address?->postalCode);
    }

    #[Test]
    public function an_address_without_street_city_and_postal_code_is_not_stored_even_with_a_country(): void
    {
        $this->assertNull(Address::fromParts(
            '',
            null,
            ' ',
            Country::CZ,
        ));
    }

    #[Test]
    public function a_filled_address_needs_a_country(): void
    {
        $this->expectException(InvalidAddressException::class);

        Address::fromParts(
            null,
            'Brno',
            null,
            null,
        );
    }
}
