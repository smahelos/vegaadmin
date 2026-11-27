<?php

namespace App\Application\Invoice\Form;

use App\Application\Shared\Form\DTO\FieldDTO;
use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Application\Shared\Form\Enum\FieldType;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields; // Temporarily reuse trait logic Phase 1

/**
 * Factory converting legacy trait array structure to FieldSetDTO for invoice create/edit forms.
 * Phase 1: Adapts existing getInvoiceFields output without altering controllers yet.
 */
class InvoiceCreateFieldSetFactory
{
    use InvoiceFormFields;

    /**
     * Build FieldSetDTO from legacy trait output.
     */
    public function build(array $paymentMethods, bool $guest): FieldSetDTO
    {
        $raw = $this->getInvoiceFields($paymentMethods, $guest);
        $fields = [];
        foreach ($raw as $item) {
            $fields[] = new FieldDTO(
                name: $item['name'],
                label: $item['label'] ?? '',
                type: $item['type'] ?? FieldType::TEXT,
                options: $item['options'] ?? [],
                required: (bool) ($item['required'] ?? false),
                hint: $item['hint'] ?? '',
                placeholder: $item['placeholder'] ?? '',
                default: $item['default'] ?? null,
                entity: $item['entity'] ?? null,
                attribute: $item['attribute'] ?? null,
                model: $item['model'] ?? null,
                accept: $item['accept'] ?? null,
            );
        }

        return new FieldSetDTO($fields);
    }
}
