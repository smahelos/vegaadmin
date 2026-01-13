<?php

namespace App\Application\Payment\Mappers;

use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Invoice;

/**
 * Maps between Invoice models and QrPaymentPayload DTOs.
 * This isolates application layer from domain implementation details.
 */
class QrPaymentPayloadMapper
{
    /**
     * Convert Invoice model to QrPaymentPayload DTO
     *
     * @param Invoice $invoice
     * @return QrPaymentPayload
     */
    public function fromInvoice(Invoice $invoice): QrPaymentPayload
    {
        // Get payment amount as Money value object
        $amount = Money::fromString(
            (string) $invoice->payment_amount,
            $invoice->payment_currency ?? 'CZK'
        );

        $supplierCountry = null;
        if ($invoice->relationLoaded('supplier') && $invoice->supplier) {
            $supplierCountry = $invoice->supplier->country;
        }

        return new QrPaymentPayload(
            variableSymbol: $invoice->invoice_vs,
            amount: $amount,
            accountNumber: $invoice->account_number,
            bankCode: $invoice->bank_code,
            iban: $invoice->iban,
            countryCode: $supplierCountry ?? $invoice->country_code ?? null,
            message: $invoice->invoice_text,
        );
    }

    /**
     * Convert generic object/array to QrPaymentPayload DTO
     * This maintains compatibility with guest invoices and other data sources
     *
     * @param mixed $data Invoice-like object or array
     * @return QrPaymentPayload
     */
    public function fromArray($data): QrPaymentPayload
    {
        // Safely extract properties from object or array
        $amount = $this->safeGetProperty($data, 'payment_amount') ?? 0;
        $currency = $this->safeGetProperty($data, 'payment_currency') ?? 'CZK';
        
        // Create Money value object
        $money = Money::fromString((string) $amount, $currency);

        return new QrPaymentPayload(
            variableSymbol: $this->safeGetProperty($data, 'invoice_vs'),
            amount: $money,
            accountNumber: $this->safeGetProperty($data, 'account_number'),
            bankCode: $this->safeGetProperty($data, 'bank_code'),
            iban: $this->safeGetProperty($data, 'iban'),
            countryCode: $this->safeGetProperty($data, 'country_code'),
            message: $this->safeGetProperty($data, 'invoice_text'),
        );
    }

    /**
     * Safely get property from object or array without triggering errors
     *
     * @param mixed $object Invoice object or array
     * @param string $property Property name
     * @return mixed Property value or null
     */
    private function safeGetProperty($object, string $property): mixed
    {
        // Check object properties
        if (is_object($object)) {
            // 1. Check property directly
            if (isset($object->$property) && $object->$property !== '') {
                return $object->$property;
            }

            // 2. Check property under supplier
            if (isset($object->supplier) && isset($object->supplier->$property) && $object->supplier->$property !== '') {
                return $object->supplier->$property;
            }
            
            // 3. Check property under supplier_PROPERTY pattern
            $supplierProperty = "supplier_" . $property;
            if (isset($object->$supplierProperty) && $object->$supplierProperty !== '') {
                return $object->$supplierProperty;
            }
        }
        
        // Check array keys
        if (is_array($object)) {
            if (isset($object[$property]) && $object[$property] !== '') {
                return $object[$property];
            }
            
            // Check supplier property under supplier_PROPERTY pattern
            $supplierProperty = "supplier_" . $property;
            if (isset($object[$supplierProperty]) && $object[$supplierProperty] !== '') {
                return $object[$supplierProperty];
            }
        }
        
        // Special case - return numeric values including 0
        if ((is_object($object) && isset($object->$property) && is_numeric($object->$property)) || 
            (is_array($object) && isset($object[$property]) && is_numeric($object[$property]))) {
            return is_object($object) ? $object->$property : $object[$property];
        }
        
        return null;
    }
}
