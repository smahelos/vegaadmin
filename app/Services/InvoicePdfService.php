<?php

namespace App\Services;

use App\Contracts\InvoicePdfServiceInterface;
use App\Contracts\InvoiceServiceInterface;
use App\Contracts\QrPaymentServiceInterface;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class InvoicePdfService implements InvoicePdfServiceInterface
{
    /**
     * @var LocaleService
     */
    protected $localeService;
    
    /**
     * @var QrPaymentServiceInterface
     */
    protected $qrPaymentService;
    
    /**
     * @var InvoiceServiceInterface
     */
    protected $invoiceService;

    /**
     * Constructor
     * 
     * @param LocaleService $localeService
     * @param QrPaymentServiceInterface $qrPaymentService
     * @param InvoiceServiceInterface $invoiceService
     */
    public function __construct(
        LocaleService $localeService,
        QrPaymentServiceInterface $qrPaymentService,
        InvoiceServiceInterface $invoiceService
    ) {
        $this->localeService = $localeService;
        $this->qrPaymentService = $qrPaymentService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Generate PDF from invoice
     * 
     * @param Invoice $invoice
     * @param string|null $requestLocale
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf(Invoice $invoice, ?string $requestLocale = null)
    {
        // Set locale based on request or default
        $userLocale = Session::get('locale');
        
        $locale = $this->localeService->determineLocale($requestLocale, $userLocale);
        $this->localeService->setLocale($locale);

        // Data for QR code
        $qrData = [
            'invoice_vs' => $invoice->invoice_vs,
            'payment_amount' => (float)$invoice->payment_amount,
            'payment_currency' => $invoice->payment_currency,
        ];

        // Set bank account details for QR code
        $supplier = $invoice->supplier;
        if ($supplier) {
            if (!empty($supplier->account_number) && !empty($supplier->bank_code)) {
                $qrData['account_number'] = $supplier->account_number;
                $qrData['bank_code'] = $supplier->bank_code;
            }
            if (!empty($supplier->iban)) {
                $qrData['iban'] = $supplier->iban;
            }
        }
        
        // If no bank details in supplier, check invoice
        if (empty($qrData['account_number']) && !empty($invoice->account_number) && !empty($invoice->bank_code)) {
            $qrData['account_number'] = $invoice->account_number;
            $qrData['bank_code'] = $invoice->bank_code;
        }
        
        if (empty($qrData['iban']) && !empty($invoice->iban)) {
            $qrData['iban'] = $invoice->iban;
        }
        
        // Create a clone of the invoice object to avoid modifying the original
        $qrInvoice = clone $invoice;
        
        // Add QR code data to the invoice object
        foreach ($qrData as $key => $value) {
            $qrInvoice->$key = $value;
        }
        
        // Generate QR code with updated data
        $qrCodeBase64 = null;
        try {
            $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($qrInvoice);
        } catch (\Exception $e) {
            Log::error('Error while generating QR code: ' . $e->getMessage());
        }
        
        // Set data for PDF generation
        $data = [
            'invoice' => $invoice,
            'user' => Auth::user(),
            'client' => $invoice->client,
            'supplier' => $invoice->supplier,
            'paymentMethod' => $invoice->paymentMethod,
            'qrCode' => $qrCodeBase64,
            'hasQrCode' => !empty($qrCodeBase64),
            'locale' => $locale
        ];

        // Generate PDF
        return Pdf::loadView('pdfs.invoice', $data);
    }

    /**
     * Generate PDF from temporary invoice data
     * 
     * @param array $invoiceData
     * @param string|null $requestLocale
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdfFromData(array $invoiceData, ?string $requestLocale = null)
    {
        // Set locale based on request or data
        $dataLocale = $invoiceData['lang'] ?? null;
        $locale = $this->localeService->determineLocale($requestLocale, $dataLocale);
        $this->localeService->setLocale($locale);

        // Create temporary invoice object
        $invoice = new \stdClass();
        foreach ($invoiceData as $key => $value) {
            // Skip complex data
            if ($key !== 'invoice-products') {
                $invoice->$key = $value;
            }
        }

        // Calculate due_date if needed
        if (isset($invoice->issue_date) && isset($invoice->due_in)) {
            $issueDate = new \DateTime($invoice->issue_date);
            $issueDate->modify('+' . (int)$invoice->due_in . ' days');
            $invoice->due_date = $issueDate->format('Y-m-d');
        }

        // Convert due_in to integer
        if (isset($invoice->due_in)) {
            $invoice->due_in = (int)$invoice->due_in;
        }

        // Ensure required properties
        $this->invoiceService->ensureObjectProperties($invoice, [
            'payment_method_id', 'due_in', 'payment_status_id', 'payment_currency',
            'invoice_vs', 'invoice_ks', 'invoice_ss', 'issue_date', 'payment_amount',
            'tax_point_date', 'invoice_text', 'due_date'
        ]);

        // Create client object
        $client = new \stdClass();
        $client->id = null;
        $client->name = $invoiceData['client_name'] ?? __('invoices.placeholders.unnamed_client');
        $client->street = $invoiceData['client_street'] ?? '';
        $client->city = $invoiceData['client_city'] ?? '';
        $client->zip = $invoiceData['client_zip'] ?? '';
        $client->country = $invoiceData['client_country'] ?? 'CZ';
        $client->ico = $invoiceData['client_ico'] ?? '';
        $client->dic = $invoiceData['client_dic'] ?? '';
        $client->email = $invoiceData['client_email'] ?? '';
        $client->phone = $invoiceData['client_phone'] ?? '';

        // Create supplier object
        $supplier = new \stdClass();
        $supplier->id = null;
        $supplier->name = $invoiceData['name'] ?? __('invoices.placeholders.unnamed_supplier');
        $supplier->street = $invoiceData['street'] ?? '';
        $supplier->city = $invoiceData['city'] ?? '';
        $supplier->zip = $invoiceData['zip'] ?? '';
        $supplier->country = $invoiceData['country'] ?? 'CZ';
        $supplier->ico = $invoiceData['ico'] ?? '';
        $supplier->dic = $invoiceData['dic'] ?? '';
        $supplier->email = $invoiceData['email'] ?? '';
        $supplier->phone = $invoiceData['phone'] ?? '';
        $supplier->account_number = $invoiceData['account_number'] ?? '';
        $supplier->bank_code = $invoiceData['bank_code'] ?? '';
        $supplier->bank_name = $invoiceData['bank_name'] ?? '';
        $supplier->iban = $invoiceData['iban'] ?? '';
        $supplier->swift = $invoiceData['swift'] ?? '';

        // Add supplier and client to invoice
        $invoice->supplier = $supplier;
        $invoice->client = $client;

        // Process invoice products
        $invoiceProducts = $this->processInvoiceProductsFromData($invoiceData);
        $invoice->invoiceProductsData = $invoiceProducts;

        // Get payment method and status
        $paymentMethod = $this->getPaymentMethodFromData($invoice);
        $paymentStatus = $this->getPaymentStatusFromData($invoice);

        // Generate QR code
        $qrCodeBase64 = $this->generateQrCodeFromInvoiceObject($invoice);

        // Data for PDF generation
        $data = [
            'invoice' => $invoice,
            'user' => null,
            'client' => $client,
            'supplier' => $supplier,
            'paymentMethod' => $paymentMethod,
            'paymentStatus' => $paymentStatus,
            'qrCode' => $qrCodeBase64,
            'hasQrCode' => !empty($qrCodeBase64),
            'locale' => $locale,
            'invoiceProducts' => $invoiceProducts,
        ];

        // Generate PDF
        return Pdf::loadView('pdfs.invoice', $data);
    }

    /**
     * Process invoice products from data
     * 
     * @param array $invoiceData
     * @return array
     */
    private function processInvoiceProductsFromData(array $invoiceData): array
    {
        $invoiceProducts = [];
        
        if (empty($invoiceData['invoice-products'])) {
            return $invoiceProducts;
        }
        
        try {
            $productsData = $invoiceData['invoice-products'];
            
            // Decode JSON if it's a string
            if (is_string($productsData)) {
                $productsData = json_decode($productsData, true);
                
                // Check for JSON decode errors
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('JSON decode error for invoice products: ' . json_last_error_msg());
                    return $invoiceProducts;
                }
            }
            
            // Create invoice product objects
            if (is_array($productsData)) {
                foreach ($productsData as $index => $product) {
                    $invoiceProduct = [];
                    $invoiceProduct['id'] = $index + 1;
                    $invoiceProduct['product_id'] = $product['product_id'] ?? null;
                    $invoiceProduct['name'] = $product['name'] ?? __('invoices.placeholders.unnamed_product');
                    $invoiceProduct['quantity'] = floatval($product['quantity'] ?? 1);
                    $invoiceProduct['unit'] = $product['unit'] ?? __('invoices.units.pieces');
                    $invoiceProduct['price'] = floatval($product['price'] ?? 0);
                    $invoiceProduct['currency'] = $product['currency'] ?? 'CZK';
                    $invoiceProduct['tax_rate'] = floatval($product['tax_rate'] ?? 21);
                    
                    // Calculate tax amount and total price
                    $invoiceProduct['tax_amount'] = ($invoiceProduct['price'] * $invoiceProduct['quantity'] * $invoiceProduct['tax_rate']) / 100;
                    $invoiceProduct['total_price'] = ($invoiceProduct['price'] * $invoiceProduct['quantity']) + $invoiceProduct['tax_amount'];
                    
                    $invoiceProducts[] = $invoiceProduct;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing invoice products: ' . $e->getMessage());
        }
        
        return $invoiceProducts;
    }

    /**
     * Get payment method based on temporary invoice data
     * 
     * @param \stdClass $tempInvoice
     * @return \stdClass
     */
    private function getPaymentMethodFromData(\stdClass $tempInvoice): \stdClass
    {
        // Check if payment_method_id exists in temporary invoice data
        if (empty($tempInvoice->payment_method_id)) {
            // Create default payment method object if none exists
            $paymentMethod = new \stdClass();
            $paymentMethod->id = 1;
            $paymentMethod->name = 'bank';
            $paymentMethod->slug = 'bank';
            $paymentMethod->description = '';
            $paymentMethod->is_active = 1;
            
            return $paymentMethod;
        }

        // Try to find payment method in database
        try {
            // Get payment method from database
            $dbPaymentMethod = PaymentMethod::find($tempInvoice->payment_method_id);
            
            if ($dbPaymentMethod) {
                // Convert to stdClass
                $paymentMethod = new \stdClass();
                $paymentMethod->id = $dbPaymentMethod->id;
                $paymentMethod->name = $dbPaymentMethod->name;
                $paymentMethod->slug = $dbPaymentMethod->slug;
                $paymentMethod->description = $dbPaymentMethod->description;
                $paymentMethod->is_active = $dbPaymentMethod->is_active;
                
                return $paymentMethod;
            }
        } catch (\Exception $e) {
            Log::error('Error getting payment method: ' . $e->getMessage());
        }

        // Create default payment method object if not found in database
        $paymentMethod = new \stdClass();
        $paymentMethod->id = 1;
        $paymentMethod->name = 'bank';
        $paymentMethod->slug = 'bank';
        $paymentMethod->description = '';
        $paymentMethod->is_active = 1;
        
        return $paymentMethod;
    }

    /**
     * Get payment status based on temporary invoice data
     * 
     * @param \stdClass $tempInvoice
     * @return \stdClass
     */
    private function getPaymentStatusFromData(\stdClass $tempInvoice): \stdClass
    {
        // Check if payment_status_id exists in temporary invoice data
        if (empty($tempInvoice->payment_status_id)) {
            // Create default payment status object if none exists
            $paymentStatus = new \stdClass();
            $paymentStatus->id = 1;
            $paymentStatus->name = 'unpaid';
            $paymentStatus->description = '';
            $paymentStatus->color = 'danger';
            $paymentStatus->is_active = 1;
            
            return $paymentStatus;
        }

        // Try to find payment status in database
        try {
            // Get payment status from database
            $dbStatus = Status::find($tempInvoice->payment_status_id);
            
            if ($dbStatus) {
                // Convert model to stdClass object
                $paymentStatus = new \stdClass();
                $paymentStatus->id = $dbStatus->id;
                $paymentStatus->name = $dbStatus->name;
                $paymentStatus->description = $dbStatus->description;
                $paymentStatus->color = $dbStatus->color;
                $paymentStatus->is_active = $dbStatus->is_active;
                
                return $paymentStatus;
            }
        } catch (\Exception $e) {
            Log::error('Error getting payment status: ' . $e->getMessage());
        }

        // Create default payment status object if not found in database
        $paymentStatus = new \stdClass();
        $paymentStatus->id = 1;
        $paymentStatus->name = 'unpaid';
        $paymentStatus->description = '';
        $paymentStatus->color = 'danger';
        $paymentStatus->is_active = 1;
        
        return $paymentStatus;
    }

    /**
     * Generate QR code from invoice object
     * 
     * @param \stdClass $invoice
     * @return string|null Base64 QR code
     */
    private function generateQrCodeFromInvoiceObject(\stdClass $invoice)
    {
        $qrCodeBase64 = null;
        
        try {
            if ((!empty($invoice->account_number) && !empty($invoice->bank_code)) || !empty($invoice->iban)) {
                $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($invoice);
            }
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());
        }
        
        return $qrCodeBase64;
    }
}
