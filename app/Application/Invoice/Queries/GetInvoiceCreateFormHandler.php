<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;

final class GetInvoiceCreateFormHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
        private readonly TaxDtoReadRepositoryInterface $taxes,
        private readonly InvoiceCreateFieldSetFactory $fieldSets,
    ) {}

    public function handle(GetInvoiceCreateForm $query, ReadScope $scope): InvoiceFormDTO
    {
        $paymentMethods = $this->paymentMethods->getAllForDropdown();
        // The draft's document language starts as its supplier's (D-050, point 6), the business the picker selects
        $businessId = $query->businessId ?? $this->businesses->findDefaultId($scope);
        $business = $businessId === null ? null : $this->businesses->findById($businessId, $scope);

        return new InvoiceFormDTO(
            fields: $this->fieldSets->build($paymentMethods, false),
            paymentMethods: $paymentMethods,
            taxRates: $this->taxes->getDphRatesForDropdown(),
            userLoggedIn: true,
            documentLanguage: $business?->invoicing->documentLanguage,
        );
    }
}
