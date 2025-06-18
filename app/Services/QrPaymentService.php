<?php

namespace App\Services;

use App\Models\Invoice;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class QrPaymentService
{
    /**
     * Generate QR code for invoice payment in base64 format
     * 
     * @param mixed $invoice Invoice object or standard class with invoice data
     * @return string|null Base64 encoded QR code image
     */
    public function generateQrCodeBase64($invoice): ?string
    {
        try {
            // Check required data
            if (
                empty($this->safeGetProperty($invoice, 'invoice_vs')) || 
                empty($this->safeGetProperty($invoice, 'payment_amount'))
            ) {
                return null;
            }
            
            // Safely get bank account information
            $accountNumber = $this->safeGetProperty($invoice, 'account_number');
            $bankCode = $this->safeGetProperty($invoice, 'bank_code');
            $iban = $this->safeGetProperty($invoice, 'iban');
            
            // Check supplier data if invoice doesn't have account information
            if ((empty($accountNumber) || empty($bankCode)) && empty($iban) && isset($invoice->supplier)) {
                $accountNumber = $invoice->supplier->account_number ?? '';
                $bankCode = $invoice->supplier->bank_code ?? '';
                $iban = $invoice->supplier->iban ?? '';
            }
            
            // If we don't have bank account information, don't generate QR code
            if ((empty($accountNumber) || empty($bankCode)) && empty($iban)) {
                return null;
            }
        
            // Generate QR code string
            $qrString = $this->generateQrString($invoice);
            
            // Generate and return QR code as base64
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode(QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($qrString));
            
            return $qrCodeBase64;
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage(), [
                'invoice_vs' => $this->safeGetProperty($invoice, 'invoice_vs'),
                'payment_amount' => $this->safeGetProperty($invoice, 'payment_amount')
            ]);
            return null;
        }
    }

    /**
     * Safely get property from object or array without triggering errors
     *
     * @param mixed $object Invoice object or array
     * @param string $property Property name
     * @return mixed Property value or null
     */
    private function safeGetProperty($object, $property): mixed
    {
        // Check object properties
        if (is_object($object)) {
            // 1. Check supplier property directly
            if (isset($object->$property) && $object->$property !== '') {
                return $object->$property;
            }

            // 2. Check supplier property under supplier
            // Check if supplier is an object and has the property
            if (isset($object->supplier) && isset($object->supplier->$property) && $object->supplier->$property !== '') {
                return $object->supplier->$property;
            }
            
            // 3. Check supplier property under supplier_PROPERTY
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
            
            // Check supplier property under supplier_PROPERTY
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
    
    /**
     * Check if invoice has all required information for QR payment
     *
     * @param Invoice $invoice
     * @return bool
     */
    public function hasRequiredPaymentInfo(Invoice $invoice): bool
    {
        // Check basic invoice data
        if (empty($invoice->payment_amount) || empty($invoice->payment_currency) || empty($invoice->invoice_vs)) {
            return false;
        }
        
        // Check if invoice has bank account information
        if ((!empty($invoice->account_number) && !empty($invoice->bank_code)) || 
            (!empty($invoice->iban))) {
            return true;
        }
        
        // Check if supplier has bank account information
        if ($invoice->supplier) {
            return (!empty($invoice->supplier->account_number) && !empty($invoice->supplier->bank_code)) || 
                !empty($invoice->supplier->iban);
        }
        
        return false;
    }

    /**
     * Generate QR payment string according to Czech Banking Association standard
     * 
     * @param mixed $invoice Invoice object or standard class with invoice data
     * @return string QR payment string
     */
    private function generateQrString($invoice): string
    {
        
        // First determine account number - IBAN has priority
        $accountNumber = '';
        
        if (!empty($invoice->iban)) {
            $accountNumber = $invoice->iban;
        } elseif (!empty($invoice->account_number) && !empty($invoice->bank_code)) {
            // For CZ accounts you can use format: CZkk+aaaaaaaaaaaaaa+mmmm
            $accountNumber = sprintf(
                'CZ00%s%s', 
                str_pad($invoice->account_number, 16, '0', STR_PAD_LEFT),
                str_pad($invoice->bank_code, 4, '0', STR_PAD_LEFT)
            );
        } elseif ($invoice->supplier) {
            if (!empty($invoice->supplier->iban)) {
                $accountNumber = $invoice->supplier->iban;
            } elseif (!empty($invoice->supplier->account_number) && !empty($invoice->supplier->bank_code)) {
                // For CZ accounts you can use format: CZkk+aaaaaaaaaaaaaa+mmmm
                $accountNumber = sprintf(
                    'CZ00%s%s', 
                    str_pad($invoice->supplier->account_number, 16, '0', STR_PAD_LEFT),
                    str_pad($invoice->supplier->bank_code, 4, '0', STR_PAD_LEFT)
                );
            }
        }
        
        // Clean IBAN from spaces and unwanted characters
        $accountNumber = preg_replace('/\s+/', '', $accountNumber);
        
        // Format amount with 2 decimal places
        $amount = number_format($invoice->payment_amount, 2, '.', '');
        
        // Build basic QR payment string
        $parts = [
            'SPD*1.0',  // QR Payment standard version 1.0
            'ACC:' . $accountNumber,
            'AM:' . $amount,
            'CC:' . $invoice->payment_currency,
        ];
        
        // Add variable symbol
        if (!empty($invoice->invoice_vs)) {
            $parts[] = 'X-VS:' . $invoice->invoice_vs;
        }
        
        // Add constant symbol if exists
        if (!empty($invoice->invoice_ks)) {
            $parts[] = 'X-KS:' . $invoice->invoice_ks;
        }
        
        // Add specific symbol if exists
        if (!empty($invoice->invoice_ss)) {
            $parts[] = 'X-SS:' . $invoice->invoice_ss;
        }
        
        // Message for recipient - invoice number
        $parts[] = 'MSG:FAKTURA' . $invoice->invoice_vs;
        
        // Add payment recipient name (supplier)
        $recipientName = '';
        if (!empty($invoice->name)) {
            $recipientName = $invoice->name;
        } elseif ($invoice->supplier && !empty($invoice->supplier->name)) {
            $recipientName = $invoice->supplier->name;
        }
        
        if (!empty($recipientName)) {
            // Limit to 35 characters per standard
            $parts[] = 'RN:' . substr($recipientName, 0, 35);
        }
        
        // Add due date if available
        // due_date is counted by due_in and issue_date
        if (!empty($invoice->issue_date) && !empty($invoice->due_in)) {
            $issueDate = new \DateTime($invoice->issue_date);
            $dueDate = clone $issueDate;
            $dueDate->modify('+' . $invoice->due_in . ' days');
            $parts[] = 'DT:' . $dueDate->format('Ymd');
        }
        
        return implode('*', $parts);
    }
}
