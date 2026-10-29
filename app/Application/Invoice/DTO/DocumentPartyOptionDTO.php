<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;

final readonly class DocumentPartyOptionDTO
{
    public function __construct(
        public int $id,
        public string $label,
        public ?bool $isDefault = null,
        public ?DocumentPartyDTO $profile = null,
        public ?DocumentAccountDTO $account = null,
    ) {}
}
