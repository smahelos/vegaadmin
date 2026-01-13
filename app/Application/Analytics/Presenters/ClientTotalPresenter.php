<?php

namespace App\Application\Analytics\Presenters;

use App\Application\Analytics\Contracts\ClientTotalPresenterInterface;
use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;

class ClientTotalPresenter implements ClientTotalPresenterInterface
{
    public function __construct(private readonly MoneyFormatterInterface $formatter)
    {
    }

    public function present(ClientTotalDTO $dto, ?string $locale = null): array
    {
        $base = $dto->toArray();
        $base['total_amount_formatted'] = $this->formatter->formatWithCode($dto->totalAmount, $locale);
        return $base;
    }
}
