<?php

declare(strict_types=1);

namespace App\Application\Invoice\Presenters;

use App\Domain\Invoice\DTO\InvoiceListDTO;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;

final class InvoiceListPresenter
{
    public function __construct(
        private readonly MoneyFormatterInterface $money,
        private readonly DateFormatterInterface $dates,
        private readonly ClockInterface $clock,
    ) {}

    public function amount(InvoiceListDTO $invoice, string $locale): string
    {
        return $this->money->formatWithCode($invoice->amount, $locale);
    }

    public function dueDate(InvoiceListDTO $invoice, string $locale): string
    {
        return $invoice->dueDate === null ? '—' : $this->dates->formatDate($invoice->dueDate->date(), $locale);
    }

    public function isOverdue(InvoiceListDTO $invoice): bool
    {
        return $invoice->status?->isUnpaid() === true && $invoice->dueDate !== null && $invoice->dueDate->daysUntil($this->clock->now()) < 0;
    }
}
