<?php

declare(strict_types=1);

namespace App\Application\Party\Services;

use App\Domain\Party\Contracts\CompanyRegistryInterface;
use App\Domain\Party\DTO\RegisteredCompanyDTO;
use App\Domain\Party\Exceptions\CompanyRegistryUnavailableException;
use App\Domain\Party\ValueObjects\RegistrationNumber;

/**
 * Looks a Czech company up in the public register to prefill a form; nothing is stored.
 */
final class CompanyRegistryApplicationService
{
    public function __construct(
        private readonly CompanyRegistryInterface $registry,
    ) {}

    /**
     * Null for a foreign number, which no register is connected for, and for a number the register does not know.
     *
     * @throws CompanyRegistryUnavailableException
     */
    public function findCompany(RegistrationNumber $number): ?RegisteredCompanyDTO
    {
        if (! $number->isCzech()) {
            return null;
        }

        return $this->registry->findCzechCompany($number);
    }
}
