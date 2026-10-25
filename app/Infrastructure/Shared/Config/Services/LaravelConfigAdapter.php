<?php

namespace App\Infrastructure\Shared\Config\Services;

use App\Domain\Shared\Config\Contracts\Config;

class LaravelConfigAdapter implements Config
{
    public function get(string $key, mixed $default = null): mixed
    {
        return config($key, $default);
    }
}
