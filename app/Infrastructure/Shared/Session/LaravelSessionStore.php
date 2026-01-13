<?php

namespace App\Infrastructure\Shared\Session;

use App\Domain\Shared\Session\Contracts\SessionInterface;
use Illuminate\Support\Facades\Session;

/**
 * Laravel adapter for Domain SessionInterface.
 */
class LaravelSessionStore implements SessionInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Session::get($key, $default);
    }

    public function put(string $key, mixed $value): void
    {
        Session::put($key, $value);
    }

    public function forget(string $key): void
    {
        Session::forget($key);
    }
}
