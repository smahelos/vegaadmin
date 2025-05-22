<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class InvoiceService
{
    /**
     * Generate next available invoice number
     * 
     * @return string
     */
    public function getNextInvoiceNumber(): string
    {
        $lastInvoice = Invoice::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->first();
    
        if ($lastInvoice) {
            // Extract the last number from the invoice_vs field
            // Assuming the format is YYYYXXXX, where YYYY is the year and XXXX is the number
            preg_match('/(\d+)$/', $lastInvoice->invoice_vs, $matches);
            $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
            return sprintf('%04d', $nextNumber);
        }
        
        return date('Y') . '0001';
    }

    /**
     * Get item units for invoice items
     *
     * @return array
     */
    public function getItemUnits(): array
    {
        return [
            'hours' => __('invoices.units.hours'),
            'days' => __('invoices.units.days'),
            'pieces' => __('invoices.units.pieces'),
            'kilograms' => __('invoices.units.kilograms'),
            'grams' => __('invoices.units.grams'),
            'liters' => __('invoices.units.liters'),
            'meters' => __('invoices.units.meters'),
            'cubic_meters' => __('invoices.units.cubic_meters'),
            'centimeters' => __('invoices.units.centimeters'),
            'cubic_centimeters' => __('invoices.units.cubic_centimeters'),
            'milliliters' => __('invoices.units.milliliters'),
        ];
    }

    /**
     * Save products to an invoice
     *
     * @param Invoice $invoice The invoice
     * @param array $products Array of products data
     * @return void
     */
    public function saveInvoiceProducts(Invoice $invoice, array $products): void
    {
        foreach ($products as $product) {
            // Check if this is a custom product (no product_id or explicitly marked)
            $isCustom = !isset($product['product_id']) || empty($product['product_id']) || 
                       (isset($product['is_custom_product']) && $product['is_custom_product']);
            
            // Convert to numbers for safety
            $quantity = floatval($product['quantity'] ?? 1);
            $price = floatval($product['price'] ?? 0);
            $taxRate = floatval($product['tax_rate'] ?? 21);
            
            $taxAmount = ($quantity * $price * $taxRate) / 100;
            $totalPrice = $quantity * $price * (1 + $taxRate / 100);
            
            $invoiceProduct = new InvoiceProduct([
                'invoice_id' => $invoice->id,
                'product_id' => $isCustom ? null : $product['product_id'],
                'name' => $product['name'] ?? '',
                'quantity' => $quantity,
                'price' => $price,
                'currency' => $product['currency'] ?? 'CZK',
                'unit' => $product['unit'] ?? 'ks',
                'category' => $product['category'] ?? null,
                'description' => $product['description'] ?? null,
                'is_custom_product' => $isCustom,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_price' => $totalPrice,
            ]);
            
            $invoiceProduct->save();
        }
    }

    /**
     * Store temporary invoice for guest users
     * 
     * @param array $data Invoice data
     * @return string Generated token
     */
    public function storeTemporaryInvoice(array $data): string
    {
        // Create token to identify the invoice
        $token = Str::random(64);
        
        // Save invoice data to cache with expiration in 10 minutes
        Cache::put('invoice_data_' . $token, $data, now()->addMinutes(10));
        
        return $token;
    }

    /**
     * Get temporary invoice data by token
     * 
     * @param string $token
     * @return array|null
     */
    public function getTemporaryInvoiceByToken(string $token)
    {
        return Cache::get('invoice_data_' . $token);
    }

    /**
     * Delete temporary invoice
     * 
     * @param string $token
     * @return bool
     */
    public function deleteTemporaryInvoice(string $token): bool
    {
        return Cache::forget('invoice_data_' . $token);
    }

    /**
     * Mark invoice as paid
     *
     * @param int $id Invoice ID
     * @return bool
     */
    public function markInvoiceAsPaid(int $id): bool
    {
        try {
            $invoice = Invoice::where('user_id', Auth::id())->findOrFail($id);
            
            // Get status ID for "paid"
            $paidStatusId = Status::where('slug', 'paid')->first()->id ?? null;
            
            if (!$paidStatusId) {
                return false;
            }
            
            // Update invoice status
            $invoice->update([
                'payment_status_id' => $paidStatusId
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error marking invoice as paid: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure object has all required properties (with default values)
     *
     * @param \stdClass $object
     * @param array $properties
     * @return void
     */
    public function ensureObjectProperties(\stdClass $object, array $properties): void
    {
        foreach ($properties as $property) {
            if (!property_exists($object, $property)) {
                if ($property === 'due_in') {
                    $object->$property = 14;
                } elseif ($property === 'payment_method_id' || $property === 'payment_status_id') {
                    $object->$property = 1;
                } elseif ($property === 'payment_amount') {
                    $object->$property = 0;
                } else {
                    $object->$property = '';
                }
            } else if ($property === 'due_in' || $property === 'payment_method_id' || $property === 'payment_status_id') {
                $object->$property = (int)$object->$property;
            } else if ($property === 'payment_amount') {
                $object->$property = (float)$object->$property;
            }
        }
    }
}
