<?php

declare(strict_types=1);

namespace App\Application\Payment\Mappers;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Shared\Money\ValueObjects\Money;

final class QrPaymentPayloadMapper
{
    public function fromSnapshot(
        ?string $number,
        Money $amount,
        ?DocumentAccountDTO $account,
        ?string $message,
    ): QrPaymentPayload {
        return new QrPaymentPayload(
            variableSymbol: $number,
            amount: $amount,
            accountNumber: $account?->account->number,
            bankCode: $account?->account->bankCode,
            iban: $account?->account->iban,
            countryCode: $account?->account->country->value,
            message: $message,
        );
    }

    public function fromPrintableInvoice(PrintableInvoiceDTO $invoice): QrPaymentPayload
    {
        return $this->fromSnapshot(
            $invoice->variableSymbol,
            $invoice->totalAmount,
            $invoice->account,
            $invoice->notes,
        );
    }
}
