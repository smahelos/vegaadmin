<?php

namespace Tests\Support\Fakes\Payment;

use App\Domain\Payment\Contracts\PaymentGatewayInterface;

/**
 * Simple in-memory registry stub for payment gateways in tests.
 */
class GatewayRegistryStub
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function register(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    public function get(string $name): ?PaymentGatewayInterface
    {
        return $this->gateways[$name] ?? null;
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        return array_keys($this->gateways);
    }
}
