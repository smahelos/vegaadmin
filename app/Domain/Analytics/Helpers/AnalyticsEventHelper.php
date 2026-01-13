<?php

namespace App\Domain\Analytics\Helpers;

use App\Domain\Analytics\Events\AnalyticsDataChanged;
use App\Domain\Shared\Events\DomainEventDispatcher;

/**
 * Helper class for dispatching analytics events
 */
class AnalyticsEventHelper
{
    public function __construct(
        private readonly DomainEventDispatcher $eventDispatcher
    ) {}

    /**
     * Dispatch analytics data changed event for invoice operations
     */
    public function dispatchInvoiceChange(int $userId, int $invoiceId): void
    {
        $event = new AnalyticsDataChanged($userId, 'invoice', $invoiceId);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Dispatch analytics data changed event for client operations
     */
    public function dispatchClientChange(int $userId, int $clientId): void
    {
        $event = new AnalyticsDataChanged($userId, 'client', $clientId);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Dispatch analytics data changed event for supplier operations
     */
    public function dispatchSupplierChange(int $userId, int $supplierId): void
    {
        $event = new AnalyticsDataChanged($userId, 'supplier', $supplierId);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Dispatch general analytics data changed event
     */
    public function dispatchGeneralChange(int $userId, string $changeType = 'general', ?int $entityId = null): void
    {
        $event = new AnalyticsDataChanged($userId, $changeType, $entityId);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Dispatch dashboard refresh event
     */
    public function dispatchDashboardRefresh(int $userId): void
    {
        $event = new AnalyticsDataChanged($userId, 'dashboard');
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Dispatch monthly statistics refresh event
     */
    public function dispatchMonthlyStatsRefresh(int $userId): void
    {
        $event = new AnalyticsDataChanged($userId, 'monthly');
        $this->eventDispatcher->dispatch($event);
    }
}
