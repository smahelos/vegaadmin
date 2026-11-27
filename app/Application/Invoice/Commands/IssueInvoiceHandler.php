<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Domain\Invoice\Contracts\DocumentPdfStorageInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Invoice\ValueObjects\StoredPdf;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

/**
 * The monthly invoice quota counts issued invoices, so it is checked here (D-047, D-050). The supplier's
 * invoicing settings of this moment are copied to the invoice. The PDF carries the number, so it is rendered and
 * stored inside the transaction that writes the rows: issuing succeeds completely or not at all (D-050, point 8).
 */
final class IssueInvoiceHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceDtoWriteRepositoryInterface $invoices,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly EntityLimit $limits,
        private readonly ClockInterface $clock,
        private readonly InvoicePrintDataBuilderInterface $printData,
        private readonly InvoicePdfRendererInterface $renderer,
        private readonly DocumentPdfStorageInterface $storage,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    public function handle(IssueInvoice $command, WriteScope $scope): InvoiceDTO
    {
        return $this->transactions->transaction(
            function () use ($command, $scope): InvoiceDTO {
                $this->limits->assertCanCreate($command->owner, LimitedEntity::INVOICE);
                $draft = $this->invoiceService->draftForUpdate($scope, $command->id);
                $businessId = $draft->parties->businessId;
                $business = $this->businesses->findById($businessId, $scope->read)
                    ?? throw new BusinessNotFoundException($businessId);
                $issued = $this->invoiceService->issueInvoice(
                    $scope,
                    $command->id,
                    $command->number,
                    $command->issueDate,
                    $command->taxPointDate,
                    $business->invoicing,
                    $this->clock->now(),
                );
                $printable = $this->printData->build(
                    $issued,
                    $business->invoicing,
                );
                $content = $this->renderer->render(
                    $printable,
                    $issued->language->value,
                );
                $path = $this->storage->store(
                    $scope->owner,
                    $command->id,
                    $content,
                );
                $stored = false;
                try {
                    $invoice = $this->invoices->storePdf(
                        $command->id,
                        $scope,
                        StoredPdf::of($path, $content),
                    );
                    $stored = true;

                    return $invoice;
                } finally {
                    // A throw from this callback rolls the rows back, so the file goes too. A failure while
                    // committing is not cleaned up: the rows may have been committed, and the issued invoice must
                    // not lose its only copy; a file left behind is overwritten when the draft is issued again
                    if (!$stored) {
                        $this->storage->delete($path);
                    }
                }
            },
        );
    }
}
