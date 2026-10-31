<?php

declare(strict_types=1);

namespace App\Application\Payment\Mappers;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Shared\Money\ValueObjects\Money;

final class QrPaymentMapper
{
    public function fromSnapshot(
        ?string $number,
        Money $amount,
        ?DocumentAccountDTO $account,
        ?string $message,
    ): QrPaymentDTO {
        return new QrPaymentDTO(
            variableSymbol: $number,
            amount: $amount,
            account: $account?->account,
            message: $message,
        );
    }

    public function fromPrintableInvoice(PrintableInvoiceDTO $invoice): QrPaymentDTO
    {
        return $this->fromSnapshot(
            $invoice->variableSymbol,
            $invoice->totalAmount,
            $invoice->account,
            $invoice->notes,
        );
    }
}
