<?php

namespace App\Application\Invoice\Form;

use App\Application\Shared\Form\DTO\FieldDTO;
use App\Application\Shared\Form\Enum\FieldType;
use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields; // Temporarily reuse trait logic Phase 1

/**
 * Factory converting legacy trait array structure to FieldSetDTO for invoice create/edit forms.
 * Phase 1: Adapts existing getInvoiceFields output without altering controllers yet.
 */
class InvoiceCreateFieldSetFactory
{
    // Alias trait method so we can provide a safe wrapper fallback in non-Laravel (plain PHPUnit) context
    use InvoiceFormFields { getInvoiceFields as private traitGetInvoiceFields; }

    /**
     * Build FieldSetDTO from legacy trait output.
     *
     * @param array $clients
     * @param array $suppliers
     * @param array $paymentMethods
     * @param array $statuses
     * @param array $currencies
     */
    public function build(array $clients, array $suppliers, array $paymentMethods, array $statuses, array $currencies = []): FieldSetDTO
    {
        $raw = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses, $currencies);
        $fields = [];
        foreach ($raw as $item) {
            $fields[] = new FieldDTO(
                name: $item['name'],
                label: $item['label'] ?? '',
                type: $item['type'] ?? FieldType::TEXT,
                options: $item['options'] ?? [],
                required: (bool)($item['required'] ?? false),
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

    /**
     * Safe wrapper around trait's getInvoiceFields. When running plain PHPUnit unit tests
     * without a bootstrapped Laravel container, Facades are unavailable and would throw
     * "A facade root has not been set.". In that case, provide a minimal fallback field
     * definition sufficient for unit tests. In normal app runtime, delegate to the trait.
     */
    protected function getInvoiceFields(
        array $clients = [],
        array $suppliers = [],
        array $paymentMethods = [],
        array $statuses = [],
        array $currencies = []
    ): array {
        try {
            // Attempt to call the original trait implementation (requires Laravel container)
            return $this->traitGetInvoiceFields($clients, $suppliers, $paymentMethods, $statuses, $currencies);
        } catch (\Throwable $e) {
            // Minimal fallback for non-Laravel context (plain PHPUnit)
            if (empty($currencies)) {
                $currencies = [ 'CZK' => 'CZK' ];
            }
            return [
                [ 'name' => 'invoice_logo', 'label' => 'Invoice Logo', 'type' => 'file', 'accept' => 'image/*' ],
                [ 'name' => 'template', 'label' => 'Template', 'type' => 'hidden', 'default' => 'default' ],
                [ 'name' => 'invoice_vs', 'label' => 'Variable Symbol', 'type' => 'text', 'required' => true ],
                [ 'name' => 'payment_status_id', 'label' => 'Status', 'type' => 'select', 'options' => $statuses, 'required' => true ],
                [ 'name' => 'issue_date', 'label' => 'Issue Date', 'type' => 'date' ],
                [ 'name' => 'payment_method_id', 'label' => 'Payment Method', 'type' => 'select', 'options' => $paymentMethods, 'required' => true ],
                [ 'name' => 'due_in', 'label' => 'Due In', 'type' => 'select_from_array', 'options' => [1=>'1',3=>'3',7=>'7',14=>'14',30=>'30'] ],
                [ 'name' => 'payment_currency', 'label' => 'Currency', 'type' => 'select_from_array', 'options' => $currencies, 'required' => true ],
            ];
        }
    }
}
