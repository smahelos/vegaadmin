<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Domain\Invoice\DTO\PrintableInvoiceData;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Application\Payment\Mappers\QrPaymentPayloadMapper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoicePdfRenderer implements InvoicePdfRendererInterface
{
    public function __construct(
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly QrPaymentServiceInterface $qrPaymentService,
        private readonly QrPaymentPayloadMapper $qrPaymentMapper,
    ) {}

    public function render(PrintableInvoiceData $data, ?string $template = null, ?string $locale = null)
    {
        $templateName = $this->getTemplateName($template ?? $data->template ?? 'default');

        // Prepare formatted monetary values
        $paymentAmountFormatted = $this->moneyFormatter->format($data->total_amount, $locale);
        $subtotalFormatted = $this->moneyFormatter->format($data->subtotal, $locale);
        $taxFormatted = $this->moneyFormatter->format($data->total_tax, $locale);

        // Map items to array expected by view when formatted present
        $invoiceProductsFormatted = [];
        foreach ($data->items as $idx => $item) {
            $invoiceProductsFormatted[$idx] = [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'price_formatted' => $this->moneyFormatter->format($item->price, $locale),
                'tax_rate' => $item->tax_rate,
                'total_price_formatted' => $this->moneyFormatter->format($item->line_total, $locale),
                'currency' => $data->currency,
            ];
        }
        Log::error('invoiceProductsFormatted', ['data' => $invoiceProductsFormatted]);

        // Build lightweight stdClass invoice for compatibility with existing Blade
        $invoice = (object) [
            'invoice_vs' => $data->number,
            'invoice_ss' => $data->invoice_ss,
            'invoice_ks' => $data->invoice_ks,
            'issue_date' => $data->issue_date?->format('Y-m-d'),
            'tax_point_date' => $data->tax_point_date?->format('Y-m-d'),
            'due_in' => $data->due_in,
            'due_date' => $data->due_date?->format('Y-m-d'),
            'payment_amount' => $data->total_amount->toFloat(),
            'payment_currency' => $data->currency,
            'invoice_text' => $data->notes,
            'account_number' => $data->account_number,
            'bank_code' => $data->bank_code,
            'bank_name' => $data->bank_name,
            'iban' => $data->iban,
            'swift' => $data->swift,
            'invoice_logo' => $data->invoice_logo,
        ];

        $supplier = (object) [
            'name' => $data->supplier_name,
            'email' => $data->supplier_email,
            'phone' => $data->supplier_phone,
            'street' => $data->supplier_street,
            'city' => $data->supplier_city,
            'zip' => $data->supplier_zip,
            'country' => $data->supplier_country,
            'ico' => $data->supplier_ico,
            'dic' => $data->supplier_dic,
            'supplier_logo' => $data->supplier_logo,
            'account_number' => $data->account_number,
            'bank_code' => $data->bank_code,
            'bank_name' => $data->bank_name,
            'iban' => $data->iban,
            'swift' => $data->swift,
        ];

        $client = (object) [
            'name' => $data->client_name,
            'email' => $data->client_email,
            'phone' => $data->client_phone,
            'street' => $data->client_street,
            'city' => $data->client_city,
            'zip' => $data->client_zip,
            'country' => $data->client_country,
            'ico' => $data->client_ico,
            'dic' => $data->client_dic,
        ];

        // Try to generate QR code if payment information present
        $qrCode = null; $hasQr = false;
        $qrCandidate = (object) [
            'invoice_vs' => $data->number,
            'payment_amount' => $data->total_amount->toFloat(),
            'payment_currency' => $data->currency,
            'account_number' => $data->account_number,
            'bank_code' => $data->bank_code,
            'iban' => $data->iban,
        ];
        try {
            if ((!empty($data->account_number) && !empty($data->bank_code)) || !empty($data->iban)) {
                $qrPayload = $this->qrPaymentMapper->fromArray($qrCandidate);
                $qrCode = $this->qrPaymentService->generateQrCodeBase64($qrPayload);
                $hasQr = !empty($qrCode);
            }
        } catch (\Throwable) {
            // ignore QR generation errors in renderer
        }

        $viewData = [
            'invoice' => $invoice,
            'user' => Auth::user(),
            'client' => $client,
            'supplier' => $supplier,
            'paymentMethod' => null, // left null; app can enrich if needed
            'qrCode' => $qrCode,
            'hasQrCode' => $hasQr,
            'locale' => $locale,
            'paymentAmountFormatted' => $paymentAmountFormatted,
            'subtotalFormatted' => $subtotalFormatted,
            'taxFormatted' => $taxFormatted,
            'invoiceProductsFormatted' => $invoiceProductsFormatted,
            // Provide raw VO arrays for <x-money> compatibility if used
                'paymentAmount' => ['amount' => $data->total_amount->getAmount(), 'currency' => $data->total_amount->getCurrency()],
                'subtotal' => ['amount' => $data->subtotal->getAmount(), 'currency' => $data->subtotal->getCurrency()],
                'totalTax' => ['amount' => $data->total_tax->getAmount(), 'currency' => $data->total_tax->getCurrency()],
        ];

        return Pdf::loadView($templateName, $viewData);
    }

    private function getTemplateName(?string $template): string
    {
        $available = ['default', 'modern', 'minimal'];
        $tpl = $template ?? 'default';
        if (!in_array($tpl, $available, true)) { $tpl = 'default'; }
        if (!$this->templateFileExists($tpl)) { $tpl = 'default'; }
        return "pdfs.templates.{$tpl}";
    }

    protected function templateFileExists(string $template): bool
    {
        $templatePath = resource_path("views/pdfs/templates/{$template}.blade.php");
        return file_exists($templatePath);
    }
}
