<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\Invoice;

/**
 * Eloquent implementation of Invoice DTO repositories.
 *
 * Read methods return InvoiceDTO instances using EloquentInvoiceMapper.
 * Write methods accept InvoiceWriteData and return InvoiceDTO after persistence.
 */
class EloquentInvoiceDtoRepository implements InvoiceDtoReadRepositoryInterface, InvoiceDtoWriteRepositoryInterface
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

    public function create(InvoiceWriteData $writeData): InvoiceDTO
    {
        // Delegate attribute mapping to mapper to keep logic centralized
        $attrs = $this->mapper->toModelAttributes($writeData);

        $model = $this->invoiceRepository->create($attrs);

        return $this->mapper->toDto($model);
    }

    public function update(InvoiceId $id, InvoiceWriteData $writeData): InvoiceDTO
    {
        // Delegate attribute mapping to mapper to keep logic centralized
        $attrs = $this->mapper->toModelAttributes($writeData);

        $model = $this->invoiceRepository->updateById($id->getValue(), $attrs);

        return $this->mapper->toDto($model);
    }

    public function setTemplate(InvoiceId $id, string $template): bool
    {
        return $this->invoiceRepository->setTemplate($id->getValue(), $template);
    }

    public function linkContact(InvoiceId $id, ContactId $contactId): void
    {
        Invoice::query()->whereKey($id->getValue())->update(['contact_id' => $contactId->value]);
    }

    public function findLastInvoiceNumber(UserId $userId, int $year): ?string
    {
        // Highest sequence among numbers in the standard format; sorting the strings would put
        // 20259999 after 202510000, and non-standard numbers must not affect the sequence
        $last = null;
        foreach ($this->invoiceRepository->findInvoiceNumbersForYear($userId->toInt(), $year) as $number) {
            try {
                $candidate = new InvoiceNumber($number);
            } catch (\InvalidArgumentException) {
                continue;
            }
            if ($candidate->getYear() === $year && ($last === null || $candidate->isGreaterThan($last))) {
                $last = $candidate;
            }
        }

        return $last?->getNumber();
    }
}
