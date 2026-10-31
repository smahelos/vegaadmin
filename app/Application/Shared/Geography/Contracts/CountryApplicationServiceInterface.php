<?php

namespace App\Application\Shared\Geography\Contracts;

interface CountryApplicationServiceInterface
{
    /**
     * @return array<string, array{code:string, name:string, flag:string}>
     */
    public function getCountries(): array;

    public function getCountry(string $code): ?array;
}
