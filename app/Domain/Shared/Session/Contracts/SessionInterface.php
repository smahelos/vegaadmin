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
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Put a value into session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, mixed $value): void;

    /**
     * Forget a key from session.
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void;
}
