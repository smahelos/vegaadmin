<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Mappers;

use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\UserActivityDTO;
use App\Domain\Analytics\Enums\ActivityLevel;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\UserActivitySummary;
use DateTimeImmutable;

final class EloquentAnalyticsMapper
{
    public static function customer(
        ?int $contactId,
        string $name,
        string $total,
    ): CustomerTotalDTO {
        return new CustomerTotalDTO(
            $contactId === null ? null : ContactId::fromInt($contactId),
            $name,
            Money::fromString($total, 'CZK'),
        );
    }

    public static function userActivity(UserActivitySummary $summary): UserActivityDTO
    {
        $lastInvoice = null;
        if ($summary->last_invoice_date !== null) {
            $lastInvoice = DateTimeImmutable::createFromInterface($summary->last_invoice_date);
        }

        return new UserActivityDTO(
            $summary->user_name,
            $summary->user_email,
            $summary->invoices_last_30_days,
            $summary->invoices_last_7_days,
            $lastInvoice,
            ActivityLevel::fromInvoices($summary->invoices_last_30_days),
        );
    }
}
