<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Domain\Invoice\DTO\InvoiceDTO;

/**
 * The invoice form: create (also for a guest) and edit, which also carries the stored invoice.
 */
final readonly class InvoiceFormDTO
{
    /**
     * @param  array<int, string>  $paymentMethods  Payment method code by id
     * @param  array<int, string>  $taxRates  VAT rate in percent by id
     */
    public function __construct(
        public FieldSetDTO $fields,
        public array $paymentMethods,
        public array $taxRates,
        public bool $userLoggedIn,
        public ?string $suggestedNumber = null,
        public ?InvoiceDTO $invoice = null,
    ) {}
}
