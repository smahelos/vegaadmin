<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface;
use App\Domain\User\Contracts\Locale;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\Contracts\BankServiceInterface;
use App\Application\Payment\Mappers\QrPaymentPayloadMapper;
use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Tax;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GuestInvoiceApplicationService implements GuestInvoiceApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceFormApplicationServiceInterface $invoiceFormService,
        private readonly Locale $locale,
        private readonly QrPaymentServiceInterface $qrPaymentService,
        private readonly BankServiceInterface $bankService,
        private readonly InvoicePdfRendererInterface $invoicePdfRenderer,
        private readonly TemporaryInvoicePrintDataFactoryInterface $tempPrintDataFactory,
        private readonly TemporaryInvoiceStoreInterface $temporaryInvoiceStore,
        private readonly QrPaymentPayloadMapper $qrPaymentMapper,
    ) {}

    public function storeGuestInvoice(array $validatedData, array|string|null $invoiceProducts, ?string $requestedLocale): array
    {
        $locale = $this->locale->determineLocale($requestedLocale);
        $validatedData['lang'] = $locale;

        if (isset($validatedData['due_in'])) {
            $validatedData['due_in'] = (int)$validatedData['due_in'];
        }

        if (is_string($invoiceProducts)) {
            $validatedData['invoice-products'] = $invoiceProducts;
        } elseif (is_array($invoiceProducts)) {
            $validatedData['invoice-products'] = json_encode($invoiceProducts);
        } else {
            $validatedData['invoice-products'] = json_encode([]);
        }

        $token = $this->temporaryInvoiceStore->store($validatedData);

        Session::put('last_guest_invoice_token', $token);
        Session::put('last_guest_invoice_number', $validatedData['invoice_vs'] ?? null);
        Session::put('last_guest_invoice_expires', now()->addMinutes(10)->timestamp);

        $tempInvoice = new \stdClass();
        foreach ($validatedData as $k => $v) { $tempInvoice->$k = $v; }
        if (isset($tempInvoice->due_in)) { $tempInvoice->due_in = (int)$tempInvoice->due_in; }

        $qrCodeBase64 = null;
        try {
            if (((!empty($tempInvoice->account_number) && !empty($tempInvoice->bank_code)) || !empty($tempInvoice->iban))) {
                $qrPayload = $this->qrPaymentMapper->fromArray($tempInvoice);
                $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($qrPayload);
            }
        } catch (\Exception $e) {
            Log::error('Error while generating QR code: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => __('invoices.messages.created_guest'),
            'invoice_number' => $validatedData['invoice_vs'] ?? null,
            'download_url' => route('frontend.invoice.download.token', [
                'locale' => $locale,
                'token' => $token
            ]),
            'token' => $token,
            'qr_code' => $qrCodeBase64,
            'has_qr_code' => !empty($qrCodeBase64),
        ];
    }

    public function prepareGuestCreateData(): array
    {
        $paymentMethods = PaymentMethod::all()->pluck('slug', 'id')->toArray();
        $statuses = Status::pluck('name', 'id')->toArray();
        $taxRates = Tax::where('slug', 'dph')->pluck('rate', 'id')->toArray();
        $banks = $this->bankService->getBanksForDropdown();
        $banksData = $this->bankService->getBanksForJs();
        // Guest context: no authenticated user ID => use 0 sentinel to start sequence at YYYY0001
        $suggestedNumber = $this->invoiceFormService->getNextInvoiceNumber(0);
        $itemUnits = $this->invoiceFormService->getItemUnits();
        $limitsData = [ 'limit' => 0, 'current_usage' => 0, 'allowed' => false ];
        $userInfo = [ 'name' => '', 'street' => '', 'city' => '', 'zip' => '', 'country' => 'CZ', 'ico' => '', 'dic' => '', 'email' => '', 'phone' => '' ];
        $clients = $suppliers = [];
        $invoiceProducts = [];
        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build($clients, $suppliers, $paymentMethods, $statuses);
        $fields = $fieldSet->toArray();
        
        return compact('paymentMethods','statuses','taxRates','banks','banksData','suggestedNumber','itemUnits','limitsData','userInfo','clients','suppliers','invoiceProducts','fields');
    }

    public function generatePdfByToken(?string $token, ?string $requestedLocale, bool $preview = false): Response
    {
        if (!$token) {
            $token = Session::get('last_guest_invoice_token');
            if (!$token) {
                abort(404, __('invoices.messages.not_found'));
            }
        }

        Log::info('Starting PDF generation with token: ' . substr($token, 0, 8) . '...');
        $invoiceData = $this->temporaryInvoiceStore->get($token);
        if (!$invoiceData) {
            abort(404, __('invoices.messages.expired'));
        }

    $printData = $this->tempPrintDataFactory->fromArray($invoiceData);
    $pdf = $this->invoicePdfRenderer->render($printData, $printData->template ?? null, $requestedLocale);
        $filename = 'faktura-' . ($invoiceData['invoice_vs'] ?? date('YmdHis')) . '.pdf';

        if ($preview) {
            return $pdf->stream($filename);
        }

        $locale = $this->locale->determineLocale($requestedLocale, $invoiceData['lang'] ?? null);
        $response = $pdf->download($filename);
        return $response->cookie('locale', $locale, 60 * 24 * 30);
    }

    public function deleteTemporary(string $token): bool
    {
        $sessionToken = Session::get('last_guest_invoice_token');
        if ($sessionToken !== $token) {
            return false;
        }
        $this->temporaryInvoiceStore->delete($token);
        Session::forget('last_guest_invoice_token');
        Session::forget('last_guest_invoice_number');
        Session::forget('last_guest_invoice_expires');
        return true;
    }

    /**
     * Get item units for guest invoices
     */
    private function getItemUnits(): array
    {
        return [
            'hours' => __('invoices.units.hours'),
            'days' => __('invoices.units.days'),
            'pieces' => __('invoices.units.pieces'),
        ];
    }
}
