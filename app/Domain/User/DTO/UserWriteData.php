<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

final readonly class UserWriteData
{
    /**
     * @param  ?string  $password  plain text, hashed on storing; null keeps the stored password
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
    ) {}
}
