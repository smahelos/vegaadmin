<?php

namespace App\Domain\Shared\Notifications\DTO;

class Recipient
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $preferredLocale = null,
    ) {}
}
