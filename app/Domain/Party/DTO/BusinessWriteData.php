<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

/**
 * The whole editable business; the default account, archiving and the default business are separate operations.
 */
final readonly class BusinessWriteData
{
    public function __construct(
        public PartyProfile $profile,
        public ?string $logo,
    ) {}
}
