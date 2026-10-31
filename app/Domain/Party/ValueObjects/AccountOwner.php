<?php

declare(strict_types=1);

namespace App\Domain\Party\ValueObjects;

/**
 * The business or contact a bank account belongs to; exactly one of the two ids is set.
 */
final readonly class AccountOwner
{
    private function __construct(
        public ?BusinessId $businessId,
        public ?ContactId $contactId,
    ) {}

    public static function business(BusinessId $id): self
    {
        return new self($id, null);
    }

    public static function contact(ContactId $id): self
    {
        return new self(null, $id);
    }
}
