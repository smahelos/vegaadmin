<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Shared;

use App\Domain\Shared\Log\Contracts\LogInterface;

final class RecordingLog implements LogInterface
{
    /** @var list<array{level: string, message: string, context: array<mixed>}> */
    public array $entries = [];

    public function log(
        string $level,
        string $message,
        array $context = [],
    ): void {
        $this->entries[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}
