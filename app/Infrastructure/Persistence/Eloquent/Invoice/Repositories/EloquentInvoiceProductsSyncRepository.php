<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceProductsSyncRepositoryInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Models\Invoice;

/**
 * Eloquent implementation of the Invoice products sync port.
 * Hides model operations from Domain services.
 */
class EloquentInvoiceProductsSyncRepository implements InvoiceProductsSyncRepositoryInterface
{
    public function syncFromInvoiceText(InvoiceId $invoiceId): void
    {
        $model = Invoice::query()->find($invoiceId->getValue());
        if (!$model) { return; }
        $model->syncProductsFromJson();
    }

    /** @return array<int, InvoiceId> */
    public function listIdsWithInvoiceText(): array
    {
        return Invoice::query()
            ->whereNotNull('invoice_text')
            ->pluck('id')
            ->map(fn ($id) => InvoiceId::fromInt((int) $id))
            ->all();
    }
}
