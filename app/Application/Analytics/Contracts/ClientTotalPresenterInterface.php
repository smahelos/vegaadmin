<?php

namespace App\Application\Analytics\Contracts;

use App\Domain\Analytics\DTO\ClientTotalDTO;

/**
 * Formats ClientTotalDTO for UI/API responses.
 */
interface ClientTotalPresenterInterface
{
    /**
     * @return array{client_id:int,client_name:string,total_amount:float,total_amount_formatted:string}
     */
    public function present(ClientTotalDTO $dto, ?string $locale = null): array;
}
