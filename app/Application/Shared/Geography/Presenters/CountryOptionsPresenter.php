<?php

declare(strict_types=1);

namespace App\Application\Shared\Geography\Presenters;

use App\Domain\Shared\Geography\Contracts\CountryNamesInterface;

/**
 * Countries for the address, registration and bank account selects.
 */
final class CountryOptionsPresenter
{
    public function __construct(
        private readonly CountryNamesInterface $names,
    ) {}

    /**
     * @return array<string, string> name by country code, in alphabetical order of the locale
     */
    public function options(string $locale): array
    {
        return $this->names->sortedNames($locale);
    }
}
