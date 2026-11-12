<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

final readonly class CreateGuestInvoiceResult
{
    public function __construct(
        public string $token,
        public string $locale,
        public ?string $number,
        public ?string $qrCode,
    ) {}
}
