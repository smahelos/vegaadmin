<?php

namespace App\Domain\Shared\Session\Contracts;

/**
 * Framework-agnostic session interface for Domain layer.
 *
 * This port abstracts session access so Domain services do not rely
 * on Laravel facades or framework-specific implementations.
 */
interface SessionInterface
{
    /**
     * Get a value from session by key.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Put a value into session.
     */
    public function put(string $key, mixed $value): void;

    /**
     * Forget a key from session.
     */
    public function forget(string $key): void;
}
