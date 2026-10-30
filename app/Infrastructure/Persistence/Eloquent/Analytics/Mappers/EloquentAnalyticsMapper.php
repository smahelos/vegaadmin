<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Mappers;

use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Money\ValueObjects\Money;

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
}
