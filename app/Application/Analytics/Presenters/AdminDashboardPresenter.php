<?php

declare(strict_types=1);

namespace App\Application\Analytics\Presenters;

use App\Domain\Analytics\DTO\UserActivityDTO;
use App\Domain\Analytics\DTO\UserActivityStatisticsDTO;
use App\Domain\Analytics\Enums\ActivityLevel;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;

final class AdminDashboardPresenter
{
    public function __construct(
        private readonly MoneyFormatterInterface $money,
        private readonly DateFormatterInterface $dates,
    ) {}

    /**
     * @param  list<Money>  $revenue  one sum per currency
     * @return list<string>
     */
    public function revenue(array $revenue, string $locale): array
    {
        return array_map(
            fn (Money $amount): string => $this->money->formatWithCode($amount, $locale),
            $revenue,
        );
    }

    /**
     * Share of users with an invoice in the last 30 days, in percent.
     */
    public function activityRate(UserActivityStatisticsDTO $activity): float
    {
        if ($activity->userCount === 0) {
            return 0.0;
        }

        return round($activity->activeUserCount / $activity->userCount * 100, 1);
    }

    public function lastInvoice(UserActivityDTO $user, string $locale): ?string
    {
        if ($user->lastInvoice === null) {
            return null;
        }

        return $this->dates->formatDate($user->lastInvoice, $locale);
    }

    /**
     * Tabler colour of the activity badge.
     */
    public function levelColour(ActivityLevel $level): string
    {
        return match ($level) {
            ActivityLevel::HIGH => 'success',
            ActivityLevel::MEDIUM => 'primary',
            ActivityLevel::LOW => 'warning',
            ActivityLevel::INACTIVE => 'secondary',
        };
    }
}
