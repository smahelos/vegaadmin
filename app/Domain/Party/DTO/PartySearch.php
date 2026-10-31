<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\Enums\PartySort;

/**
 * One page of the user's active or archived businesses or contacts. The term matches the name, short name,
 * company number, email or city.
 */
final readonly class PartySearch
{
    public function __construct(
        public ?string $term,
        public bool $archived,
        public PartySort $sort,
        public bool $ascending,
    ) {}
}
