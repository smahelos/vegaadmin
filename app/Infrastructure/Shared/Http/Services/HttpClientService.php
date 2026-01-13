<?php

namespace App\Infrastructure\Shared\Http\Services;

use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use Illuminate\Support\Facades\Http;

/**
 * Infrastructure implementation of HttpClientInterface.
 *
 * Contains framework dependent logic (HTTP requests) thus lives in Infrastructure layer.
 */
class HttpClientService implements HttpClientInterface
{
    public function get(string $url, array $headers = []): array
    {
        $response = Http::withHeaders($headers)->get($url);
        if ($response->successful()) {
            $json = $response->json();
            return is_array($json) ? $json : [];
        }
        return [];
    }

    public function post(string $url, array $data = [], array $headers = []): array
    {
        $response = Http::withHeaders($headers)->post($url, $data);
        if ($response->successful()) {
            $json = $response->json();
            return is_array($json) ? $json : [];
        }
        return [];
    }

    public function put(string $url, array $data = [], array $headers = []): array
    {
        $response = Http::withHeaders($headers)->put($url, $data);
        $return = [];
        $json = $response->json();
        $return['data'] = is_array($json) ? $json : [];
        $return['status'] = $response->status();
        $return['success'] = $response->successful() ? true : false;
        return $return;
    }
}
