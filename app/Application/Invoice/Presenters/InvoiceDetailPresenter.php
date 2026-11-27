<?php

declare(strict_types=1);

namespace App\Application\Invoice\Presenters;

use App\Domain\Invoice\DTO\InvoiceIssue;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Number\Contracts\NumberFormatterInterface;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use DateTimeImmutable;

final class InvoiceDetailPresenter
{
    public function __construct(
        private readonly MoneyFormatterInterface $money,
        private readonly DateFormatterInterface $dates,
        private readonly NumberFormatterInterface $numbers,
    ) {}

    public function money(Money $amount, string $locale): string
    {
        return $this->money->formatWithCode($amount, $locale);
    }

    /** @param  string  $decimal  A quantity or a VAT rate as a decimal string */
    public function number(string $decimal, string $locale): string
    {
        return $this->numbers->format($decimal, $locale);
    }

    public function date(DateTimeImmutable $date, string $locale): string
    {
        return $this->dates->formatDate($date, $locale);
    }

    /** Only an issued invoice has a due date; a draft is due the given days after its issue. */
    public function dueDate(
        InvoiceIssue $issue,
        int $dueIn,
        string $locale,
    ): string {
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issue->date, $dueIn);

        return $this->dates->formatDate($dueDate->date(), $locale);
    }
}
