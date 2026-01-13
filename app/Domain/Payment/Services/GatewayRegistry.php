<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;

/**
 * Default in-memory implementation of GatewayRegistryInterface.
 */
class GatewayRegistry implements GatewayRegistryInterface
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];
    /** @var array<string,array<string,mixed>> */
    private array $meta = [];

    public function register(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
        // Derive basic metadata lazily (can be overridden later via explicit setMetadata in future if needed)
        $this->meta[$name] = [
            'display_name' => method_exists($gateway, 'getDisplayName') ? $gateway->getDisplayName() : $name,
            'currencies' => method_exists($gateway, 'getSupportedCurrencies') ? $gateway->getSupportedCurrencies() : [],
            'recurring' => method_exists($gateway, 'supportsRecurringPayments') ? $gateway->supportsRecurringPayments($name) : null,
        ];
    }

    public function get(string $name): ?PaymentGatewayInterface
    {
        return $this->gateways[$name] ?? null;
    }

    public function names(): array
    {
        return array_keys($this->gateways);
    }

    public function remove(string $name): void
    {
        unset($this->gateways[$name], $this->meta[$name]);
    }

    public function metadata(string $name): ?array
    {
        return $this->meta[$name] ?? null;
    }

    public function allMetadata(): array
    {
        return $this->meta;
    }
}
