<?php

namespace App\Domain\Shared\Http\Contracts;


interface HttpClientInterface
{
    /** 
     * Perform a GET request and return the response as an associative array
     * @param string $url
     * @param array $headers
     * @return array<string, mixed>
     */
    public function get(string $url, array $headers = []): array;

    /** 
     * Perform a POST request with given data and return the response as an associative array
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array<string, mixed>
     */
    public function post(string $url, array $data = [], array $headers = []): array;

    /**
     * Perform a PUT request with given data and return the response as an associative array
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return ?array
     */
    public function put(string $url, array $data = [], array $headers = []): ?array;
}
