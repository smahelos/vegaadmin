<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\Contracts\InvoiceWriteRepositoryInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Models\Invoice;

class EloquentInvoiceRepository implements InvoiceReadRepositoryInterface, InvoiceWriteRepositoryInterface
{
    public function find(InvoiceId $id, ReadScope $scope): ?Invoice
    {
        $query = Invoice::with([
            'invoiceProducts',
            'paymentMethod',
            'paymentStatus',
        ]);
        $query->where('user_id', $scope->owner->toInt());

        return $query->find($id->getValue());
    }

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

    public function setTemplate(
        int $id,
        int $owner,
        string $template,
    ): bool {
        $query = Invoice::query();
        $query->where('user_id', $owner);
        $invoice = $query->find($id);
        if (!$invoice) {
            return false;
        }
        // Persist using existing 'template' column (legacy naming)
        $invoice->template = $template;

        return (bool) $invoice->save();
    }

    public function updateById(
        int $id,
        int $owner,
        array $data,
    ): Invoice {
        $query = Invoice::query();
        $query->where('user_id', $owner);
        $invoice = $query->findOrFail($id);
        $invoice->update($data);

        return $invoice->fresh();
    }
}
