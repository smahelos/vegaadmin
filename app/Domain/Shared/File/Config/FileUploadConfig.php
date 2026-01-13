<?php

namespace App\Domain\Shared\File\Config;


/**
 * High-level configuration accessor & merger for file upload contexts.
 */
class FileUploadConfig
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('file_upload', []);
    }

    public function forContext(?string $context = null): FileUploadContextConfig
    {
        $default = $this->config['default'] ?? [];
        $contexts = $this->config['contexts'] ?? [];
        $override = $context && isset($contexts[$context]) ? $contexts[$context] : [];

        $merged = $this->deepMerge($default, $override);
        return new FileUploadContextConfig(
            max(1, (int)($merged['max_kb'] ?? 10240)),
            array_values(array_unique(array_map('strtolower', (array)($merged['allowed_extensions'] ?? [])))),
            array_values(array_unique((array)($merged['allowed_mime_groups'] ?? []))),
            (array)($merged['thumbnail'] ?? []),
            (bool)($merged['hash_deduplication']['enabled'] ?? false)
        );
    }

    private function deepMerge(array $base, array $override): array
    {
        foreach ($override as $k => $v) {
            if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
                // If both are sequential (list) arrays, fully replace instead of merging to avoid residual values.
                if ($this->isListArray($base[$k]) && $this->isListArray($v)) {
                    $base[$k] = $v;
                } else {
                    $base[$k] = $this->deepMerge($base[$k], $v);
                }
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }

    private function isListArray(array $arr): bool
    {
        if ($arr === []) { return true; }
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
