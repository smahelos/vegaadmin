<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TemporaryInvoiceStore implements TemporaryInvoiceStoreInterface
{
    public function store(array $data, int $minutes = 10): string
    {
        $token = Str::random(64);
        Cache::put($this->key($token), $data, now()->addMinutes($minutes));
        return $token;
    }

    public function get(string $token): ?array
    {
        return Cache::get($this->key($token));
    }

    public function delete(string $token): bool
    {
        return Cache::forget($this->key($token));
    }

    private function key(string $token): string
    {
        return 'invoice_data_' . $token;
    }
}
