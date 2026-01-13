<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceLimitApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Application\Shared\Form\DTO\LimitDTO;

/**
 * Service for invoice limit management operations
 */
class InvoiceLimitApplicationService implements InvoiceLimitApplicationServiceInterface
{
    public function __construct(
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
        private UniversalLimitServiceInterface $limitService
    ) {}

    /**
     * Get total count of invoices for user
     */
    public function getInvoicesCount(UserId $userId): int
    {
        $invoices = $this->invoiceReadRepository->allForUser($userId->toInt());
        return $invoices->count();
    }

    /**
     * Check if user can create new invoice
     */
    public function canCreateInvoice(UserId $userId): bool
    {
        $userIdInt = $userId->toInt();
        $bestPeriod = $this->limitService->getBestPeriodType($userIdInt, 'invoice', 'count');
        $limitCheck = $this->limitService->checkLimit($userIdInt, 'invoice', 'count', $bestPeriod);
        
        return (bool)($limitCheck['allowed'] ?? false);
    }

    /**
     * Get remaining invoice count for user
     */
    public function getRemainingInvoices(UserId $userId): int
    {
        $userIdInt = $userId->toInt();
        $bestPeriod = $this->limitService->getBestPeriodType($userIdInt, 'invoice', 'count');
        $limitCheck = $this->limitService->checkLimit($userIdInt, 'invoice', 'count', $bestPeriod);
        $currentUsage = $this->limitService->getUsageStatistics($userIdInt, 'invoice', 'count', $bestPeriod);
        
        $limit = (int)($limitCheck['limit'] ?? 0);
        $usage = (int)($currentUsage['current_usage'] ?? 0);
        
        return max(0, $limit - $usage);
    }

    /**
     * Get user's invoice limit
     */
    public function getUserLimit(UserId $userId): int
    {
        $userIdInt = $userId->toInt();
        $bestPeriod = $this->limitService->getBestPeriodType($userIdInt, 'invoice', 'count');
        $limitCheck = $this->limitService->checkLimit($userIdInt, 'invoice', 'count', $bestPeriod);
        
        return (int)($limitCheck['limit'] ?? 0);
    }

    /**
     * Check if user has reached limit
     */
    public function hasReachedLimit(UserId $userId): bool
    {
        return !$this->canCreateInvoice($userId);
    }

    /**
     * Get limit information for user
     */
    public function getLimitInfo(UserId $userId): array
    {
        $userIdInt = $userId->toInt();
        
        // Always choose the best period to reflect most permissive active limit
        $bestPeriod = $this->limitService->getBestPeriodType($userIdInt, 'invoice', 'count');
        $limitCheck = $this->limitService->checkLimit($userIdInt, 'invoice', 'count', $bestPeriod);
        $currentUsage = $this->limitService->getUsageStatistics($userIdInt, 'invoice', 'count', $bestPeriod);
        
        $dto = new LimitDTO(
            limit: (int)($limitCheck['limit'] ?? 0),
            currentUsage: (int)($currentUsage['current_usage'] ?? 0),
            allowed: (bool)($limitCheck['allowed'] ?? false)
        );
        
        return $dto->toArray();
    }

    /**
     * Get usage statistics for user
     */
    public function getUsageStatistics(UserId $userId): array
    {
        $userIdInt = $userId->toInt();
        $bestPeriod = $this->limitService->getBestPeriodType($userIdInt, 'invoice', 'count');
        $currentUsage = $this->limitService->getUsageStatistics($userIdInt, 'invoice', 'count', $bestPeriod);
        $limitCheck = $this->limitService->checkLimit($userIdInt, 'invoice', 'count', $bestPeriod);
        
        return [
            'period' => $bestPeriod,
            'current_usage' => (int)($currentUsage['current_usage'] ?? 0),
            'limit' => (int)($limitCheck['limit'] ?? 0),
            'remaining' => $this->getRemainingInvoices($userId),
            'percentage_used' => $this->calculateUsagePercentage($userId),
            'is_at_limit' => $this->hasReachedLimit($userId),
        ];
    }

    /**
     * Check if user can upgrade to remove limits
     */
    public function canUpgrade(UserId $userId): bool
    {
        // This would depend on the business logic for upgrades
        // For now, assume users who have reached their limit can upgrade
        return $this->hasReachedLimit($userId);
    }

    /**
     * Get detailed limit breakdown by period
     */
    public function getLimitBreakdown(UserId $userId): array
    {
        $userIdInt = $userId->toInt();
        $periods = ['monthly', 'yearly', 'lifetime']; // Available periods
        $breakdown = [];
        
        foreach ($periods as $period) {
            $limitCheck = $this->limitService->checkLimit($userIdInt, 'invoice', 'count', $period);
            $currentUsage = $this->limitService->getUsageStatistics($userIdInt, 'invoice', 'count', $period);
            
            $breakdown[$period] = [
                'limit' => (int)($limitCheck['limit'] ?? 0),
                'current_usage' => (int)($currentUsage['current_usage'] ?? 0),
                'allowed' => (bool)($limitCheck['allowed'] ?? false),
                'remaining' => max(0, (int)($limitCheck['limit'] ?? 0) - (int)($currentUsage['current_usage'] ?? 0)),
            ];
        }
        
        return $breakdown;
    }

    /**
     * Calculate usage percentage
     */
    private function calculateUsagePercentage(UserId $userId): float
    {
        $userIdInt = $userId->toInt();
        $bestPeriod = $this->limitService->getBestPeriodType($userIdInt, 'invoice', 'count');
        $limitCheck = $this->limitService->checkLimit($userIdInt, 'invoice', 'count', $bestPeriod);
        $currentUsage = $this->limitService->getUsageStatistics($userIdInt, 'invoice', 'count', $bestPeriod);
        
        $limit = (int)($limitCheck['limit'] ?? 0);
        $usage = (int)($currentUsage['current_usage'] ?? 0);
        
        if ($limit === 0) {
            return 0.0;
        }
        
        return min(100.0, ($usage / $limit) * 100.0);
    }

    /**
     * Check if user is approaching limit (80% or more used)
     */
    public function isApproachingLimit(UserId $userId): bool
    {
        return $this->calculateUsagePercentage($userId) >= 80.0;
    }

    /**
     * Get limit warning message if applicable
     */
    public function getLimitWarning(UserId $userId): ?string
    {
        if ($this->hasReachedLimit($userId)) {
            return 'You have reached your invoice limit. Please upgrade to create more invoices.';
        }
        
        if ($this->isApproachingLimit($userId)) {
            $remaining = $this->getRemainingInvoices($userId);
            return "You are approaching your invoice limit. {$remaining} invoices remaining.";
        }
        
        return null;
    }
}
