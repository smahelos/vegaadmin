<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;

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
        private readonly ClockInterface $clock,
    ) {}

    public function read(): InvoiceFormDTO
    {
        $paymentMethods = $this->paymentMethods->getAllForDropdown();
        // A guest has no numbering history: the first number of the current year (D-050, point 11)
        $now = $this->clock->now();
        $number = InvoiceNumber::generateNext(
            (int) $now->format('Y'),
            null,
        );

        return new InvoiceFormDTO(
            fields: $this->fieldSets->build($paymentMethods, true),
            paymentMethods: $paymentMethods,
            taxRates: $this->taxes->getDphRatesForDropdown(),
            userLoggedIn: false,
            suggestedNumber: $number->value,
        );
    }
}
