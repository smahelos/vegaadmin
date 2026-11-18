<?php

declare(strict_types=1);

namespace App\Domain\Analytics\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Totals over every owner's records for the admin dashboard. Revenue is one sum per currency, ordered by its code.
 */
final readonly class PlatformStatisticsDTO
{
    /**
     * @param  list<Money>  $revenue
     * @param  list<Money>  $monthlyRevenue
     */
    public function __construct(
        public int $invoiceCount,
        public int $contactCount,
        public int $businessCount,
        public int $userCount,
        public int $recentInvoiceCount,
        public int $recentContactCount,
        public int $activeSubscriptionCount,
        public array $revenue,
        public array $monthlyRevenue,
    ) {}
}
