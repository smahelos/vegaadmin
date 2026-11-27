<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Services\DocumentLogoService;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use LogicException;

/**
 * The parties are the stored ones, as the copied invoice prints them; the VAT payer flag is the business's current
 * one, as on any draft.
 */
final class CopyInvoiceHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly DocumentLogoService $logos,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    public function handle(CopyInvoice $command, WriteScope $scope): InvoiceDTO
    {
        $source = $this->invoices->findById($command->id, $scope->read)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $parties = $source->parties;
        $business = $this->businesses->findById($parties->businessId, $scope->read)
            ?? throw new BusinessNotFoundException($parties->businessId);
        $data = new InvoiceWriteData(
            constantSymbol: $source->constantSymbol,
            specificSymbol: $source->specificSymbol,
            dueIn: $source->dueIn,
            currency: $source->totalAmount->currency,
            paymentMethodId: $source->paymentMethodId ?? throw new LogicException('A stored invoice has a payment method'),
            text: $source->text,
            template: $source->template,
            language: $source->language,
        );
        $lines = [];
        foreach ($source->items as $item) {
            $lines[] = new InvoiceProductWriteData(
                name: $item->name,
                quantity: $item->quantity,
                unit: $item->unit,
                price: $item->price,
                taxRate: $item->taxRate,
                productId: $item->productId,
                category: $item->category,
                description: $item->description,
            );
        }

        return $this->logos->saveWithLogo(
            null,
            false,
            $parties->supplierLogo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transactions->transaction(
                fn (): InvoiceDTO => $this->invoiceService->createInvoice(
                    $scope,
                    $data,
                    $parties->withSupplierLogo($logo),
                    $lines,
                    $business->invoicing->vatPayer,
                ),
            ),
        );
    }
}
