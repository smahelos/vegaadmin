<?php

namespace App\Domain\Shared\Config\Contracts;

interface Config
{
    public function get(string $key, mixed $default = null): mixed;
}
