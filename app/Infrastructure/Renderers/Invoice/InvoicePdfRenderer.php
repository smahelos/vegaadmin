<?php

declare(strict_types=1);

namespace App\Infrastructure\Renderers\Invoice;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Payment\Mappers\QrPaymentMapper;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Number\Contracts\NumberFormatterInterface;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use DateTimeImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

final class InvoicePdfRenderer implements InvoicePdfRendererInterface
{
    public function __construct(
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly QrPaymentServiceInterface $qrPaymentService,
        private readonly QrPaymentMapper $qrPaymentMapper,
        private readonly DateFormatterInterface $dateFormatter,
        private readonly NumberFormatterInterface $numberFormatter,
    ) {}

    public function render(PrintableInvoiceDTO $data, string $locale): PdfDocument
    {
        $paymentAmountFormatted = $this->moneyFormatter->formatWithCurrencyAfter($data->totalAmount, $locale);
        $totals = $data->totals;
        $vatLinesFormatted = [];
        foreach ($totals->vatLines as $vatLine) {
            $vatLineTotal = $vatLine->total();
            $vatLinesFormatted[] = [
                'rate_formatted' => $this->numberFormatter->format($vatLine->rate, $locale),
                'base_formatted' => $this->moneyFormatter->formatWithCurrencyAfter($vatLine->base, $locale),
                'vat_formatted' => $this->moneyFormatter->formatWithCurrencyAfter($vatLine->vat, $locale),
                'total_formatted' => $this->moneyFormatter->formatWithCurrencyAfter($vatLineTotal, $locale),
            ];
        }

        $invoiceProductsFormatted = [];
        foreach ($data->items as $idx => $item) {
            $invoiceProductsFormatted[$idx] = [
                'name' => $item->name,
                'quantity_formatted' => $this->numberFormatter->format($item->quantity, $locale),
                'unit' => $item->unit->value,
                'price_formatted' => $this->moneyFormatter->formatWithCurrencyAfter($item->price, $locale),
                'tax_rate_formatted' => $this->numberFormatter->format($item->taxRate, $locale),
                'base_formatted' => $this->moneyFormatter->formatWithCurrencyAfter($item->base, $locale),
                'currency' => $data->currency,
            ];
        }

        $qrCode = null;
        $hasQr = false;
        // A draft is no tax document and has no variable symbol to pay with (D-050, point 2)
        if (
            $data->paymentMethod === PaymentMethodCode::BANK_ACCOUNT
            && $data->account !== null
            && $data->variableSymbol !== null
        ) {
            $qrPayload = $this->qrPaymentMapper->fromPrintableInvoice($data);
            $qrCode = $this->qrPaymentService->generateQrCodeBase64($qrPayload);
            $hasQr = !empty($qrCode);
        }

        $logoPath = null;
        if ($data->supplierLogo !== null) {
            $disk = Storage::disk('public');
            if ($disk->exists($data->supplierLogo)) {
                $logoPath = $disk->path($data->supplierLogo);
            }
        }

        $viewData = [
            'invoice' => $data,
            'issueDateFormatted' => $this->formatDate($data->issueDate, $locale),
            'taxPointDateFormatted' => $this->formatDate($data->taxPointDate, $locale),
            'dueDateFormatted' => $this->formatDate($data->dueDate?->date(), $locale),
            'dueDays' => $data->dueIn,
            'customer' => $data->customer,
            'supplier' => $data->supplier,
            'accountNumber' => $data->account?->account->number,
            'bankCode' => $data->account?->account->bankCode,
            'bankName' => $data->account?->bankName,
            'iban' => $data->account?->account->iban,
            'swift' => $data->account?->account->swift,
            'logoPath' => $logoPath,
            'paymentMethod' => $data->paymentMethod,
            'qrCode' => $qrCode,
            'hasQrCode' => $hasQr,
            'locale' => $locale,
            'paymentAmountFormatted' => $paymentAmountFormatted,
            'vatLinesFormatted' => $vatLinesFormatted,
            'invoiceProductsFormatted' => $invoiceProductsFormatted,

        ];

        $previousLocale = App::getLocale();
        try {
            App::setLocale($locale);

            return Pdf::loadView('pdfs.templates.' . $data->template->value, $viewData);
        } finally {
            App::setLocale($previousLocale);
        }
    }

    private function formatDate(?DateTimeImmutable $date, string $locale): ?string
    {
        if ($date === null) {
            return null;
        }

        return $this->dateFormatter->formatDate($date, $locale);
    }
}
