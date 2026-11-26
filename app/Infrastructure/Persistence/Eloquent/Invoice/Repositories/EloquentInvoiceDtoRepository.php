<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\Invoice;
use InvalidArgumentException;

final class EloquentInvoiceDtoRepository implements InvoiceDtoReadRepositoryInterface, InvoiceDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentInvoiceMapper $mapper,
    ) {}

    public function findById(InvoiceId $id, ReadScope $scope): ?InvoiceDTO
    {
        // The mapper reads the lines
        $query = Invoice::with('invoiceProducts');
        $query->where('user_id', $scope->owner->value);
        $model = $query->find($id->value);

        return $model === null ? null : $this->mapper->toDto($model);
    }

    public function findByIdForUpdate(InvoiceId $id, WriteScope $scope): ?InvoiceDTO
    {
        $query = Invoice::query();
        $query->with('invoiceProducts');
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->value);
        $query->lockForUpdate();

        $model = $query->first();

        return $model === null ? null : $this->mapper->toDto($model);
    }

    /** @param  list<InvoiceProductWriteData>  $lines */
    public function create(
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
        array $lines,
    ): InvoiceDTO {
        $attributes = $this->mapper->toAttributes(
            $data,
            $parties,
            $total,
        );
        $attributes['user_id'] = $scope->owner->value;

        $model = Invoice::create($attributes);
        $this->createLines($model, $lines);
        $model->load('invoiceProducts');

        return $this->mapper->toDto($model);
    }

    /** @param  list<InvoiceProductWriteData>  $lines */
    public function update(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
        array $lines,
    ): InvoiceDTO {
        $attributes = $this->mapper->toAttributes(
            $data,
            $parties,
            $total,
        );
        $query = Invoice::query();
        $query->where('user_id', $scope->owner->value);
        $model = $query->findOrFail($id->value);
        $model->update($attributes);
        $lineQuery = $model->invoiceProducts();
        $lineQuery->delete();
        $this->createLines($model, $lines);
        $model->refresh();
        $model->load('invoiceProducts');

        return $this->mapper->toDto($model);
    }

    public function setTemplate(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceTemplate $template,
    ): void {
        $query = Invoice::query();
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->value);
        $query->update(['template' => $template->value]);
    }

    public function setStatus(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceStatus $status,
    ): void {
        $query = Invoice::query();
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->value);
        $query->update(['payment_status' => $status->value]);
    }

    public function linkContact(
        InvoiceId $id,
        WriteScope $scope,
        ContactId $contactId,
    ): void {
        $query = Invoice::query();
        $query->whereKey($id->value);
        $query->where('user_id', $scope->owner->value);
        $query->update(['contact_id' => $contactId->value]);
    }

    public function findLastInvoiceNumber(ReadScope $scope, int $year): ?string
    {
        // Highest sequence among numbers in the standard format; sorting the strings would put
        // 20259999 after 202510000, and non-standard numbers must not affect the sequence
        $query = Invoice::query();
        $query->where('user_id', $scope->owner->value);
        $query->where(
            'invoice_vs',
            'like',
            $year . '%',
        );
        $numbers = $query->pluck('invoice_vs');

        $last = null;
        foreach ($numbers as $number) {
            try {
                $candidate = new InvoiceNumber($number);
            } catch (InvalidArgumentException) {
                continue;
            }
            if ($candidate->year() === $year && ($last === null || $candidate->isGreaterThan($last))) {
                $last = $candidate;
            }
        }

        return $last?->value;
    }

    /** @param  list<InvoiceProductWriteData>  $lines */
    private function createLines(Invoice $model, array $lines): void
    {
        $relation = $model->invoiceProducts();
        foreach ($lines as $line) {
            $relation->create(
                $this->mapper->toLineAttributes($line),
            );
        }
    }
}
