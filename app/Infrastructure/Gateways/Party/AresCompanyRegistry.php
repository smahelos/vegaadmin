<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateways\Party;

use App\Domain\Party\Contracts\CompanyRegistryInterface;
use App\Domain\Party\DTO\RegisteredCompanyDTO;
use App\Domain\Party\Exceptions\CompanyRegistryUnavailableException;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Party\ValueObjects\VatNumber;
use App\Domain\Shared\Geography\Enums\Country;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * ARES, the register of economic subjects of the Czech Ministry of Finance.
 */
final class AresCompanyRegistry implements CompanyRegistryInterface
{
    private const URL = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/';

    private const TIMEOUT_SECONDS = 5;

    public function findCzechCompany(RegistrationNumber $number): ?RegisteredCompanyDTO
    {
        try {
            $response = Http::acceptJson()
                ->timeout(self::TIMEOUT_SECONDS)
                ->get(self::URL . rawurlencode($number->number));
        } catch (ConnectionException $e) {
            throw new CompanyRegistryUnavailableException($e);
        }

        if ($response->notFound()) {
            return null;
        }

        $name = $response->json('obchodniJmeno');
        if (! $response->successful() || ! is_string($name)) {
            throw new CompanyRegistryUnavailableException($response->toException());
        }

        $vatNumber = $response->json('dic');
        $seat = $response->json('sidlo');

        return new RegisteredCompanyDTO(
            $name,
            VatNumber::fromNullable(is_string($vatNumber) ? $vatNumber : null),
            is_array($seat) ? $this->address($seat) : null,
        );
    }

    /**
     * A street name with the house number and, after a slash, the orientation number; a village without streets
     * is addressed by the part of the municipality instead.
     *
     * @param  array<string, mixed>  $seat
     */
    private function address(array $seat): ?Address
    {
        $place = $this->text($seat, 'nazevUlice') ?? $this->text($seat, 'nazevCastiObce');
        $houseNumber = $this->text($seat, 'cisloDomovni');
        $orientationNumber = $this->text($seat, 'cisloOrientacni');
        if ($houseNumber !== null && $orientationNumber !== null) {
            $houseNumber .= '/' . $orientationNumber;
        }

        $street = trim(($place ?? '') . ' ' . ($houseNumber ?? ''));

        return Address::fromParts(
            $street,
            $this->text($seat, 'nazevObce'),
            $this->text($seat, 'psc'),
            Country::tryFrom($this->text($seat, 'kodStatu') ?? '') ?? Country::CZ,
        );
    }

    /**
     * @param  array<string, mixed>  $seat
     */
    private function text(array $seat, string $key): ?string
    {
        $value = $seat[$key] ?? null;
        if (is_int($value)) {
            return (string) $value;
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
