<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\Contracts\InvoiceWriteRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use Illuminate\Support\Collection;
use App\Models\Invoice;

class EloquentInvoiceRepository implements InvoiceReadRepositoryInterface, InvoiceWriteRepositoryInterface
{
    public function findById(int $id): Invoice
    {
        return Invoice::findOrFail($id);
    }

    public function findForUser(int $userId, int $invoiceId): ?Invoice
    {
        return Invoice::with(['invoiceProducts'])
            ->where('user_id', $userId)
            ->find($invoiceId);
    }

    public function listForUser(int $userId): Collection
    {
        return Invoice::with(['invoiceProducts'])
            ->where('user_id', $userId)
            ->get();
    }

    public function listAll(): Collection
    {
        return Invoice::with(['invoiceProducts'])->get();
    }

    /** Nullable finder for admin (DTO repo convenience). */
    public function findByIdAny(int $id): ?Invoice
    {
        return Invoice::with(['invoiceProducts'])->find($id);
    }

    /** Dropdown options for a user's invoices: [ ['id'=>int,'text'=>string], ... ] */
    public function getInvoicesForDropdown(int $userId): array
    {
        return Invoice::where('user_id', $userId)
            ->orderBy('invoice_vs', 'desc')
            ->get(['id', 'invoice_vs'])
            ->map(fn(Invoice $model) => [
                'id' => $model->id,
                'text' => (string)($model->invoice_vs ?? ('#' . $model->id)),
            ])
            ->values()
            ->all();
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function updateForUser(int $userId, int $invoiceId, array $data): Invoice
    {
        $invoice = Invoice::where('user_id', $userId)->findOrFail($invoiceId);
        $invoice->update($data);
        return $invoice;
    }

    public function make(array $attributes = []): Invoice
    {
        return new Invoice($attributes);
    }

    public function findLastInvoiceForYear(int $userId, int $year): ?Invoice
    {
        return Invoice::where('user_id', $userId)
            ->where('invoice_vs', 'like', $year . '%')
            ->orderBy('invoice_vs', 'desc')
            ->first();
    }

    public function markAsPaid(int $id, int $paidStatusId): bool
    {
        $invoice = Invoice::find($id);
        if (!$invoice) { return false; }
        $invoice->payment_status_id = $paidStatusId;
        return (bool)$invoice->save();
    }

    public function setTemplate(int $id, string $template): bool
    {
        $invoice = Invoice::find($id);
        if (!$invoice) { return false; }
        // Persist using existing 'template' column (legacy naming)
        $invoice->template = $template;
        return (bool)$invoice->save();
    }

    public function changeStatus(int $id, int $statusId): bool
    {
        $invoice = Invoice::find($id);
        if (!$invoice) { return false; }
        $invoice->payment_status_id = $statusId;
        return (bool)$invoice->save();
    }

    public function listWithInvoiceText(): Collection
    {
        return Invoice::whereNotNull('invoice_text')->get();
    }

    /** Update by ID without user scope (admin or internal use). */
    public function updateById(int $id, array $data): Invoice
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);
        return $invoice->fresh();
    }

    /** Delete by ID without user scope. */
    public function deleteById(int $id): bool
    {
        if (!Invoice::where('id', $id)->exists()) {
            return false;
        }
        Invoice::where('id', $id)->delete();

        return true;
    }

    public function allForAdmin(): Collection
    {
        return Invoice::with(['invoiceProducts'])->get();
    }

    public function allForUser(int $userId): Collection
    {
        return Invoice::where('user_id', $userId)
            ->with(['invoiceProducts'])
            ->get();
    }

    public function existsWithNumber(int $userId, string $number): bool
    {
        return Invoice::where('user_id', $userId)
            ->where('invoice_vs', $number)
            ->exists();
    }

    public function findOverdueForUser(int $userId): array
    {
        $now = date('Y-m-d');
        return Invoice::where('user_id', $userId)
            ->where('due_date', '<', $now)
            ->whereHas('paymentStatus', function ($query) {
                $query->where('slug', 'unpaid');
            })
            ->orderBy('due_date', 'asc')
            ->with(['invoiceProducts'])
            ->get()
            ->all();
    }

    public function findUnpaidForUser(int $userId): array
    {
        return Invoice::where('user_id', $userId)
            ->whereHas('paymentStatus', function ($query) {
                $query->where('slug', 'unpaid');
            })
            ->orderBy('due_date', 'asc')
            ->with(['invoiceProducts'])
            ->get()
            ->all();
    }
    
    public function getUserInvoiceCount(int $userId): int
    {
        return Invoice::where('user_id', $userId)->count();
    }
}
