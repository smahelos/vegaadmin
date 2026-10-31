<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Contracts;

interface CountryNamesInterface
{
    /**
     * Every country by its code, ordered alphabetically by its name in the locale.
     *
     * @return array<string, string>
     */
    public function sortedNames(string $locale): array;
}
