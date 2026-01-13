<?php

namespace App\Application\Shared\Money\Contracts;

/**
 * Application-layer facade for currency operations used by controllers.
 * Keeps controllers decoupled from Domain contracts.
 */
interface CurrencyApplicationServiceInterface
{
    /**
     * @return array<string,string>
     */
    public function getCommonCurrencies(): array;

    /**
     * @return array<string,string>
     */
    public function getAllCurrencies(): array;

    public function getExchangeRate(string $from, string $to): ?float;

    public function convert(float $amount, string $from, string $to): ?float;
}
