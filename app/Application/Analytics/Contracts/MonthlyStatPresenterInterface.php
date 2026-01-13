<?php

namespace App\Application\Analytics\Contracts;

use App\Domain\Analytics\DTO\MonthlyStatDTO;

/**
 * Formats MonthlyStatDTO for UI/API responses.
 */
interface MonthlyStatPresenterInterface
{
    /**
     * @return array{month:string,total:float,total_formatted:string}
     */
    public function present(MonthlyStatDTO $dto, ?string $locale = null): array;
}
