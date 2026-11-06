<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\Invoice;
use App\Models\Status;
use InvalidArgumentException;
use LogicException;

final class EloquentInvoiceDtoRepository implements InvoiceDtoReadRepositoryInterface, InvoiceDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentInvoiceRepository $invoiceRepository,
        private readonly EloquentInvoiceMapper $mapper,
    ) {}

    public function findByIdForUser(InvoiceId $id, UserId $userId): ?InvoiceDTO
    {
        $model = $this->invoiceRepository->findForUser($userId->toInt(), $id->getValue());

        return $model ? $this->mapper->toDto($model) : null;
    }

    public function findByIdForUserForUpdate(InvoiceId $id, UserId $userId): ?InvoiceDTO
    {
        $query = Invoice::query();
        $query->with('invoiceProducts');
        $query->whereKey($id->getValue());
        $query->where('user_id', $userId->toInt());
        $query->lockForUpdate();

        $model = $query->first();

        return $model === null ? null : $this->mapper->toDto($model);
    }

    public function create(
        UserId $owner,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): InvoiceDTO {
        $attributes = $this->mapper->toAttributes(
            $data,
            $parties,
            $total,
        );
        $attributes['user_id'] = $owner->toInt();
        $attributes['payment_status_id'] = $this->statusId($data->status);

        $model = $this->invoiceRepository->create($attributes);

        return $this->mapper->toDto($model);
    }

    public function update(
        InvoiceId $id,
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): InvoiceDTO {
        $attributes = $this->mapper->toAttributes(
            $data,
            $parties,
            $total,
        );
        $attributes['payment_status_id'] = $this->statusId($data->status);

        $model = $this->invoiceRepository->updateById($id->getValue(), $attributes);

        return $this->mapper->toDto($model);
    }

    public function setTemplate(InvoiceId $id, string $template): bool
    {
        return $this->invoiceRepository->setTemplate($id->getValue(), $template);
    }

    public function linkContact(InvoiceId $id, ContactId $contactId): void
    {
        $query = Invoice::query();
        $query->whereKey($id->getValue());
        $query->update(['contact_id' => $contactId->value]);
    }

    public function findLastInvoiceNumber(UserId $userId, int $year): ?string
    {
        // Highest sequence among numbers in the standard format; sorting the strings would put
        // 20259999 after 202510000, and non-standard numbers must not affect the sequence
        $last = null;
        foreach ($this->invoiceRepository->findInvoiceNumbersForYear($userId->toInt(), $year) as $number) {
            try {
                $candidate = new InvoiceNumber($number);
            } catch (InvalidArgumentException) {
                continue;
            }
            if ($candidate->getYear() === $year && ($last === null || $candidate->isGreaterThan($last))) {
                $last = $candidate;
            }
        }

        return $last?->getNumber();
    }

    /** The statuses row an invoice status is stored as; the reference data contain one for every case. */
    private function statusId(InvoiceStatus $status): int
    {
        $query = Status::query();
        $query->where('slug', $status->value);
        $id = $query->value('id');
        if ($id === null) {
            throw new LogicException(
                'Invoice status ' . $status->value . ' is missing from the statuses table',
            );
        }

        return (int) $id;
    }
}
