<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceLimitApplicationServiceInterface;
use App\Application\Shared\Form\DTO\LimitDTO;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Domain\User\ValueObjects\UserId;

/**
 * Service for invoice limit management operations
 */
class InvoiceLimitApplicationService implements InvoiceLimitApplicationServiceInterface
{
    public function __construct(
        private UniversalLimitServiceInterface $limitService,
    ) {}

    /**
     * Get limit information for user
     */
    public function getLimitInfo(UserId $userId): array
    {
        $userIdInt = $userId->toInt();

        // Always choose the best period to reflect most permissive active limit
        $bestPeriod = $this->limitService->getBestPeriodType(
            $userIdInt,
            'invoice',
            'count',
        );
        $limitCheck = $this->limitService->checkLimit(
            $userIdInt,
            'invoice',
            'count',
            $bestPeriod,
        );
        $currentUsage = $this->limitService->getUsageStatistics(
            $userIdInt,
            'invoice',
            'count',
            $bestPeriod,
        );

        $dto = new LimitDTO(
            limit: (int) ($limitCheck['limit'] ?? 0),
            currentUsage: (int) ($currentUsage['current_usage'] ?? 0),
            allowed: (bool) ($limitCheck['allowed'] ?? false),
        );

        return $dto->toArray();
    }
}
