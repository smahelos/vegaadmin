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

    public function get(string $name): ?PaymentGatewayInterface
    {
        return $this->gateways[$name] ?? null;
    }
}
