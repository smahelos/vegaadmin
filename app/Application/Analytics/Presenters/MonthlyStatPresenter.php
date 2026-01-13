<?php

namespace App\Application\Analytics\Presenters;

use App\Application\Analytics\Contracts\MonthlyStatPresenterInterface;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;

class MonthlyStatPresenter implements MonthlyStatPresenterInterface
{
    public function __construct(private readonly MoneyFormatterInterface $formatter)
    {
    }

    public function present(MonthlyStatDTO $dto, ?string $locale = null): array
    {
        $base = $dto->toArray();
        $base['total_formatted'] = $this->formatter->formatWithCode($dto->total, $locale);
        return $base;
    }
}
