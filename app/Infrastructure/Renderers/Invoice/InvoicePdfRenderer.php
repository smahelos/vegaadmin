<?php

declare(strict_types=1);

namespace App\Infrastructure\Renderers\Invoice;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Payment\Mappers\QrPaymentPayloadMapper;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use App\Domain\User\Contracts\Locale;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use DateTimeImmutable;
use Illuminate\Support\Facades\Storage;
use NumberFormatter;

final class InvoicePdfRenderer implements InvoicePdfRendererInterface
{
    public function __construct(
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly QrPaymentServiceInterface $qrPaymentService,
        private readonly QrPaymentPayloadMapper $qrPaymentMapper,
        private readonly DateFormatterInterface $dateFormatter,
        private readonly ClockInterface $clock,
        private readonly Locale $currentLocale,
    ) {}

    public function render(
        PrintableInvoiceDTO $data,
        ?string $template = null,
        ?string $locale = null,
    ): PdfDocument {
        $templateName = $this->getTemplateName($template ?? $data->template ?? 'default');
        $locale ??= $this->currentLocale->getLocale();

        $paymentAmountFormatted = $this->moneyFormatter->format($data->totalAmount, $locale);
        $subtotalFormatted = $this->moneyFormatter->format($data->subtotal, $locale);
        $taxFormatted = $this->moneyFormatter->format($data->totalTax, $locale);

        $numbers = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $numbers->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 3);

        $invoiceProductsFormatted = [];
        foreach ($data->items as $idx => $item) {
            $invoiceProductsFormatted[$idx] = [
                'name' => $item->name,
                'quantity_formatted' => $numbers->format($item->quantity),
                'unit' => $item->unit->value,
                'price_formatted' => $this->moneyFormatter->format($item->price, $locale),
                'tax_rate_formatted' => $numbers->format($item->taxRate),
                'total_price_formatted' => $this->moneyFormatter->format($item->lineTotal, $locale),
                'currency' => $data->currency,
            ];
        }

        $qrCode = null;
        $hasQr = false;
        try {
            if ($data->paymentMethod === PaymentMethodCode::BANK_ACCOUNT && $data->account !== null) {
                $qrPayload = $this->qrPaymentMapper->fromPrintableInvoice($data);
                $qrCode = $this->qrPaymentService->generateQrCodeBase64($qrPayload);
                $hasQr = ! empty($qrCode);
            }
        } catch (\Throwable) {
            // ignore QR generation errors in renderer
        }

        $viewData = [
            'invoice' => $data,
            'issueDateFormatted' => $this->formatDate($data->issueDate, $locale),
            'taxPointDateFormatted' => $this->formatDate($data->taxPointDate, $locale),
            'dueDateFormatted' => $this->formatDate($data->dueDate?->date(), $locale),
            'dueDays' => $data->dueDate?->days,
            'customer' => $data->customer,
            'supplier' => $data->supplier,
            'accountNumber' => $data->account?->account->number,
            'bankCode' => $data->account?->account->bankCode,
            'bankName' => $data->account?->bankName,
            'iban' => $data->account?->account->iban,
            'swift' => $data->account?->account->swift,
            'logoPath' => $data->supplierLogo !== null && Storage::disk('public')->exists($data->supplierLogo)
                ? Storage::disk('public')->path($data->supplierLogo) : null,
            'paymentMethod' => $data->paymentMethod,
            'qrCode' => $qrCode,
            'hasQrCode' => $hasQr,
            'locale' => $locale,
            'generatedAt' => $this->dateFormatter->formatDateTime($this->clock->now(), $locale),
            'paymentAmountFormatted' => $paymentAmountFormatted,
            'subtotalFormatted' => $subtotalFormatted,
            'taxFormatted' => $taxFormatted,
            'invoiceProductsFormatted' => $invoiceProductsFormatted,

        ];

        $previousLocale = app()->getLocale();
        try {
            app()->setLocale($locale);

            return Pdf::loadView($templateName, $viewData);
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    private function formatDate(?DateTimeImmutable $date, string $locale): ?string
    {
        if ($date === null) {
            return null;
        }

        return $this->dateFormatter->formatDate($date, $locale);
    }

    private function getTemplateName(?string $template): string
    {
        $available = [
            'default',
            'modern',
            'minimal',
        ];
        $tpl = $template ?? 'default';
        if (! in_array(
            $tpl,
            $available,
            true,
        )) {
            $tpl = 'default';
        }
        if (! $this->templateFileExists($tpl)) {
            $tpl = 'default';
        }

        return "pdfs.templates.{$tpl}";
    }

    private function templateFileExists(string $template): bool
    {
        $templatePath = resource_path("views/pdfs/templates/{$template}.blade.php");

        return file_exists($templatePath);
    }
}
