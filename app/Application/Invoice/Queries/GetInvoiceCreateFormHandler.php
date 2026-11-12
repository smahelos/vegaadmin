<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;

final class GetInvoiceCreateFormHandler
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
        private readonly TaxDtoReadRepositoryInterface $taxes,
        private readonly InvoiceCreateFieldSetFactory $fieldSets,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(GetInvoiceCreateForm $query, ReadScope $scope): InvoiceFormDTO
    {
        $paymentMethods = $this->paymentMethods->getAllForDropdown();
        $now = $this->clock->now();

        return new InvoiceFormDTO(
            $this->fieldSets->build($paymentMethods),
            [
                'paymentMethods' => $paymentMethods,
                'invoiceProducts' => [],
                'taxRates' => $this->taxes->getDphRatesForDropdown(),
                'suggestedNumber' => $this->invoiceService->generateInvoiceNumber(
                    $scope,
                    (int) $now->format('Y'),
                ),
                'userLoggedIn' => true,
            ],
        );
    }
}
