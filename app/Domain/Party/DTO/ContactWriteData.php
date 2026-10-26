<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

/**
 * The whole editable contact; the default account and archiving are separate operations.
 */
final readonly class ContactWriteData
{
    public function __construct(
        public PartyProfile $profile,
        public ?string $contactPerson,
    ) {}
}
