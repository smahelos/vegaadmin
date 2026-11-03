<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\Contracts\InvoiceWriteRepositoryInterface;
use App\Models\Invoice;

class EloquentInvoiceRepository implements InvoiceReadRepositoryInterface, InvoiceWriteRepositoryInterface
{
    public function findForUser(int $userId, int $invoiceId): ?Invoice
    {
        $query = Invoice::with(['invoiceProducts']);
        $query->where('user_id', $userId);

        return $query->find($invoiceId);
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function make(array $attributes = []): Invoice
    {
        return new Invoice($attributes);
    }

    public function findInvoiceNumbersForYear(int $userId, int $year): array
    {
        $query = Invoice::where('user_id', $userId);
        $query->where(
            'invoice_vs',
            'like',
            $year . '%',
        );
        $numbers = $query->pluck('invoice_vs');

        return $numbers->all();
    }

    public function setTemplate(int $id, string $template): bool
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return false;
        }
        // Persist using existing 'template' column (legacy naming)
        $invoice->template = $template;

        return (bool) $invoice->save();
    }

    /** Update by ID without user scope (admin or internal use). */
    public function updateById(int $id, array $data): Invoice
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);

        return $invoice->fresh();
    }
}
