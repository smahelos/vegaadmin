<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use Illuminate\Support\Collection;

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
        private readonly EloquentInvoiceMapper $mapper
    ) {
    }

    public function findByIdAny(InvoiceId $id): ?InvoiceDTO
    {
        $model = $this->invoiceRepository->findByIdAny($id->getValue());
        return $model ? $this->mapper->toDto($model) : null;
    }

    public function findByIdForUser(InvoiceId $id, UserId $userId): ?InvoiceDTO
    {
        $model = $this->invoiceRepository->findForUser($userId->toInt(), $id->getValue());
        return $model ? $this->mapper->toDto($model) : null;
    }

    public function getInvoicesForDropdown(UserId $userId): array
    {
        return $this->invoiceRepository->getInvoicesForDropdown($userId->toInt());
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

    public function delete(InvoiceId $id): void
    {
        $this->invoiceRepository->deleteById($id->getValue());
    }
    public function markAsPaid(InvoiceId $id, int $paidStatusId): bool
    {
        return $this->invoiceRepository->markAsPaid($id->getValue(), $paidStatusId);
    }

    public function setTemplate(InvoiceId $id, string $template): bool
    {
        return $this->invoiceRepository->setTemplate($id->getValue(), $template);
    }

    public function changeStatus(InvoiceId $id, int $statusId): bool
    {
        return $this->invoiceRepository->changeStatus($id->getValue(), $statusId);
    }

    public function findLastInvoiceNumber(UserId $userId, int $year): ?string
    {
        $invoice = $this->invoiceRepository->findLastInvoiceForYear($userId->toInt(), $year);
        return $invoice ? $invoice->invoice_vs : null;
    }

    public function existsWithNumber(UserId $userId, string $number): bool
    {
        return $this->invoiceRepository->existsWithNumber($userId->toInt(), $number);
    }

    public function findOverdueForUser(UserId $userId): array
    {
        $models = $this->invoiceRepository->findOverdueForUser($userId->toInt());
        return array_map(fn($m) => $this->mapper->toDto($m), $models);
    }

    public function findUnpaidForUser(UserId $userId): array
    {
        $models = $this->invoiceRepository->findUnpaidForUser($userId->toInt());
        return array_map(fn($m) => $this->mapper->toDto($m), $models);
    }

    public function getUserInvoiceCount(UserId $userId): int
    {
        return $this->invoiceRepository->getUserInvoiceCount($userId->toInt());
    }
}
