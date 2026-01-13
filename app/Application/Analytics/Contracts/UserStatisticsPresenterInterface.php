<?php

namespace App\Application\Analytics\Contracts;

use App\Domain\Analytics\DTO\UserStatisticsDTO;

/**
 * Formats UserStatisticsDTO for UI/API responses.
 */
interface UserStatisticsPresenterInterface
{
    /**
     * @return array{invoice_count:int,client_count:int,suppliers_count:int,total_amount:float,total_amount_formatted:string}
     */
    public function present(UserStatisticsDTO $dto, ?string $locale = null): array;
}
