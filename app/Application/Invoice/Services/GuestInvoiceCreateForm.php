<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;

/**
 * The guest invoice form reads only reference data, the same for anyone, so it runs outside the gate
 * (rules/application.md).
 */
final class GuestInvoiceCreateForm
{
    public function __construct(
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
        private readonly TaxDtoReadRepositoryInterface $taxes,
        private readonly InvoiceCreateFieldSetFactory $fieldSets,
    ) {}

    public function read(): InvoiceFormDTO
    {
        $paymentMethods = $this->paymentMethods->getAllForDropdown();
        // A guest has no numbering history: the first number of the year
        $number = InvoiceNumber::generateNext();

        return new InvoiceFormDTO(
            $this->fieldSets->build($paymentMethods),
            [
                'paymentMethods' => $paymentMethods,
                'taxRates' => $this->taxes->getDphRatesForDropdown(),
                'suggestedNumber' => $number->getNumber(),
                'invoiceProducts' => [],
                'userLoggedIn' => false,
            ],
        );
    }
}
