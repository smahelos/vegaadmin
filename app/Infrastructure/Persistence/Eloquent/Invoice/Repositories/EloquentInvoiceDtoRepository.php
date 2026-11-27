<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceIssue;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\Exceptions\InvoiceNumberTakenException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\StoredPdf;
use App\Domain\Invoice\ValueObjects\VatLine;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\Invoice;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;

final class EloquentInvoiceDtoRepository implements InvoiceDtoReadRepositoryInterface, InvoiceDtoWriteRepositoryInterface
{
    private const RELATIONS = ['invoiceProducts', 'vatLines'];

    public function __construct(
        private readonly EloquentInvoiceMapper $mapper,
    ) {}

    public function findById(InvoiceId $id, ReadScope|AllOwnersScope $scope): ?InvoiceDTO
    {
        // The mapper reads the lines and the VAT lines
        $query = Invoice::with(self::RELATIONS);
        if ($scope instanceof ReadScope) {
            $query->where('user_id', $scope->owner->value);
        }
        $model = $query->find($id->value);

        return $model === null ? null : $this->mapper->toDto($model);
    }

    public function findByIdForUpdate(InvoiceId $id, WriteScope $scope): ?InvoiceDTO
    {
        $query = Invoice::query();
        $query->with(self::RELATIONS);
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
        InvoiceStatus $status,
    ): InvoiceDTO {
        $attributes = $this->mapper->toAttributes(
            $data,
            $parties,
            $total,
        );
        $attributes['user_id'] = $scope->owner->value;
        $attributes['payment_status'] = $status->value;

        $model = Invoice::create($attributes);
        $this->createLines($model, $lines);
        $model->load(self::RELATIONS);

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
        $model->load(self::RELATIONS);

        return $this->mapper->toDto($model);
    }

    /** @param  list<VatLine>  $vatLines */
    public function issue(
        InvoiceId $id,
        WriteScope $scope,
        InvoiceIssue $issue,
        array $vatLines,
    ): InvoiceDTO {
        $query = Invoice::query();
        $query->where('user_id', $scope->owner->value);
        $model = $query->findOrFail($id->value);
        try {
            $model->update(
                $this->mapper->toIssueAttributes($issue),
            );
        } catch (UniqueConstraintViolationException $e) {
            throw new InvoiceNumberTakenException(previous: $e);
        }
        $relation = $model->vatLines();
        foreach ($vatLines as $vatLine) {
            $relation->create(
                $this->mapper->toVatLineAttributes($vatLine),
            );
        }
        $model->load(self::RELATIONS);

        return $this->mapper->toDto($model);
    }

    public function storePdf(
        InvoiceId $id,
        WriteScope $scope,
        StoredPdf $pdf,
    ): InvoiceDTO {
        $query = Invoice::query();
        $query->where('user_id', $scope->owner->value);
        $model = $query->findOrFail($id->value);
        $model->update(
            $this->mapper->toPdfAttributes($pdf),
        );
        $model->load(self::RELATIONS);

        return $this->mapper->toDto($model);
    }

    public function delete(InvoiceId $id, WriteScope $scope): void
    {
        // The lines and VAT lines go with the invoice (foreign keys with cascade). The model is deleted, not the query,
        // so InvoiceObserver publishes UserDataChanged
        $query = Invoice::query();
        $query->where('user_id', $scope->owner->value);
        $model = $query->findOrFail($id->value);
        $model->delete();
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
