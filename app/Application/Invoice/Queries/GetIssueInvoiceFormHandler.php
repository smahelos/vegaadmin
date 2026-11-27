<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\IssueInvoiceFormDTO;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceIssuedException;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;

final class GetIssueInvoiceFormHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoiceService $invoiceService,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(GetIssueInvoiceForm $query, ReadScope $scope): IssueInvoiceFormDTO
    {
        $invoice = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        if ($invoice->issue !== null) {
            throw new InvoiceIssuedException('An issued invoice cannot be issued again');
        }
        $today = $this->clock->now();

        return new IssueInvoiceFormDTO(
            $this->invoiceService->nextNumber(
                $scope,
                (int) $today->format('Y'),
            ),
            $today,
        );
    }
}
