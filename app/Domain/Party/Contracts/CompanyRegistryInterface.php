<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\RegisteredCompanyDTO;
use App\Domain\Party\Exceptions\CompanyRegistryUnavailableException;
use App\Domain\Party\ValueObjects\RegistrationNumber;

/**
 * The public register of Czech companies (ARES).
 */
interface CompanyRegistryInterface
{
    /**
     * Null when the register has no company with this number.
     *
     * @throws CompanyRegistryUnavailableException
     */
    public function findCzechCompany(RegistrationNumber $number): ?RegisteredCompanyDTO;
}
