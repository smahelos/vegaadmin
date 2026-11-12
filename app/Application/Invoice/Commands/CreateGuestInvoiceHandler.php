<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\DTO\CreateGuestInvoiceResult;
use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use App\Application\Payment\Mappers\QrPaymentMapper;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\Contracts\Locale;
use Exception;

/**
 * The parties are stored as entered, in the shape a stored invoice keeps them, so the print reads both alike.
 */
final class CreateGuestInvoiceHandler
{
    public function __construct(
        private readonly Locale $locale,
        private readonly QrPaymentServiceInterface $qrPayments,
        private readonly TemporaryInvoiceStoreInterface $store,
        private readonly QrPaymentMapper $qrPaymentMapper,
        private readonly InvoiceProductWriteDataAssembler $products,
        private readonly LogInterface $log,
    ) {}

    public function handle(CreateGuestInvoice $command): CreateGuestInvoiceResult
    {
        $data = $command->validated;
        $locale = $this->locale->determineLocale($command->locale);
        $data['lang'] = $locale;
        $data['supplier'] = $command->supplier;
        $data['customer'] = $command->customer;
        $data['account'] = $command->account;

        if (isset($data['due_in'])) {
            $data['due_in'] = (int) $data['due_in'];
        }

        $totals = InvoiceTotals::fromProducts(
            Currency::from((string) $data['payment_currency']),
            $this->products->fromValidated($data['invoice-products']),
        );
        $total = $totals->total();
        $data['payment_amount'] = $total->getAmount();

        $token = $this->store->store($data);

        $qrCode = null;
        try {
            if ($command->account !== null) {
                $qrPayload = $this->qrPaymentMapper->fromSnapshot(
                    $data['invoice_vs'] ?? null,
                    Money::fromString($data['payment_amount'], $data['payment_currency']),
                    $command->account,
                    $data['invoice_text'] ?? null,
                );
                $qrCode = $this->qrPayments->generateQrCodeBase64($qrPayload);
            }
        } catch (Exception $e) {
            // The invoice is stored; it only goes out without the QR code
            $this->log->log(
                'error',
                'Guest invoice QR generation failed',
                ['exception' => $e],
            );
        }

        return new CreateGuestInvoiceResult(
            $token,
            $locale,
            $data['invoice_vs'] ?? null,
            $qrCode,
        );
    }
}
