<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Shared;

use App\Domain\Shared\Hash\Contracts\HashInterface;

final class PrefixHash implements HashInterface
{
    public function hash(string $value): string
    {
        return 'hash:' . $value;
    }
}
