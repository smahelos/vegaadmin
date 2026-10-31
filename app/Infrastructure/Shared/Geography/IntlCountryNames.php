<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Geography;

use App\Domain\Shared\Geography\Contracts\CountryNamesInterface;
use App\Domain\Shared\Geography\Enums\Country;
use Collator;
use Locale;

/**
 * Country names from ICU, so all 249 codes are named in every locale without translation files.
 */
final class IntlCountryNames implements CountryNamesInterface
{
    public function sortedNames(string $locale): array
    {
        $names = [];
        foreach (Country::cases() as $country) {
            $names[$country->value] = $this->name($country, $locale);
        }

        (new Collator($locale))->asort($names);

        return $names;
    }

    private function name(Country $country, string $locale): string
    {
        return Locale::getDisplayRegion('-' . $country->value, $locale);
    }
}
