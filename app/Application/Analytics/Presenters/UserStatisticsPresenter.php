<?php

namespace App\Application\Analytics\Presenters;

use App\Application\Analytics\Contracts\UserStatisticsPresenterInterface;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;

class UserStatisticsPresenter implements UserStatisticsPresenterInterface
{
    public function __construct(private readonly MoneyFormatterInterface $formatter)
    {
    }

    public function present(UserStatisticsDTO $dto, ?string $locale = null): array
    {
        $base = $dto->toArray();
        $base['total_amount_formatted'] = $this->formatter->formatWithCode($dto->totalAmount, $locale);
        return $base;
    }
}
