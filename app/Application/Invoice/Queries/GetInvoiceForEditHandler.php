<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;

final class GetInvoiceForEditHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
        private readonly TaxDtoReadRepositoryInterface $taxes,
        private readonly InvoiceCreateFieldSetFactory $fieldSets,
    ) {}

    public function handle(GetInvoiceForEdit $query, ReadScope $scope): InvoiceFormDTO
    {
        $invoice = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $paymentMethods = $this->paymentMethods->getAllForDropdown();

        return new InvoiceFormDTO(
            fields: $this->fieldSets->build($paymentMethods),
            paymentMethods: $paymentMethods,
            taxRates: $this->taxes->getDphRatesForDropdown(),
            userLoggedIn: true,
            invoice: $invoice,
        );
    }
}
