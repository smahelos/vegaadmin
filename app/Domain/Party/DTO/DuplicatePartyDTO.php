<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\Enums\DuplicateReason;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;

/**
 * An existing record of the same entity that may be the party being saved.
 */
final readonly class DuplicatePartyDTO
{
    public function __construct(
        public BusinessId|ContactId $id,
        public string $name,
        public bool $archived,
        public DuplicateReason $reason,
    ) {}
}
