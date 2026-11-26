<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Shared\Translation\Enums\Language;

/**
 * How a business issues documents (D-050, point 6).
 */
final readonly class InvoicingSettings
{
    public function __construct(
        public bool $vatPayer,
        public ?string $registerEntry,
        public Language $documentLanguage,
    ) {}
}
