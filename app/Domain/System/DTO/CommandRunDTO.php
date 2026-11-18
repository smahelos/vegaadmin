<?php

declare(strict_types=1);

namespace App\Domain\System\DTO;

final readonly class CommandRunDTO
{
    public function __construct(
        public int $exitCode,
        public string $output,
    ) {}

    public function succeeded(): bool
    {
        return $this->exitCode === 0;
    }
}
