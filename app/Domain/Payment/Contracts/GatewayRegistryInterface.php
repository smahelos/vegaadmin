<?php

namespace App\Domain\Payment\Contracts;

/**
 * Registry contract for resolving payment gateways by name.
 */
interface GatewayRegistryInterface
{
    /** Register a gateway instance under a name. */
    public function register(string $name, PaymentGatewayInterface $gateway): void;

    /** Get a gateway by name or null if not registered. */
    public function get(string $name): ?PaymentGatewayInterface;

    /** Return all registered gateway names. */
    public function names(): array;

    /** Remove a gateway registration if it exists. */
    public function remove(string $name): void;

    /** Return metadata for a single gateway (null if missing). */
    public function metadata(string $name): ?array;

    /** Return metadata array for all gateways. */
    public function allMetadata(): array;
}
