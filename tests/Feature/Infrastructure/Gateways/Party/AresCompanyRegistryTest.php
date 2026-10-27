<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Gateways\Party;

use App\Domain\Party\Exceptions\CompanyRegistryUnavailableException;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Shared\Geography\Enums\Country;
use App\Infrastructure\Gateways\Party\AresCompanyRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AresCompanyRegistryTest extends TestCase
{
    #[Test]
    public function reads_the_name_vat_number_and_seat_of_a_company(): void
    {
        Http::fake([
            'ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/00006947' => Http::response([
                'obchodniJmeno' => 'Ministerstvo financí',
                'dic' => 'CZ00006947',
                'sidlo' => [
                    'kodStatu' => 'CZ',
                    'nazevObce' => 'Praha',
                    'nazevUlice' => 'Letenská',
                    'cisloDomovni' => 525,
                    'cisloOrientacni' => 15,
                    'psc' => 11800,
                ],
            ]),
        ]);

        $company = (new AresCompanyRegistry)->findCzechCompany($this->number('00006947'));

        $this->assertSame('Ministerstvo financí', $company?->name);
        $this->assertSame('CZ00006947', $company?->vatNumber?->value);
        $this->assertSame('Letenská 525/15', $company?->address?->street);
        $this->assertSame('11800', $company?->address?->postalCode);
        $this->assertSame(Country::CZ, $company?->address?->country);
    }

    #[Test]
    public function a_member_of_a_vat_group_takes_the_group_vat_number(): void
    {
        Http::fake([
            'ares.gov.cz/*' => Http::response([
                'obchodniJmeno' => 'Komerční banka, a.s.',
                'dicSkDph' => 'CZ699001182',
            ]),
        ]);

        $company = (new AresCompanyRegistry)->findCzechCompany($this->number('45317054'));

        $this->assertSame('CZ699001182', $company?->vatNumber?->value);
    }

    #[Test]
    public function an_own_vat_number_wins_over_the_group_one(): void
    {
        Http::fake([
            'ares.gov.cz/*' => Http::response([
                'obchodniJmeno' => 'Člen skupiny',
                'dic' => 'CZ27082440',
                'dicSkDph' => 'CZ699001182',
            ]),
        ]);

        $company = (new AresCompanyRegistry)->findCzechCompany($this->number('27082440'));

        $this->assertSame('CZ27082440', $company?->vatNumber?->value);
    }

    #[Test]
    public function a_village_without_streets_is_addressed_by_its_part(): void
    {
        Http::fake([
            'ares.gov.cz/*' => Http::response([
                'obchodniJmeno' => 'Statek',
                'sidlo' => [
                    'kodStatu' => 'CZ',
                    'nazevObce' => 'Lhota',
                    'nazevCastiObce' => 'Lhota',
                    'cisloDomovni' => 12,
                    'psc' => 39601,
                ],
            ]),
        ]);

        $company = (new AresCompanyRegistry)->findCzechCompany($this->number('27082440'));

        $this->assertSame('Lhota 12', $company?->address?->street);
        $this->assertNull($company?->vatNumber);
    }

    #[Test]
    public function an_unknown_number_is_not_found(): void
    {
        Http::fake(['ares.gov.cz/*' => Http::response(['kod' => 'NENALEZENO'], 404)]);

        $this->assertNull((new AresCompanyRegistry)->findCzechCompany($this->number('27082440')));
    }

    #[Test]
    public function a_failing_register_is_unavailable(): void
    {
        Http::fake(['ares.gov.cz/*' => Http::response('', 500)]);

        $this->expectException(CompanyRegistryUnavailableException::class);

        (new AresCompanyRegistry)->findCzechCompany($this->number('27082440'));
    }

    #[Test]
    public function an_unreachable_register_is_unavailable(): void
    {
        Http::fake(['ares.gov.cz/*' => fn () => throw new ConnectionException('timeout')]);

        $this->expectException(CompanyRegistryUnavailableException::class);

        (new AresCompanyRegistry)->findCzechCompany($this->number('27082440'));
    }

    private function number(string $number): RegistrationNumber
    {
        return RegistrationNumber::fromParts(Country::CZ, $number) ?? $this->fail('Empty number');
    }
}
