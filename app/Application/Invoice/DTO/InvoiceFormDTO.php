<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Domain\Shared\Translation\Enums\Language;

/**
 * The invoice form: create (also for a guest) and edit.
 */
final readonly class InvoiceFormDTO
{
    /**
     * @param  array<int, string>  $paymentMethods  Payment method code by id
     * @param  array<int, string>  $taxRates  VAT rate in percent by id
     * @param  string|null  $suggestedNumber  Of the guest invoice; a draft gets its number at issue
     * @param  Language|null  $documentLanguage  Of the default business, prefilled on a new draft
     */
    public function __construct(
        public FieldSetDTO $fields,
        public array $paymentMethods,
        public array $taxRates,
        public bool $userLoggedIn,
        public ?string $suggestedNumber = null,
        public ?Language $documentLanguage = null,
    ) {}
}
